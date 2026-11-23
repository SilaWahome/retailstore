<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPortion;
use App\Models\Currency;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\InventoryLog;
use App\Models\Branch;
use App\Models\IoTecTransaction;
use App\Services\IoTecService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class POSController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $activeBranchId = session('active_branch_id', 'all');

        $query = Product::where('tenant_id', $tenantId)->where('is_active', true)->with('portions')->orderBy('name');
        if ($activeBranchId !== 'all') {
            $query->where(function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId)->orWhereNull('branch_id');
            });
        }
        $products = $query->get();

        $currencies = Currency::where('tenant_id', $tenantId)->orderBy('code')->get();
        $baseCurrency = Currency::activeForTenant($tenantId);
        $tenant = auth()->user()->tenant;
        
        $editingSale = null;
        $editingSaleData = null;

        if ($request->filled('edit_sale')) {
            if (auth()->user()->role !== 'admin') {
                abort(403, 'Only administrators can edit sales.');
            }

            $editingSale = Sale::where('tenant_id', $tenantId)
                ->with('items.product')
                ->find($request->edit_sale);

            if ($editingSale) {
                $editingSaleData = [
                    'id' => $editingSale->id,
                    'customer_name' => $editingSale->customer_name,
                    'customer_phone' => $editingSale->customer_phone,
                    'discount' => (float) $editingSale->discount,
                    'tax' => (float) $editingSale->tax,
                    'subtotal' => (float) $editingSale->subtotal,
                    'total' => (float) $editingSale->total,
                    'payment_method' => $editingSale->payment_method,
                    'amount_paid' => (float) $editingSale->amount_paid,
                    'items' => $editingSale->items->map(function ($item) {
                        return [
                    'product_id'    => $item->product_id,
                            'name'          => $item->product ? $item->product->name : 'Item #' . $item->product_id,
                            'price'         => (float) $item->price,
                            'quantity'      => (float) $item->quantity,
                            'unit_label'    => $item->unit_label ?? 'pcs',
                            'portion_label' => $item->portion_label ?? null,
                            'stock'         => (float) (($item->product ? $item->product->quantity : 0) + $item->quantity),
                            'sold_by'       => $item->product->sold_by ?? 'unit',
                        ];
                    })->toArray(),
                ];
            }
        }

        return view('pos', compact('products', 'currencies', 'baseCurrency', 'tenant', 'editingSale', 'editingSaleData'));
    }

    public function scanProduct(Request $request)
    {
        $sku = $request->query('sku');
        if (! $sku) {
            return response()->json(['error' => 'SKU barcode parameter is required.'], 400);
        }

        $activeBranchId = session('active_branch_id', 'all');
        $query = Product::where('sku', $sku)->where('is_active', true);

        if ($activeBranchId !== 'all') {
            $query->where(function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId)->orWhereNull('branch_id');
            });
        }

        $product = $query->first();

        if (! $product) {
            return response()->json(['error' => 'Product not found.'], 404);
        }

        return response()->json([
            'id'         => $product->id,
            'name'       => $product->name,
            'sku'        => $product->sku,
            'price'      => (float) $product->price,
            'quantity'   => (float) $product->quantity,
            'sold_by'    => $product->sold_by,
            'base_unit'  => $product->base_unit,
            'portions'   => $product->portions->map(fn($p) => [
                'id'       => $p->id,
                'label'    => $p->label,
                'quantity' => (float) $p->quantity,
                'price'    => (float) $p->price,
            ]),
            'image_path' => $product->image_path ? asset('storage/' . $product->image_path) : null,
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'customer_name' => 'nullable|string|max:100',
            'customer_phone' => 'nullable|string|max:50',
            'mobile_network' => 'nullable|string|in:MTN,Airtel',
            'discount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string',
            'currency_code' => 'required|string|exists:currencies,code',
            'amount_paid' => 'required|numeric|min:0',
            'cart' => 'required|json', // [{"product_id": 1, "qty": 2}]
            'editing_sale_id' => 'nullable|integer',
        ]);

        if ($request->payment_method === 'debt' && (empty($request->customer_name) || empty($request->customer_phone))) {
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'error' => 'Customer Name and Phone Number are required to record a Debt / Credit sale.'], 422);
            }
            return back()->withErrors(['error' => 'Customer Name and Phone Number are required to record a Debt / Credit sale.'])->withInput();
        }

        if ($request->payment_method === 'mobile' && empty($request->customer_phone)) {
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'error' => 'Customer Mobile Number is required for Mobile Money STK push payment.'], 422);
            }
            return back()->withErrors(['error' => 'Customer Mobile Number is required for Mobile Money STK push payment.'])->withInput();
        }

        $isEditing = $request->filled('editing_sale_id');

        if ($isEditing && auth()->user()->role !== 'admin') {
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'error' => 'Only administrators can edit sales.'], 403);
            }
            return back()->withErrors(['error' => 'Only administrators can edit sales.']);
        }

        $cart = json_decode($request->cart, true);
        if (empty($cart)) {
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'error' => 'Your cart is empty.'], 422);
            }
            return back()->withErrors(['error' => 'Your cart is empty.']);
        }

        $currency = Currency::where('is_base', true)->first() ?: Currency::where('code', $request->currency_code)->firstOrFail();
        $exchangeRate = 1.0000;

        // Resolve branch_id
        $activeBranchId = session('active_branch_id');
        if (!$activeBranchId || $activeBranchId === 'all') {
            $activeBranchId = auth()->user()->branch_id ?? Branch::where('tenant_id', auth()->user()->tenant_id)->first()?->id;
        }

        DB::beginTransaction();
        try {
            $editingSale = null;
            if ($isEditing) {
                $editingSale = Sale::where('tenant_id', auth()->user()->tenant_id)
                    ->with('items.product')
                    ->findOrFail($request->editing_sale_id);

                // Restore stock from old sale items
                foreach ($editingSale->items as $oldItem) {
                    if ($oldItem->product) {
                        $oldItem->product->increment('quantity', $oldItem->quantity);
                        InventoryLog::create([
                            'tenant_id' => auth()->user()->tenant_id,
                            'branch_id' => $activeBranchId,
                            'product_id' => $oldItem->product_id,
                            'change_amount' => $oldItem->quantity,
                            'type' => 'adjustment',
                            'description' => 'Restored stock for Sale #' . $editingSale->id . ' edit/redo',
                        ]);
                    }
                }

                // Delete old items
                $editingSale->items()->delete();
            }

            $subtotalBase = 0;

            // 1. Verify stock and calculate subtotal
            $itemsData = [];
            foreach ($cart as $item) {
                $product = Product::findOrFail($item['product_id']);

                $requestedQty = (float) $item['qty'];
                if ((float) $product->quantity < $requestedQty) {
                    $stockLabel = number_format((float) $product->quantity, 3) . ' ' . $product->base_unit;
                    throw new \Exception('Insufficient stock for ' . $product->name . '. Available: ' . $stockLabel);
                }

                // Use portion price if the cart item references a portion
                $unitPrice    = (float) $product->price;
                $portionLabel = $item['portion_label'] ?? null;
                if (!empty($item['portion_id'])) {
                    $portion = ProductPortion::find($item['portion_id']);
                    if ($portion && $portion->product_id === $product->id) {
                        $unitPrice    = (float) $portion->price;
                        $portionLabel = $portion->label;
                        // qty is always 1 portion = portion->quantity base units in stock
                        // but cart qty is already the base-unit quantity to deduct
                    }
                }

                $itemSubtotal = $unitPrice * $requestedQty;
                $subtotalBase += $itemSubtotal;

                $itemsData[] = [
                    'product'       => $product,
                    'qty'           => $requestedQty,
                    'price'         => $unitPrice,
                    'total'         => $itemSubtotal,
                    'unit_label'    => $product->base_unit,
                    'portion_label' => $portionLabel,
                ];
            }

            // 2. Calculate discounts/tax in Base Currency
            $discountBase = (float)$request->discount;
            
            // Tax calculation
            $taxRate = (float)$request->tax_rate; // e.g. 5 for 5%
            $taxBase = ($subtotalBase - $discountBase) * ($taxRate / 100);
            $totalBase = $subtotalBase - $discountBase + $taxBase;
            if ($totalBase < 0) {
                $totalBase = 0;
            }

            // 3. Extra 500 UGX charged for Mobile Money checkout
            $mobileFee = 0.00;
            if ($request->payment_method === 'mobile') {
                $mobileFee = 500.00;
                $totalBase += $mobileFee;
            }

            // Convert total to payment currency
            $totalPaymentCurrency = $totalBase * $exchangeRate;
            $amountPaid = (float)$request->amount_paid;

            // For mobile money, if amount paid was exact before fee, adjust to total
            if ($request->payment_method === 'mobile' && $amountPaid < $totalPaymentCurrency) {
                $amountPaid = $totalPaymentCurrency;
            }

            $isDebt = ($request->payment_method === 'debt');
            $isMobile = ($request->payment_method === 'mobile');
            $paymentStatus = 'paid';

            if ($isDebt) {
                if ($amountPaid < $totalPaymentCurrency) {
                    $paymentStatus = 'unpaid';
                }
                $changePaymentCurrency = max(0, $amountPaid - $totalPaymentCurrency);
            } elseif ($isMobile) {
                $paymentStatus = 'pending'; // STK push sent to client, awaiting PIN
                $changePaymentCurrency = 0.00;
            } else {
                if ($amountPaid < $totalPaymentCurrency) {
                    throw new \Exception('Insufficient amount paid. Total in ' . $currency->code . ' is ' . number_format($totalPaymentCurrency, 2));
                }
                $changePaymentCurrency = $amountPaid - $totalPaymentCurrency;
            }

            // 4. Create or Update Sale
            if ($isEditing && $editingSale) {
                $editingSale->update([
                    'customer_name' => $request->customer_name,
                    'customer_phone' => $request->customer_phone,
                    'subtotal' => $subtotalBase,
                    'discount' => $discountBase,
                    'tax' => $taxBase,
                    'total' => $totalBase,
                    'currency_code' => $currency->code,
                    'exchange_rate' => $exchangeRate,
                    'amount_paid' => $amountPaid,
                    'change_amount' => $changePaymentCurrency,
                    'payment_method' => $request->payment_method,
                    'payment_status' => $paymentStatus,
                ]);
                $sale = $editingSale;
            } else {
                $sale = Sale::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'branch_id' => $activeBranchId,
                    'user_id' => auth()->id(),
                    'customer_name' => $request->customer_name,
                    'customer_phone' => $request->customer_phone,
                    'subtotal' => $subtotalBase,
                    'discount' => $discountBase,
                    'tax' => $taxBase,
                    'total' => $totalBase,
                    'currency_code' => $currency->code,
                    'exchange_rate' => $exchangeRate,
                    'amount_paid' => $amountPaid,
                    'change_amount' => $changePaymentCurrency,
                    'payment_method' => $request->payment_method,
                    'payment_status' => $paymentStatus,
                ]);
            }

            // 5. Create SaleItems, decrement inventory, log inventory changes
            foreach ($itemsData as $data) {
                $product = $data['product'];
                
                SaleItem::create([
                    'sale_id'       => $sale->id,
                    'product_id'    => $product->id,
                    'quantity'      => $data['qty'],
                    'unit_label'    => $data['unit_label'],
                    'portion_label' => $data['portion_label'],
                    'price'         => $data['price'],
                    'total'         => $data['total'],
                ]);

                // Decrement stock (decimal)
                $product->decrement('quantity', $data['qty']);

                // Log change with unit context
                $unitStr = number_format($data['qty'], 3) . ' ' . $data['unit_label'];
                InventoryLog::create([
                    'tenant_id'     => auth()->user()->tenant_id,
                    'branch_id'     => $activeBranchId,
                    'product_id'    => $product->id,
                    'change_amount' => -$data['qty'],
                    'type'          => 'sale',
                    'description'   => $isEditing
                        ? 'Sold ' . $unitStr . ' via Sale #' . $sale->id . ' update'
                        : 'Sold ' . $unitStr . ' via POS checkout (Sale ID: #' . $sale->id . ')',
                ]);
            }

            // 6. Mobile Money STK Push handling via ioTec Pay
            $iotecTx = null;
            if ($request->payment_method === 'mobile') {
                $formattedPhone = IoTecService::formatUgandanPhone($request->customer_phone);
                $network = $request->input('mobile_network') ?: IoTecService::detectNetwork($formattedPhone);
                $externalId = 'POS-COL-' . $sale->id . '-' . time();

                $iotecTx = IoTecTransaction::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'user_id' => auth()->id(),
                    'sale_id' => $sale->id,
                    'type' => 'collection',
                    'network' => $network,
                    'phone_number' => $formattedPhone,
                    'amount' => $totalBase - $mobileFee,
                    'fee' => $mobileFee,
                    'net_amount' => $totalBase,
                    'currency' => 'UGX',
                    'status' => 'pending',
                    'external_id' => $externalId,
                    'payer_or_payee_name' => $request->customer_name ?: 'Walk-in Customer',
                    'notes' => 'POS Mobile Money Checkout. Extra 500 UGX charged. Sale #' . $sale->id,
                ]);

                $iotecService = app(IoTecService::class);
                $stkRes = $iotecService->initiateCollection(
                    $totalBase,
                    $formattedPhone,
                    $externalId,
                    $request->customer_name ?: 'Customer',
                    'Sale #' . $sale->id . ' Payment',
                    'POS Mobile Checkout'
                );

                if (!empty($stkRes['success'])) {
                    $iotecTx->update([
                        'status' => $stkRes['status'] ?? 'pending',
                        'iotec_request_id' => $stkRes['request_id'] ?? null,
                        'iotec_transaction_id' => $stkRes['transaction_id'] ?? null,
                        'response_data' => $stkRes['data'] ?? null,
                    ]);
                } else {
                    $errorMsg = $stkRes['message'] ?? 'Failed to send Mobile Money STK prompt to customer.';
                    $iotecTx->update([
                        'status' => 'failed',
                        'error_message' => $errorMsg,
                        'response_data' => $stkRes['data'] ?? null,
                    ]);
                    throw new \Exception($errorMsg);
                }
            }

            DB::commit();

            $sale->load('items.product', 'user', 'branch');
            $receiptMsg = $isEditing
                ? 'Sale #' . $sale->id . ' updated successfully!'
                : 'Sale #' . $sale->id . ' initiated.';

            if ($request->payment_method === 'mobile' && $iotecTx) {
                $receiptMsg = 'STK prompt pushed to ' . $iotecTx->phone_number . '. Awaiting customer PIN authorization.';
            }

            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'is_mobile' => ($request->payment_method === 'mobile'),
                    'message' => $receiptMsg,
                    'sale' => $sale,
                    'iotec_transaction' => $iotecTx,
                    'is_demo' => !empty($stkRes['is_demo']),
                ]);
            }

            return redirect()->route('pos')
                ->with('success', $receiptMsg)
                ->with('receiptSale', $sale);
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'error' => 'Checkout failed: ' . $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['error' => 'Checkout failed: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Check STK push collection transaction status (AJAX polling).
     */
    public function checkStkStatus(IoTecTransaction $transaction)
    {
        if ($transaction->tenant_id !== auth()->user()->tenant_id && auth()->user()->role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $iotecService = app(IoTecService::class);
        $res = $iotecService->getCollectionStatus(
            $transaction->external_id,
            $transaction->iotec_transaction_id ?: $transaction->iotec_request_id
        );

        if ($res['success'] && isset($res['status'])) {
            $newStatus = $res['status'];
            $transaction->update([
                'status' => $newStatus,
                'response_data' => $res['data'] ?? $transaction->response_data,
            ]);

            if ($newStatus === 'complete' && $transaction->sale) {
                $transaction->sale->update([
                    'payment_status' => 'paid',
                ]);
            } elseif (in_array($newStatus, ['failed', 'rejected', 'cancelled', 'expired']) && $transaction->sale) {
                $transaction->sale->update([
                    'payment_status' => 'failed',
                ]);
            }
        }

        return response()->json([
            'status' => $transaction->status,
            'message' => 'Status is ' . strtoupper($transaction->status),
            'transaction' => $transaction,
            'sale' => $transaction->sale ? $transaction->sale->load('items.product', 'user', 'branch') : null,
        ]);
    }

    /**
     * Cancel an active STK push transaction and restore stock.
     */
    public function cancelStk(IoTecTransaction $transaction)
    {
        if ($transaction->tenant_id !== auth()->user()->tenant_id && auth()->user()->role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $transaction->update([
            'status' => 'cancelled',
            'notes' => ($transaction->notes ? $transaction->notes . ' | ' : '') . 'Cancelled by cashier',
        ]);

        if ($transaction->sale) {
            foreach ($transaction->sale->items as $item) {
                if ($item->product) {
                    $item->product->increment('quantity', $item->quantity);
                    InventoryLog::create([
                        'tenant_id' => auth()->user()->tenant_id,
                        'branch_id' => $transaction->sale->branch_id,
                        'product_id' => $item->product_id,
                        'change_amount' => $item->quantity,
                        'type' => 'adjustment',
                        'description' => 'Stock restored after cancelled STK push (Sale #' . $transaction->sale->id . ')',
                    ]);
                }
            }

            $transaction->sale->update([
                'payment_status' => 'cancelled',
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => 'cancelled',
            'message' => 'STK Push was cancelled and stock has been restored.',
        ]);
    }

    /**
     * Simulate customer approving or declining PIN in Sandbox/Demo mode.
     */
    public function simulateStk(IoTecTransaction $transaction, Request $request)
    {
        if (!config('app.debug') || !env('ENABLE_STK_SIMULATION', false)) {
            return response()->json([
                'error' => 'Simulated approvals are disabled for security. Real customer telecom PIN confirmation is required.'
            ], 403);
        }

        if ($transaction->tenant_id !== auth()->user()->tenant_id && auth()->user()->role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $action = $request->input('action', 'complete');

        if ($action === 'complete') {
            $transaction->update([
                'status' => 'complete',
                'response_data' => array_merge((array) $transaction->response_data, [
                    'simulated' => true,
                    'status' => 'Success',
                    'authorized_at' => now()->toIso8601String(),
                ]),
            ]);

            if ($transaction->sale) {
                $transaction->sale->update([
                    'payment_status' => 'paid',
                ]);
            }
        } else {
            $transaction->update([
                'status' => 'failed',
                'error_message' => 'Simulated: Customer entered incorrect PIN or cancelled prompt on handset.',
            ]);

            if ($transaction->sale) {
                foreach ($transaction->sale->items as $item) {
                    if ($item->product) {
                        $item->product->increment('quantity', $item->quantity);
                    }
                }
                $transaction->sale->update([
                    'payment_status' => 'failed',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'status' => $transaction->status,
            'transaction' => $transaction,
            'sale' => $transaction->sale ? $transaction->sale->load('items.product', 'user', 'branch') : null,
        ]);
    }
}
