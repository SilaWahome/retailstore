<?php

namespace App\Http\Controllers;

use App\Models\IoTecTransaction;
use App\Services\IoTecService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DisbursementController extends Controller
{
    protected IoTecService $iotecService;

    public function __construct(IoTecService $iotecService)
    {
        $this->iotecService = $iotecService;
    }

    /**
     * Display the disbursement / withdrawal portal for the store admin.
     */
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        // 1. Calculate available balance in UGX
        // Total collected from completed mobile money sales
        $totalCollected = (float) IoTecTransaction::where('tenant_id', $tenantId)
            ->where('type', 'collection')
            ->where('status', 'complete')
            ->sum('amount');

        // Total withdrawn or pending disbursement
        $totalWithdrawn = (float) IoTecTransaction::where('tenant_id', $tenantId)
            ->where('type', 'disbursement')
            ->whereIn('status', ['complete', 'pending'])
            ->sum('amount');

        $availableBalance = max(0, $totalCollected - $totalWithdrawn);

        // 2. Fetch disbursement history for this tenant
        $disbursements = IoTecTransaction::where('tenant_id', $tenantId)
            ->where('type', 'disbursement')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // 3. Total SaaS fees deducted for this tenant (5% commission)
        $totalSaasFeesPaid = (float) IoTecTransaction::where('tenant_id', $tenantId)
            ->where('type', 'disbursement')
            ->where('status', 'complete')
            ->sum('fee');

        return view('finances.disbursements', compact(
            'availableBalance',
            'totalCollected',
            'totalWithdrawn',
            'totalSaasFeesPaid',
            'disbursements'
        ));
    }

    /**
     * Submit a withdrawal request via IoTec Disbursements API.
     */
    public function withdraw(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        // Calculate available balance
        $totalCollected = (float) IoTecTransaction::where('tenant_id', $tenantId)
            ->where('type', 'collection')
            ->where('status', 'complete')
            ->sum('amount');

        $totalWithdrawn = (float) IoTecTransaction::where('tenant_id', $tenantId)
            ->where('type', 'disbursement')
            ->whereIn('status', ['complete', 'pending'])
            ->sum('amount');

        $availableBalance = max(0, $totalCollected - $totalWithdrawn);

        $request->validate([
            'network' => 'required|string|in:MTN,Airtel',
            'phone_number' => 'required|string|min:9|max:15',
            'amount' => 'required|numeric|min:1000',
        ], [
            'amount.min' => 'The minimum withdrawal amount is UGX 1,000.',
            'network.in' => 'Please select either MTN Uganda or Airtel Uganda.',
        ]);

        $amount = (float) $request->amount;

        if ($amount > $availableBalance) {
            return back()->withErrors([
                'amount' => 'Insufficient funds. Your available balance is UGX ' . number_format($availableBalance) . '.',
            ])->withInput();
        }

        // Format phone number
        $formattedPhone = IoTecService::formatUgandanPhone($request->phone_number);

        // Calculate 5% (.05) platform fee for SaaS superadmin
        $saasFee = round($amount * 0.05, 2);
        $netDisbursed = $amount - $saasFee;

        $externalId = 'DISB-' . $tenantId . '-' . time() . '-' . rand(100, 999);

        // Record pending transaction
        $transaction = IoTecTransaction::create([
            'tenant_id' => $tenantId,
            'user_id' => auth()->id(),
            'type' => 'disbursement',
            'network' => $request->network,
            'phone_number' => $formattedPhone,
            'amount' => $amount,
            'fee' => $saasFee,
            'net_amount' => $netDisbursed,
            'currency' => 'UGX',
            'status' => 'pending',
            'external_id' => $externalId,
            'payer_or_payee_name' => auth()->user()->name,
            'notes' => 'Store withdrawal: UGX ' . number_format($amount) . ' (SaaS 5% Fee: UGX ' . number_format($saasFee) . ', Net Sent: UGX ' . number_format($netDisbursed) . ')',
        ]);

        // Trigger IoTec Disbursement API
        $res = $this->iotecService->initiateDisbursement(
            $netDisbursed,
            $formattedPhone,
            $externalId,
            auth()->user()->name,
            'Store Admin Withdrawal',
            'Retail Store Payout'
        );

        if ($res['success']) {
            $transaction->update([
                'status' => $res['status'] ?? 'pending',
                'iotec_transaction_id' => $res['transaction_id'] ?? null,
                'response_data' => $res['data'] ?? null,
            ]);

            return redirect()->route('finances.disbursements')
                ->with('success', 'Withdrawal request of UGX ' . number_format($amount) . ' submitted successfully! UGX ' . number_format($netDisbursed) . ' sent to ' . $formattedPhone . ' (' . $request->network . '). UGX ' . number_format($saasFee) . ' (5%) retained by platform.');
        } else {
            $transaction->update([
                'status' => 'failed',
                'error_message' => $res['message'] ?? 'API error occurred',
                'response_data' => $res['data'] ?? null,
            ]);

            return back()->withErrors([
                'error' => 'Withdrawal failed: ' . ($res['message'] ?? 'Unable to connect to payment provider.'),
            ])->withInput();
        }
    }

    /**
     * Check status of a disbursement transaction.
     */
    public function checkStatus(IoTecTransaction $transaction)
    {
        if ($transaction->tenant_id !== auth()->user()->tenant_id && auth()->user()->role !== 'superadmin') {
            abort(403);
        }

        $res = $this->iotecService->getDisbursementStatus($transaction->external_id, $transaction->iotec_transaction_id);

        if ($res['success'] && isset($res['status'])) {
            $transaction->update([
                'status' => $res['status'],
                'response_data' => $res['data'] ?? $transaction->response_data,
            ]);

            return back()->with('success', 'Transaction #' . $transaction->external_id . ' status updated to ' . strtoupper($res['status']) . '.');
        }

        return back()->with('error', 'Could not refresh transaction status at this moment.');
    }
}
