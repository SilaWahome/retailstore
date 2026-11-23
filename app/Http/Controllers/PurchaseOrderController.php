<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\InventoryLog;
use App\Models\Branch;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $activeBranchId = session('active_branch_id', 'all');

        $query = PurchaseOrder::with('items.product', 'branch')->orderByDesc('created_at');
        if ($activeBranchId !== 'all') {
            $query->where('branch_id', $activeBranchId);
        }
        $purchaseOrders = $query->get();

        $productQuery = Product::orderBy('name');
        if ($activeBranchId !== 'all') {
            $productQuery->where(function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId)->orWhereNull('branch_id');
            });
        }
        $products = $productQuery->get();

        $baseCurrency = Currency::activeForTenant($tenantId);
        $branches = Branch::where('tenant_id', $tenantId)->where('is_active', true)->get();
        
        return view('purchase_orders.index', compact('purchaseOrders', 'products', 'baseCurrency', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_name' => 'required|string|max:191',
            'po_number' => 'nullable|string|max:50',
            'amount_paid' => 'nullable|numeric|min:0',
            'branch_id' => 'nullable|exists:branches,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.cost' => 'required|numeric|min:0',
        ]);

        $poNumber = $request->po_number ?: 'PO-' . rand(100000, 999999);

        if (PurchaseOrder::where('po_number', $poNumber)->exists()) {
            return back()->withErrors(['po_number' => 'This PO number has already been used.'])->withInput();
        }

        $activeBranchId = session('active_branch_id');
        $branchId = $request->filled('branch_id') 
            ? $request->branch_id 
            : (($activeBranchId && $activeBranchId !== 'all') ? $activeBranchId : auth()->user()->branch_id);

        DB::beginTransaction();
        try {
            $totalCost = 0;
            foreach ($request->items as $item) {
                $totalCost += $item['quantity'] * $item['cost'];
            }

            $amountPaid = (float)$request->amount_paid;
            $paymentStatus = 'unpaid';
            if ($amountPaid >= $totalCost && $totalCost > 0) {
                $paymentStatus = 'paid';
                $amountPaid = $totalCost;
            } elseif ($amountPaid > 0) {
                $paymentStatus = 'partial';
            }

            $po = PurchaseOrder::create([
                'tenant_id' => auth()->user()->tenant_id,
                'branch_id' => $branchId,
                'po_number' => $poNumber,
                'supplier_name' => $request->supplier_name,
                'status' => 'pending',
                'total_cost' => $totalCost,
                'amount_paid' => $amountPaid,
                'payment_status' => $paymentStatus,
            ]);

            foreach ($request->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'cost' => $item['cost'],
                ]);
            }

            DB::commit();
            return redirect()->route('purchase_orders.index')->with('success', 'Purchase Order ' . $poNumber . ' created successfully in PENDING status.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create PO: ' . $e->getMessage()])->withInput();
        }
    }

    public function receive(PurchaseOrder $po)
    {
        if ($po->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending Purchase Orders can be received.']);
        }

        DB::beginTransaction();
        try {
            $po->update(['status' => 'received']);

            foreach ($po->items as $item) {
                if ($item->product) {
                    $product = $item->product;
                    $oldQty = $product->quantity;
                    $product->update(['quantity' => $oldQty + $item->quantity]);

                    InventoryLog::create([
                        'tenant_id' => auth()->user()->tenant_id,
                        'branch_id' => $po->branch_id,
                        'product_id' => $product->id,
                        'change_amount' => $item->quantity,
                        'type' => 'purchase_order',
                        'description' => 'Received Purchase Order: ' . $po->po_number . ' from ' . $po->supplier_name,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('purchase_orders.index')->with('success', 'Purchase Order ' . $po->po_number . ' has been received. Inventory counts updated!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to receive PO: ' . $e->getMessage()]);
        }
    }

    public function cancel(PurchaseOrder $po)
    {
        if ($po->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending Purchase Orders can be cancelled.']);
        }

        $po->update(['status' => 'cancelled']);
        return redirect()->route('purchase_orders.index')->with('success', 'Purchase Order ' . $po->po_number . ' cancelled.');
    }

    public function payVendor(Request $request, PurchaseOrder $po)
    {
        $request->validate([
            'payment_amount' => 'required|numeric|min:0.01',
        ]);

        $newPaid = (float)$po->amount_paid + (float)$request->payment_amount;
        $status = 'partial';

        if ($newPaid >= $po->total_cost) {
            $newPaid = $po->total_cost;
            $status = 'paid';
        }

        $po->update([
            'amount_paid' => $newPaid,
            'payment_status' => $status,
        ]);

        return redirect()->route('purchase_orders.index')->with('success', 'Vendor payment of ' . number_format($request->payment_amount, 2) . ' recorded for PO ' . $po->po_number);
    }
}
