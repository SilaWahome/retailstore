<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $activeBranchId = session('active_branch_id', 'all');

        $query = Quotation::with('items', 'branch')->orderByDesc('created_at');
        if ($activeBranchId !== 'all') {
            $query->where('branch_id', $activeBranchId);
        }
        $quotations = $query->get();
        $branches = Branch::where('tenant_id', $tenantId)->where('is_active', true)->get();

        return view('quotations.index', compact('quotations', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'provider_name' => 'required|string|max:191',
            'quote_number' => 'nullable|string|max:50',
            'valid_until' => 'required|date|after_or_equal:today',
            'branch_id' => 'nullable|exists:branches,id',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string|max:191',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $quoteNumber = $request->quote_number ?: 'QT-' . rand(100000, 999999);

        if (Quotation::where('quote_number', $quoteNumber)->exists()) {
            return back()->withErrors(['quote_number' => 'This quotation number already exists.'])->withInput();
        }

        $activeBranchId = session('active_branch_id');
        $branchId = $request->filled('branch_id') 
            ? $request->branch_id 
            : (($activeBranchId && $activeBranchId !== 'all') ? $activeBranchId : auth()->user()->branch_id);

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += $item['quantity'] * $item['price'];
            }

            $quotation = Quotation::create([
                'tenant_id' => auth()->user()->tenant_id,
                'branch_id' => $branchId,
                'provider_name' => $request->provider_name,
                'quote_number' => $quoteNumber,
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'valid_until' => $request->valid_until,
            ]);

            foreach ($request->items as $item) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            DB::commit();
            return redirect()->route('quotations.index')->with('success', 'Quotation ' . $quoteNumber . ' created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create quotation: ' . $e->getMessage()])->withInput();
        }
    }

    public function updateStatus(Request $request, Quotation $quotation)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,expired',
        ]);

        $quotation->update(['status' => $request->status]);

        return redirect()->route('quotations.index')->with('success', 'Quotation ' . $quotation->quote_number . ' status updated to ' . strtoupper($request->status) . '.');
    }
}
