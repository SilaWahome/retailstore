<?php

namespace App\Http\Controllers;

use App\Models\IoTecTransaction;
use App\Services\IoTecService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionBillingController extends Controller
{
    public function __construct(protected IoTecService $iotecService)
    {
    }

    /**
     * Initiate an on-site Mobile Money STK push to pay the tenant's pending bill (or 1-month renewal).
     */
    public function initiatePayment(Request $request)
    {
        $user = auth()->user();
        $tenant = $user->tenant;

        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'No tenant account linked.'], 422);
        }

        $validated = $request->validate([
            'phone_number' => 'required|string|min:9|max:15',
        ]);

        $pending = $tenant->pendingBillAmountUgx();
        $amount = $pending > 0 ? $pending : $tenant->effectiveMonthlyFeeUgx();

        if ($amount < 500) {
            return response()->json(['success' => false, 'message' => 'No billable amount due.'], 422);
        }

        $formattedPhone = IoTecService::formatUgandanPhone($validated['phone_number']);
        $network = IoTecService::detectNetwork($formattedPhone);
        $externalId = 'SUB-' . $tenant->id . '-' . time() . '-' . random_int(100, 999);

        $transaction = IoTecTransaction::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'type' => 'subscription',
            'network' => $network,
            'phone_number' => $formattedPhone,
            'amount' => $amount,
            'fee' => 0,
            'net_amount' => $amount,
            'currency' => 'UGX',
            'status' => 'pending',
            'external_id' => $externalId,
            'payer_or_payee_name' => $tenant->name,
            'notes' => "Monthly subscription payment for {$tenant->name} (UGX " . number_format($amount) . ")",
        ]);

        $res = $this->iotecService->initiateCollection(
            $amount,
            $formattedPhone,
            $externalId,
            $user->name,
            'Luxeshop Monthly Subscription',
            'Luxeshop Billing',
            route('settings.index')
        );

        if ($res['success']) {
            $transaction->update([
                'status' => $res['status'] ?? 'pending',
                'iotec_request_id' => $res['request_id'] ?? null,
                'iotec_transaction_id' => $res['transaction_id'] ?? null,
                'response_data' => $res['data'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
                'amount' => $amount,
                'phone_number' => $formattedPhone,
                'network' => $network,
                'message' => $res['message'] ?? 'Mobile Money prompt sent to your phone. Enter PIN to complete payment.',
            ]);
        }

        $transaction->update([
            'status' => 'failed',
            'error_message' => $res['message'] ?? 'Failed to initiate Mobile Money prompt.',
            'response_data' => $res['data'] ?? null,
        ]);

        return response()->json([
            'success' => false,
            'status' => 'failed',
            'message' => $res['message'] ?? 'Unable to send Mobile Money prompt.',
        ], 422);
    }

    /**
     * Poll Mobile Money STK push status and activate/extend the tenant subscription once complete.
     */
    public function checkStatus(IoTecTransaction $transaction)
    {
        $user = auth()->user();
        if ($transaction->tenant_id !== $user->tenant_id || $transaction->type !== 'subscription') {
            abort(403);
        }

        if ($transaction->status === 'complete') {
            $tenant = $user->tenant->fresh();
            return response()->json([
                'success' => true,
                'status' => 'complete',
                'message' => 'Payment already confirmed! Subscription is active.',
                'subscription_ends_at' => $tenant?->subscription_ends_at,
            ]);
        }

        $res = $this->iotecService->getCollectionStatus($transaction->external_id, $transaction->iotec_transaction_id);
        $newStatus = $res['status'] ?? 'pending';

        if ($newStatus === 'complete') {
            $tenant = DB::transaction(function () use ($transaction, $res) {
                $lockedTx = IoTecTransaction::where('id', $transaction->id)->lockForUpdate()->firstOrFail();
                $tenant = $lockedTx->tenant()->lockForUpdate()->firstOrFail();

                if ($lockedTx->status !== 'complete') {
                    $lockedTx->update([
                        'status' => 'complete',
                        'response_data' => $res['data'] ?? $lockedTx->response_data,
                    ]);

                    $baseDate = ($tenant->subscription_ends_at && Carbon::parse($tenant->subscription_ends_at)->isFuture())
                        ? Carbon::parse($tenant->subscription_ends_at)
                        : now();

                    $tenant->update([
                        'pending_bill_ugx' => 0,
                        'subscription_status' => 'active',
                        'subscription_ends_at' => $baseDate->copy()->addMonth(),
                    ]);
                }

                return $tenant->fresh();
            });

            return response()->json([
                'success' => true,
                'status' => 'complete',
                'message' => 'Payment confirmed! Your subscription is now active until ' . Carbon::parse($tenant->subscription_ends_at)->format('Y-m-d') . '.',
                'subscription_ends_at' => $tenant->subscription_ends_at,
            ]);
        }

        if ($newStatus === 'failed') {
            $transaction->update([
                'status' => 'failed',
                'error_message' => $res['message'] ?? 'Payment declined or expired.',
                'response_data' => $res['data'] ?? $transaction->response_data,
            ]);
        }

        return response()->json([
            'success' => $newStatus !== 'failed',
            'status' => $newStatus,
            'message' => $res['message'] ?? 'Waiting for Mobile Money PIN confirmation...',
        ]);
    }
}
