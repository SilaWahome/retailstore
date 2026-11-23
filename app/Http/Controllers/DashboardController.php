<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Expense;
use App\Models\Product;
use App\Models\User;
use App\Models\PurchaseOrder;
use App\Models\Currency;
use App\Models\Branch;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $baseCurrency = Currency::activeForTenant($tenantId);
        $activeBranchId = session('active_branch_id', 'all');

        $salesQuery = Sale::where('tenant_id', $tenantId);
        $expensesQuery = Expense::where('tenant_id', $tenantId);
        $productsQuery = Product::where('tenant_id', $tenantId);

        if ($activeBranchId !== 'all') {
            $salesQuery->where('branch_id', $activeBranchId);
            $expensesQuery->where('branch_id', $activeBranchId);
            $productsQuery->where(function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId)->orWhereNull('branch_id');
            });
        }

        // 1. Core Financial Summaries (Base Currency)
        $totalSales = (float) (clone $salesQuery)->sum('total');
        
        $cogsQuery = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.tenant_id', $tenantId)
            ->whereNull('sales.deleted_at');
        if ($activeBranchId !== 'all') {
            $cogsQuery->where('sales.branch_id', $activeBranchId);
        }
        $totalCogs = (float) $cogsQuery->sum(DB::raw('sale_items.quantity * products.cost'));
            
        $totalExpenses = (float) (clone $expensesQuery)->sum('amount');
        $netProfit = $totalSales - $totalCogs - $totalExpenses;

        // 1.5 Debt Summaries
        $debtQuery = (clone $salesQuery)->where(function($q) {
            $q->where('payment_status', 'unpaid')->orWhere('payment_method', 'debt');
        });

        $totalOutstandingDebts = (float) (clone $debtQuery)->sum(DB::raw('total - amount_paid'));
        $debtorsCount = (clone $debtQuery)->count();
        $unpaidSales = (clone $debtQuery)->with('user', 'branch')->orderByDesc('created_at')->limit(5)->get();

        // 2. Alert lists
        $lowStockProducts = (clone $productsQuery)->whereColumn('quantity', '<=', 'low_stock_threshold')->orderBy('quantity')->get();

        // 3. Lists
        $recentSales = (clone $salesQuery)->with('user', 'branch')->orderByDesc('created_at')->limit(5)->get();
        $recentExpenses = (clone $expensesQuery)->with('user', 'branch')->orderByDesc('expense_date')->limit(5)->get();

        // 4. Statistics Groupings
        $topProductsQuery = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.tenant_id', $tenantId)
            ->whereNull('sales.deleted_at');
        if ($activeBranchId !== 'all') {
            $topProductsQuery->where('sales.branch_id', $activeBranchId);
        }
        $topProducts = $topProductsQuery->select('products.name', 'products.sku', DB::raw('SUM(sale_items.quantity) as total_qty'), DB::raw('SUM(sale_items.total) as total_revenue'))
            ->groupBy('sale_items.product_id', 'products.name', 'products.sku')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $topSellersQuery = DB::table('sales')
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->where('sales.tenant_id', $tenantId)
            ->whereNull('sales.deleted_at');
        if ($activeBranchId !== 'all') {
            $topSellersQuery->where('sales.branch_id', $activeBranchId);
        }
        $topSellers = $topSellersQuery->select('users.name', DB::raw('COUNT(sales.id) as sales_count'), DB::raw('SUM(sales.total) as total_revenue'))
            ->groupBy('sales.user_id', 'users.name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        // 5. Chart Metrics
        $salesTrendQuery = Sale::where('tenant_id', $tenantId)
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->where('created_at', '>=', now()->subDays(15));
        if ($activeBranchId !== 'all') {
            $salesTrendQuery->where('branch_id', $activeBranchId);
        }
        $salesTrend = $salesTrendQuery->groupBy('date')->orderBy('date')->get();

        $categorySalesQuery = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.tenant_id', $tenantId)
            ->whereNull('sales.deleted_at');
        if ($activeBranchId !== 'all') {
            $categorySalesQuery->where('sales.branch_id', $activeBranchId);
        }
        $categorySales = $categorySalesQuery->select('products.category', DB::raw('SUM(sale_items.total) as total'))
            ->groupBy('products.category')
            ->get();

        return view('dashboard', compact(
            'baseCurrency',
            'totalSales',
            'totalCogs',
            'totalExpenses',
            'netProfit',
            'totalOutstandingDebts',
            'debtorsCount',
            'unpaidSales',
            'lowStockProducts',
            'recentSales',
            'recentExpenses',
            'topProducts',
            'topSellers',
            'salesTrend',
            'categorySales'
        ));
    }

    public function settleDebt(Sale $sale)
    {
        $sale->update([
            'payment_status' => 'paid',
            'amount_paid' => $sale->total,
            'change_amount' => 0.00
        ]);

        return back()->with('success', 'Debt for Sale #' . $sale->id . ' marked as fully settled.');
    }

    public function generateMathChallenge()
    {
        $n1 = rand(3, 19);
        $n2 = rand(2, 15);
        $op = rand(0, 1) === 1 ? '+' : '*';
        $ans = ($op === '+') ? ($n1 + $n2) : ($n1 * $n2);
        $question = "{$n1} " . ($op === '+' ? '+' : '×') . " {$n2}";
        $token = Crypt::encryptString(json_encode([
            'ans' => $ans,
            'time' => time(),
        ]));

        return response()->json([
            'success' => true,
            'question' => $question,
            'token' => $token,
            'expected' => $ans,
        ]);
    }

    public function softDelete(Request $request, Sale $sale)
    {
        if (auth()->user()->role !== 'admin') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'error' => 'Permission denied. Only store administrators can delete transactions.'], 403);
            }
            return back()->withErrors(['error' => 'Permission denied. Only store administrators can delete transactions.']);
        }

        if ($sale->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Unauthorized tenant access.');
        }

        if ($sale->trashed()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'error' => 'Transaction #' . $sale->id . ' has already been soft-deleted.'], 422);
            }
            return back()->withErrors(['error' => 'Transaction #' . $sale->id . ' has already been soft-deleted.']);
        }

        $request->validate([
            'deletion_reason' => 'required|string|min:3|max:1000',
            'math_answer' => 'required|numeric',
            'math_challenge_token' => 'required|string',
        ], [
            'deletion_reason.required' => 'Please provide an audit reason explaining why this transaction is being deleted.',
            'deletion_reason.min' => 'The deletion reason must be at least 3 characters long.',
            'math_answer.required' => 'Math authentication is required to approve the deletion.',
        ]);

        // Math Authenticator validation
        try {
            $tokenData = json_decode(Crypt::decryptString($request->math_challenge_token), true);
            if (!isset($tokenData['ans']) || intval($request->math_answer) !== intval($tokenData['ans'])) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'error' => 'Math authentication failed: Incorrect calculation answer. Please solve correctly to approve.'], 422);
                }
                return back()->withErrors(['math_answer' => 'Math authentication failed: Incorrect calculation answer. Please solve correctly to approve.'])->withInput();
            }
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'error' => 'Invalid or expired math authentication challenge token.'], 422);
            }
            return back()->withErrors(['math_answer' => 'Invalid or expired math authentication challenge. Please try again.'])->withInput();
        }

        DB::beginTransaction();
        try {
            // Restore inventory stock for each item sold in this transaction
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $item->product->increment('quantity', $item->quantity);
                    InventoryLog::create([
                        'tenant_id' => $sale->tenant_id,
                        'branch_id' => $sale->branch_id,
                        'product_id' => $item->product_id,
                        'change_amount' => $item->quantity,
                        'type' => 'adjustment',
                        'description' => 'Stock restored: Sale #' . $sale->id . ' soft-deleted. Reason: ' . $request->deletion_reason,
                    ]);
                }
            }

            // Record audit reason and deleting admin user
            $sale->deletion_reason = $request->deletion_reason;
            $sale->deleted_by = auth()->id();
            $sale->save();

            // Soft-delete the sale record (preserves the row in DB with deleted_at timestamp)
            $sale->delete();

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaction #' . $sale->id . ' soft-deleted successfully. Record preserved in audit log.',
                    'sale_id' => $sale->id,
                ]);
            }

            return back()->with('success', 'Transaction #' . $sale->id . ' has been safely soft-deleted from active sales. The record is preserved in the database audit log with reason.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'error' => 'Failed to soft delete transaction: ' . $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => 'Failed to soft delete transaction: ' . $e->getMessage()]);
        }
    }

    public function restoreSale(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Only administrators can restore transactions.');
        }

        $sale = Sale::onlyTrashed()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            // Deduct stock again as the sale is being re-activated
            foreach ($sale->items as $item) {
                if ($item->product) {
                    if ($item->product->quantity < $item->quantity) {
                        throw new \Exception('Insufficient inventory to restore Sale #' . $sale->id . ' (' . $item->product->name . ' has ' . $item->product->quantity . ' available, needs ' . $item->quantity . ').');
                    }
                    $item->product->decrement('quantity', $item->quantity);
                    InventoryLog::create([
                        'tenant_id' => $sale->tenant_id,
                        'branch_id' => $sale->branch_id,
                        'product_id' => $item->product_id,
                        'change_amount' => -$item->quantity,
                        'type' => 'sale',
                        'description' => 'Stock re-allocated: Sale #' . $sale->id . ' restored from soft-delete.',
                    ]);
                }
            }

            $sale->restore();
            $sale->deletion_reason = null;
            $sale->deleted_by = null;
            $sale->save();

            DB::commit();

            return back()->with('success', 'Transaction #' . $sale->id . ' restored successfully to active sales.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to restore transaction: ' . $e->getMessage()]);
        }
    }


    public function reports(Request $request)
    {
        $sellers = User::orderBy('name')->get();
        $categories = Product::select('category')->distinct()->orderBy('category')->pluck('category');

        $startDate = null;
        $endDate = null;

        // Period filter logic
        if ($request->filled('day')) {
            $startDate = Carbon::parse($request->day)->startOfDay();
            $endDate = Carbon::parse($request->day)->endOfDay();
        } elseif ($request->filled('month')) {
            $monthParts = explode('-', $request->month);
            if (count($monthParts) === 2) {
                $startDate = Carbon::create($monthParts[0], $monthParts[1], 1)->startOfMonth();
                $endDate = Carbon::create($monthParts[0], $monthParts[1], 1)->endOfMonth();
            }
        } elseif ($request->filled('period') && $request->period !== 'all') {
            $period = $request->period;
            if ($period === 'daily') {
                $startDate = Carbon::today()->startOfDay();
                $endDate = Carbon::today()->endOfDay();
            } elseif ($period === 'weekly') {
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
            } elseif ($period === 'monthly') {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
            }
        }

        // 1. Sales query
        $transactionStatus = $request->get('transaction_status', 'active');
        if ($transactionStatus === 'deleted') {
            $salesQuery = Sale::onlyTrashed()->with('user', 'items.product', 'deletedByUser');
        } elseif ($transactionStatus === 'all') {
            $salesQuery = Sale::withTrashed()->with('user', 'items.product', 'deletedByUser');
        } else {
            $salesQuery = Sale::with('user', 'items.product', 'deletedByUser');
        }

        if ($startDate && $endDate) {
            $salesQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        if ($request->filled('seller_id')) {
            $salesQuery->where('user_id', $request->seller_id);
        }
        $sales = $salesQuery->orderByDesc('created_at')->get();

        // 2. Expenses query
        $expensesQuery = Expense::with('user');
        if ($startDate && $endDate) {
            $expensesQuery->whereBetween('expense_date', [$startDate, $endDate]);
        }
        if ($request->filled('seller_id')) {
            $expensesQuery->where('user_id', $request->seller_id);
        }
        $expenses = $expensesQuery->orderByDesc('expense_date')->get();

        // 3. Inventory Items Sold query
        $itemsQuery = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereNull('sales.deleted_at')
            ->select(
                'products.name as product_name',
                'products.sku as product_sku',
                'products.category as product_category',
                'products.cost as cost_price',
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.total) as total_revenue'),
                DB::raw('SUM(sale_items.quantity * products.cost) as total_cost')
            );
        if ($startDate && $endDate) {
            $itemsQuery->whereBetween('sales.created_at', [$startDate, $endDate]);
        }
        if ($request->filled('seller_id')) {
            $itemsQuery->where('sales.user_id', $request->seller_id);
        }
        if ($request->filled('category')) {
            $itemsQuery->where('products.category', $request->category);
        }
        $inventorySales = $itemsQuery->groupBy('sale_items.product_id', 'products.name', 'products.sku', 'products.category', 'products.cost')->orderByDesc('total_qty')->get();

        // 4. Balance Sheet values (cumulative as of now)
        $cashOnHand = (float) Sale::sum('total') - (float) Expense::sum('amount');
        $inventoryAssetValue = (float) Product::sum(DB::raw('quantity * cost'));
        $totalAssets = $cashOnHand + $inventoryAssetValue;
        $supplierLiabilities = (float) PurchaseOrder::where('status', 'pending')->sum('total_cost');
        $ownerEquity = $totalAssets - $supplierLiabilities;

        $balanceSheet = [
            'cash_on_hand' => $cashOnHand,
            'inventory_asset_value' => $inventoryAssetValue,
            'total_assets' => $totalAssets,
            'supplier_liabilities' => $supplierLiabilities,
            'owner_equity' => $ownerEquity
        ];

        // 5. Purchases Report
        $purchasesQuery = PurchaseOrder::query();
        if ($startDate && $endDate) {
            $purchasesQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $purchases = $purchasesQuery->orderByDesc('created_at')->get();

        // 6. Attendance Report
        $attendanceQuery = \App\Models\Attendance::with('user');
        if ($startDate && $endDate) {
            $attendanceQuery->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
        }
        if ($request->filled('seller_id')) {
            $attendanceQuery->where('user_id', $request->seller_id);
        }
        $attendances = $attendanceQuery->orderByDesc('date')->orderByDesc('check_in')->get();

        // 7. Payroll Report
        $payroll = [];
        $staff = User::orderBy('name')->get();
        foreach ($staff as $user) {
            if ($user->role === 'superadmin') continue;

            $baseSalary = 800; // Cashier base
            if ($user->role === 'manager') {
                $baseSalary = 1800;
            } elseif ($user->role === 'admin') {
                $baseSalary = 3000;
            }

            $userSalesSum = Sale::where('user_id', $user->id);
            if ($startDate && $endDate) {
                $userSalesSum->whereBetween('created_at', [$startDate, $endDate]);
            }
            $salesTotal = (float) $userSalesSum->sum('total');
            $commission = $salesTotal * 0.02;

            $lateQuery = \App\Models\Attendance::where('user_id', $user->id)->where('status', 'late');
            if ($startDate && $endDate) {
                $lateQuery->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
            }
            $lateCount = $lateQuery->count();
            $deductions = $lateCount * 10.00;

            $netSalary = $baseSalary + $commission - $deductions;

            $payroll[] = (object) [
                'user_id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'base_salary' => $baseSalary,
                'sales_count' => $userSalesSum->count(),
                'sales_volume' => $salesTotal,
                'commission' => $commission,
                'late_count' => $lateCount,
                'deductions' => $deductions,
                'net_salary' => $netSalary
            ];
        }

        // 8. Tax Report
        $taxQuery = Sale::query();
        if ($startDate && $endDate) {
            $taxQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $totalSalesTaxable = (float) $taxQuery->sum('subtotal');
        $totalTaxCollected = (float) $taxQuery->sum('tax');
        
        $taxReport = [
            'total_sales_taxable' => $totalSalesTaxable,
            'total_tax_collected' => $totalTaxCollected,
        ];

        // 9. Staff Sales Report
        $staffSales = DB::table('sales')
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->whereNull('sales.deleted_at')
            ->select(
                'users.id as user_id',
                'users.name as staff_name',
                'users.role as staff_role',
                DB::raw('COUNT(sales.id) as sales_count'),
                DB::raw('SUM(sales.total) as total_revenue')
            );
        if ($startDate && $endDate) {
            $staffSales->whereBetween('sales.created_at', [$startDate, $endDate]);
        }
        $staffSalesReport = $staffSales->groupBy('users.id', 'sales.user_id', 'users.name', 'users.role')->orderByDesc('total_revenue')->get();

        // 10. Inventory Report
        $inventoryQuery = Product::query();
        if ($request->filled('category')) {
            $inventoryQuery->where('category', $request->category);
        }
        $inventory = $inventoryQuery->orderBy('name')->get();

        // Fetch all items for the legacy transaction list view (still filtering by category and seller/period)
        $legacyQuery = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->whereNull('sales.deleted_at')
            ->select(
                'sales.id as sale_id',
                'sales.created_at as sale_date',
                'users.name as seller_name',
                'sales.customer_name',
                'products.name as product_name',
                'products.category as product_category',
                'products.sku as product_sku',
                'sale_items.quantity',
                'sale_items.price as selling_price',
                'products.cost as cost_price',
                'sale_items.total as item_total',
                'sales.payment_method',
                'sales.currency_code'
            );

        if ($startDate && $endDate) {
            $legacyQuery->whereBetween('sales.created_at', [$startDate, $endDate]);
        }
        if ($request->filled('category')) {
            $legacyQuery->where('products.category', $request->category);
        }
        if ($request->filled('seller_id')) {
            $legacyQuery->where('sales.user_id', $request->seller_id);
        }

        $reportItems = $legacyQuery->orderByDesc('sales.created_at')->get();

        $debtorsQuery = Sale::with('user')
            ->where(function($q) {
                $q->where('payment_status', 'unpaid')->orWhere('payment_method', 'debt');
            });
        if ($startDate && $endDate) {
            $debtorsQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $debtorsSales = $debtorsQuery->orderByDesc('created_at')->get();
        $baseCurrency = Currency::activeForTenant();

        // 11. Trading, Profit & Loss Account (TPLA)
        $grossSalesRevenue = (float) $sales->sum('subtotal');
        $cogsTotal = (float) $inventorySales->sum('total_cost');
        $grossProfit = $grossSalesRevenue - $cogsTotal;
        $totalOperatingExpenses = (float) $expenses->sum('amount');
        $taxCollectedTotal = (float) $sales->sum('tax');
        $netProfitBeforeTax = $grossProfit - $totalOperatingExpenses;
        $netProfitAfterTax = $netProfitBeforeTax - $taxCollectedTotal;

        $tplaReport = [
            'gross_sales_revenue' => $grossSalesRevenue,
            'cogs_total' => $cogsTotal,
            'gross_profit' => $grossProfit,
            'total_operating_expenses' => $totalOperatingExpenses,
            'tax_collected_total' => $taxCollectedTotal,
            'net_profit_before_tax' => $netProfitBeforeTax,
            'net_profit_after_tax' => $netProfitAfterTax,
        ];

        // 12. Balance Sheet Statement
        $totalSalesCashPaid = (float) Sale::sum('amount_paid');
        $totalExpensesOutflow = (float) Expense::sum('amount');
        $totalVendorPOPaid = (float) PurchaseOrder::sum('amount_paid');
        $cashOnHand = max(0, $totalSalesCashPaid - $totalExpensesOutflow - $totalVendorPOPaid);

        $customerDebtsReceivable = (float) Sale::where(function($q) {
                $q->where('payment_status', 'unpaid')->orWhere('payment_method', 'debt');
            })->selectRaw('SUM(total - amount_paid) as tot_debt')->value('tot_debt') ?: 0.00;

        $inventoryValuationCost = (float) Product::selectRaw('SUM(quantity * cost) as tot_cost')->value('tot_cost') ?: 0.00;
        $totalCurrentAssets = $cashOnHand + $customerDebtsReceivable + $inventoryValuationCost;

        $accountsPayableSuppliers = (float) PurchaseOrder::selectRaw('SUM(total_cost - amount_paid) as tot_ap')->value('tot_ap') ?: 0.00;
        $taxPayableGovernment = (float) Sale::sum('tax');
        $totalCurrentLiabilities = $accountsPayableSuppliers + $taxPayableGovernment;

        $ownersEquityRetainedEarnings = $totalCurrentAssets - $totalCurrentLiabilities;
        $totalLiabilitiesAndEquity = $totalCurrentLiabilities + $ownersEquityRetainedEarnings;

        $formalBalanceSheet = [
            'cash_on_hand' => $cashOnHand,
            'customer_debts_receivable' => $customerDebtsReceivable,
            'inventory_valuation_cost' => $inventoryValuationCost,
            'total_current_assets' => $totalCurrentAssets,
            'accounts_payable_suppliers' => $accountsPayableSuppliers,
            'tax_payable_government' => $taxPayableGovernment,
            'total_current_liabilities' => $totalCurrentLiabilities,
            'owners_equity' => $ownersEquityRetainedEarnings,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
        ];

        return view('reports', compact(
            'baseCurrency', 'reportItems', 'sellers', 'categories', 'sales', 'expenses', 
            'inventorySales', 'balanceSheet', 'purchases', 'attendances', 
            'payroll', 'taxReport', 'staffSalesReport', 'inventory', 'debtorsSales',
            'tplaReport', 'formalBalanceSheet'
        ));
    }

    public function exportBalanceSheet(Request $request)
    {
        $cashOnHand = (float) Sale::sum('total') - (float) Expense::sum('amount');
        $inventoryAssetValue = (float) Product::sum(DB::raw('quantity * cost'));
        $totalAssets = $cashOnHand + $inventoryAssetValue;
        $supplierLiabilities = (float) PurchaseOrder::where('status', 'pending')->sum('total_cost');
        $ownerEquity = $totalAssets - $supplierLiabilities;

        $filename = "Luxeshop_Balance_Sheet_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($cashOnHand, $inventoryAssetValue, $totalAssets, $supplierLiabilities, $ownerEquity) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Luxeshop Retail Store - Balance Sheet']);
            fputcsv($file, ['Generated At', date('Y-m-d H:i:s')]);
            fputcsv($file, []);
            
            fputcsv($file, ['ASSETS', 'Amount (USD)']);
            fputcsv($file, ['Cash on Hand (Sales minus Expenses)', number_format($cashOnHand, 2)]);
            fputcsv($file, ['Inventory Valuation (Products cost in warehouse)', number_format($inventoryAssetValue, 2)]);
            fputcsv($file, ['TOTAL ASSETS', number_format($totalAssets, 2)]);
            fputcsv($file, []);

            fputcsv($file, ['LIABILITIES & OWNER\'S EQUITY', 'Amount (USD)']);
            fputcsv($file, ['Supplier Liabilities (Pending Purchase Orders)', number_format($supplierLiabilities, 2)]);
            fputcsv($file, ['Owner\'s Equity (Net Worth / Assets minus Liabilities)', number_format($ownerEquity, 2)]);
            fputcsv($file, ['TOTAL LIABILITIES & EQUITY', number_format($totalAssets, 2)]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportCSV(Request $request)
    {
        $reportType = $request->query('report_type', 'sales_report');

        $startDate = null;
        $endDate = null;

        // Period filter logic
        if ($request->filled('day')) {
            $startDate = Carbon::parse($request->day)->startOfDay();
            $endDate = Carbon::parse($request->day)->endOfDay();
        } elseif ($request->filled('month')) {
            $monthParts = explode('-', $request->month);
            if (count($monthParts) === 2) {
                $startDate = Carbon::create($monthParts[0], $monthParts[1], 1)->startOfMonth();
                $endDate = Carbon::create($monthParts[0], $monthParts[1], 1)->endOfMonth();
            }
        } elseif ($request->filled('period') && $request->period !== 'all') {
            $period = $request->period;
            if ($period === 'daily') {
                $startDate = Carbon::today()->startOfDay();
                $endDate = Carbon::today()->endOfDay();
            } elseif ($period === 'weekly') {
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
            } elseif ($period === 'monthly') {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
            }
        }

        $filename = "Luxeshop_" . ucfirst($reportType) . "_" . date('Ymd_His') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($reportType, $startDate, $endDate, $request) {
            $file = fopen('php://output', 'w');

            if ($reportType === 'sales_report') {
                fputcsv($file, ['Sale ID', 'Date & Time', 'Cashier', 'Customer Name', 'Payment Method', 'Total Amount (USD)']);
                
                $query = Sale::with('user');
                if ($startDate && $endDate) $query->whereBetween('created_at', [$startDate, $endDate]);
                if ($request->filled('seller_id')) $query->where('user_id', $request->seller_id);
                
                foreach ($query->orderByDesc('created_at')->get() as $row) {
                    fputcsv($file, [
                        '#' . $row->id,
                        $row->created_at->format('Y-m-d H:i'),
                        $row->user->name ?? 'System',
                        $row->customer_name ?: 'Walk-in Customer',
                        strtoupper($row->payment_method),
                        number_format($row->total, 2)
                    ]);
                }
            } elseif ($reportType === 'item_wise_sales') {
                fputcsv($file, ['Product Item', 'SKU / Barcode', 'Category', 'Pieces Sold', 'Supplier Cost (COGS)', 'Gross Revenue', 'Gross Profit']);
                
                $query = SaleItem::query()
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->whereNull('sales.deleted_at')
                    ->select(
                        'products.name as product_name',
                        'products.sku as product_sku',
                        'products.category as product_category',
                        DB::raw('SUM(sale_items.quantity) as total_qty'),
                        DB::raw('SUM(sale_items.total) as total_revenue'),
                        DB::raw('SUM(sale_items.quantity * products.cost) as total_cost')
                    );
                if ($startDate && $endDate) $query->whereBetween('sales.created_at', [$startDate, $endDate]);
                if ($request->filled('seller_id')) $query->where('sales.user_id', $request->seller_id);
                if ($request->filled('category')) $query->where('products.category', $request->category);
                
                $items = $query->groupBy('sale_items.product_id', 'products.name', 'products.sku', 'products.category', 'products.cost')->orderByDesc('total_qty')->get();
                foreach ($items as $row) {
                    fputcsv($file, [
                        $row->product_name,
                        $row->product_sku,
                        $row->product_category,
                        $row->total_qty . ' pieces',
                        number_format($row->total_cost, 2),
                        number_format($row->total_revenue, 2),
                        number_format($row->total_revenue - $row->total_cost, 2)
                    ]);
                }
            } elseif ($reportType === 'profit_loss') {
                $grossSales = (float) Sale::when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))->sum('total');
                $cogsVal = (float) SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->whereNull('sales.deleted_at')
                    ->when($startDate && $endDate, fn($q) => $q->whereBetween('sales.created_at', [$startDate, $endDate]))
                    ->sum(DB::raw('sale_items.quantity * products.cost'));
                $grossProfit = $grossSales - $cogsVal;
                $operatingExpenses = (float) Expense::when($startDate && $endDate, fn($q) => $q->whereBetween('expense_date', [$startDate, $endDate]))->sum('amount');
                $netPAndL = $grossProfit - $operatingExpenses;

                fputcsv($file, ['Statement of Profit & Loss (P&L)']);
                fputcsv($file, ['Generated At', date('Y-m-d H:i:s')]);
                fputcsv($file, []);
                fputcsv($file, ['Metric', 'Amount (USD)']);
                fputcsv($file, ['Gross Sales Revenue', number_format($grossSales, 2)]);
                fputcsv($file, ['Cost of Goods Sold (COGS)', '-' . number_format($cogsVal, 2)]);
                fputcsv($file, ['Gross Operating Profit', number_format($grossProfit, 2)]);
                fputcsv($file, ['Operating Expenses', '-' . number_format($operatingExpenses, 2)]);
                fputcsv($file, ['Net Profit / Loss', number_format($netPAndL, 2)]);
            } elseif ($reportType === 'expense_report') {
                fputcsv($file, ['Expense Date', 'Logged By', 'Category', 'Description', 'Amount (USD)']);
                
                $query = Expense::with('user');
                if ($startDate && $endDate) $query->whereBetween('expense_date', [$startDate, $endDate]);
                if ($request->filled('seller_id')) $query->where('user_id', $request->seller_id);
                
                foreach ($query->orderByDesc('expense_date')->get() as $row) {
                    fputcsv($file, [
                        $row->expense_date,
                        $row->user->name ?? 'System',
                        $row->category,
                        $row->description,
                        number_format($row->amount, 2)
                    ]);
                }
            } elseif ($reportType === 'purchases_report') {
                fputcsv($file, ['PO Number', 'Created Date', 'Supplier', 'Status', 'Total Cost (USD)']);
                
                $query = PurchaseOrder::query();
                if ($startDate && $endDate) $query->whereBetween('created_at', [$startDate, $endDate]);
                
                foreach ($query->orderByDesc('created_at')->get() as $row) {
                    fputcsv($file, [
                        $row->po_number,
                        $row->created_at->format('Y-m-d'),
                        $row->supplier_name,
                        strtoupper($row->status),
                        number_format($row->total_cost, 2)
                    ]);
                }
            } elseif ($reportType === 'attendance_report') {
                fputcsv($file, ['Date', 'Staff Member', 'Check In Time', 'Check Out Time', 'Shift Status']);
                
                $query = \App\Models\Attendance::with('user');
                if ($startDate && $endDate) $query->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
                if ($request->filled('seller_id')) $query->where('user_id', $request->seller_id);
                
                foreach ($query->orderByDesc('date')->orderByDesc('check_in')->get() as $row) {
                    fputcsv($file, [
                        $row->date,
                        $row->user->name,
                        $row->check_in,
                        $row->check_out ?: '-',
                        strtoupper($row->status)
                    ]);
                }
            } elseif ($reportType === 'payroll_report') {
                fputcsv($file, ['Employee Name', 'Role', 'Base Salary (USD)', 'Sales Count', 'Sales Volume (USD)', 'Commission (2%)', 'Late Checkins', 'Penalty Deductions', 'Net Pay (USD)']);
                
                $staff = User::orderBy('name')->get();
                foreach ($staff as $user) {
                    if ($user->role === 'superadmin') continue;

                    $baseSalary = $user->role === 'manager' ? 1800 : ($user->role === 'admin' ? 3000 : 800);
                    $userSalesSum = Sale::where('user_id', $user->id);
                    if ($startDate && $endDate) $userSalesSum->whereBetween('created_at', [$startDate, $endDate]);
                    $salesTotal = (float) $userSalesSum->sum('total');
                    $commission = $salesTotal * 0.02;

                    $lateQuery = \App\Models\Attendance::where('user_id', $user->id)->where('status', 'late');
                    if ($startDate && $endDate) $lateQuery->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
                    $lateCount = $lateQuery->count();
                    $deductions = $lateCount * 10.00;
                    $netSalary = $baseSalary + $commission - $deductions;

                    fputcsv($file, [
                        $user->name,
                        strtoupper($user->role),
                        number_format($baseSalary, 2),
                        $userSalesSum->count(),
                        number_format($salesTotal, 2),
                        number_format($commission, 2),
                        $lateCount,
                        number_format($deductions, 2),
                        number_format($netSalary, 2)
                    ]);
                }
            } elseif ($reportType === 'tax_report') {
                $taxQuery = Sale::query();
                if ($startDate && $endDate) $taxQuery->whereBetween('created_at', [$startDate, $endDate]);
                $subtotal = $taxQuery->sum('subtotal');
                $tax = $taxQuery->sum('tax');

                fputcsv($file, ['Sales Tax Collection Report']);
                fputcsv($file, []);
                fputcsv($file, ['Metric', 'Amount (USD)']);
                fputcsv($file, ['Total Taxable Sales Subtotal', number_format($subtotal, 2)]);
                fputcsv($file, ['General Tax Rate', '15.0%']);
                fputcsv($file, ['Total Sales Tax Collected', number_format($tax, 2)]);
            } elseif ($reportType === 'staff_sales') {
                fputcsv($file, ['Staff Name', 'Role', 'Receipts Handled', 'Sales Volume (USD)', 'Commission Earned']);
                
                $query = DB::table('sales')
                    ->join('users', 'sales.user_id', '=', 'users.id')
                    ->whereNull('sales.deleted_at')
                    ->select(
                        'users.name as staff_name',
                        'users.role as staff_role',
                        DB::raw('COUNT(sales.id) as sales_count'),
                        DB::raw('SUM(sales.total) as total_revenue')
                    );
                if ($startDate && $endDate) $query->whereBetween('sales.created_at', [$startDate, $endDate]);
                $staffList = $query->groupBy('users.id', 'sales.user_id', 'users.name', 'users.role')->orderByDesc('total_revenue')->get();
                
                foreach ($staffList as $row) {
                    fputcsv($file, [
                        $row->staff_name,
                        strtoupper($row->staff_role),
                        $row->sales_count,
                        number_format($row->total_revenue, 2),
                        number_format($row->total_revenue * 0.02, 2)
                    ]);
                }
            } elseif ($reportType === 'inventory_report') {
                fputcsv($file, ['Product Item', 'SKU / Barcode', 'Category', 'Remaining Quantity', 'Cost Price', 'Selling Price', 'Cost Valuation', 'Retail Valuation', 'Status']);
                
                $query = Product::query();
                if ($request->filled('category')) $query->where('category', $request->category);
                
                foreach ($query->orderBy('name')->get() as $row) {
                    $costVal = $row->quantity * $row->cost;
                    $priceVal = $row->quantity * $row->price;
                    $status = $row->quantity <= 0 ? 'OUT OF STOCK' : ($row->quantity <= $row->low_stock_threshold ? 'LOW STOCK' : 'IN STOCK');
                    
                    fputcsv($file, [
                        $row->name,
                        $row->sku,
                        $row->category,
                        $row->quantity . ' units',
                        number_format($row->cost, 2),
                        number_format($row->price, 2),
                        number_format($costVal, 2),
                        number_format($priceVal, 2),
                        $status
                    ]);
                }
            } elseif ($reportType === 'debtors_report') {
                fputcsv($file, ['Sale ID', 'Date & Time', 'Debtor Name', 'Customer Contact Phone', 'Cashier', 'Order Total', 'Amount Paid', 'Outstanding Debt Balance', 'Status']);
                
                $query = Sale::with('user')->where(function($q) {
                    $q->where('payment_status', 'unpaid')->orWhere('payment_method', 'debt');
                });
                if ($startDate && $endDate) $query->whereBetween('created_at', [$startDate, $endDate]);
                
                foreach ($query->orderByDesc('created_at')->get() as $row) {
                    $debtBalance = $row->total - $row->amount_paid;
                    fputcsv($file, [
                        '#' . $row->id,
                        $row->created_at->format('Y-m-d H:i'),
                        $row->customer_name ?: 'Unknown Debtor',
                        $row->customer_phone ?: 'N/A',
                        $row->user->name ?? 'System',
                        number_format($row->total, 2),
                        number_format($row->amount_paid, 2),
                        number_format($debtBalance, 2),
                        strtoupper($row->payment_status)
                    ]);
                }
            } elseif ($reportType === 'tpla_report') {
                $grossSales = (float) Sale::when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))->sum('subtotal');
                $cogsVal = (float) SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->whereNull('sales.deleted_at')
                    ->when($startDate && $endDate, fn($q) => $q->whereBetween('sales.created_at', [$startDate, $endDate]))
                    ->sum(DB::raw('sale_items.quantity * products.cost'));
                $grossProfit = $grossSales - $cogsVal;
                $operatingExpenses = (float) Expense::when($startDate && $endDate, fn($q) => $q->whereBetween('expense_date', [$startDate, $endDate]))->sum('amount');
                $taxCollected = (float) Sale::when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))->sum('tax');
                $netBeforeTax = $grossProfit - $operatingExpenses;
                $netAfterTax = $netBeforeTax - $taxCollected;

                fputcsv($file, ['Trading, Profit & Loss Account (TPLA)']);
                fputcsv($file, ['Generated At', date('Y-m-d H:i:s')]);
                fputcsv($file, []);
                fputcsv($file, ['ACCOUNTING METRIC', 'AMOUNT']);
                fputcsv($file, ['Gross Sales Revenue (Subtotal)', number_format($grossSales, 2)]);
                fputcsv($file, ['Less: Cost of Goods Sold (COGS)', '-' . number_format($cogsVal, 2)]);
                fputcsv($file, ['GROSS TRADING PROFIT', number_format($grossProfit, 2)]);
                fputcsv($file, ['Less: Store Operating Expenses', '-' . number_format($operatingExpenses, 2)]);
                fputcsv($file, ['NET OPERATING PROFIT BEFORE TAX', number_format($netBeforeTax, 2)]);
                fputcsv($file, ['Less: Sales Tax Collected / Liability', '-' . number_format($taxCollected, 2)]);
                fputcsv($file, ['NET PROFIT AFTER TAX (RETAINED)', number_format($netAfterTax, 2)]);
            } elseif ($reportType === 'balance_sheet_report' || $reportType === 'balance_sheet') {
                $totalSalesCashPaid = (float) Sale::sum('amount_paid');
                $totalExpensesOutflow = (float) Expense::sum('amount');
                $totalVendorPOPaid = (float) PurchaseOrder::sum('amount_paid');
                $cashOnHand = max(0, $totalSalesCashPaid - $totalExpensesOutflow - $totalVendorPOPaid);

                $customerDebts = (float) Sale::where(function($q) {
                        $q->where('payment_status', 'unpaid')->orWhere('payment_method', 'debt');
                    })->selectRaw('SUM(total - amount_paid) as tot_debt')->value('tot_debt') ?: 0.00;

                $invValuation = (float) Product::selectRaw('SUM(quantity * cost) as tot_cost')->value('tot_cost') ?: 0.00;
                $totalAssets = $cashOnHand + $customerDebts + $invValuation;

                $supplierPayable = (float) PurchaseOrder::selectRaw('SUM(total_cost - amount_paid) as tot_ap')->value('tot_ap') ?: 0.00;
                $taxPayable = (float) Sale::sum('tax');
                $totalLiabilities = $supplierPayable + $taxPayable;
                $ownersEquity = $totalAssets - $totalLiabilities;

                fputcsv($file, ['Corporate Balance Sheet Statement']);
                fputcsv($file, ['Generated At', date('Y-m-d H:i:s')]);
                fputcsv($file, []);
                fputcsv($file, ['CURRENT ASSETS', 'AMOUNT']);
                fputcsv($file, ['Cash & Cash Equivalents', number_format($cashOnHand, 2)]);
                fputcsv($file, ['Customer Accounts Receivable (Debts)', number_format($customerDebts, 2)]);
                fputcsv($file, ['Inventory Asset Valuation (Warehouse)', number_format($invValuation, 2)]);
                fputcsv($file, ['TOTAL CURRENT ASSETS', number_format($totalAssets, 2)]);
                fputcsv($file, []);
                fputcsv($file, ['LIABILITIES & OWNER EQUITY', 'AMOUNT']);
                fputcsv($file, ['Accounts Payable (Supplier POs)', number_format($supplierPayable, 2)]);
                fputcsv($file, ['Sales Tax Payable (Government Liability)', number_format($taxPayable, 2)]);
                fputcsv($file, ['Total Liabilities', number_format($totalLiabilities, 2)]);
                fputcsv($file, ['Owner Equity & Retained Capital', number_format($ownersEquity, 2)]);
                fputcsv($file, ['TOTAL LIABILITIES & EQUITY', number_format($totalAssets, 2)]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function cashierDashboard()
    {
        $baseCurrency = Currency::activeForTenant();
        $today = Carbon::today();

        // 1. Today's Total Store Metrics
        $todayStoreSales = (float) Sale::whereDate('created_at', $today)->sum('total');
        $todayStoreTransactions = Sale::whereDate('created_at', $today)->count();

        // 2. Cashier's Personal Today Metrics
        $user = auth()->user();
        $todayCashierSales = (float) Sale::where('user_id', $user->id)->whereDate('created_at', $today)->sum('total');
        $todayCashierTransactions = Sale::where('user_id', $user->id)->whereDate('created_at', $today)->count();

        // 3. Payment Methods Breakdown Today
        $paymentBreakdown = Sale::whereDate('created_at', $today)
            ->select('payment_method', DB::raw('SUM(total) as total_amount'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        // 4. Best Selling Products Today
        $bestSellingToday = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereDate('sales.created_at', $today)
            ->whereNull('sales.deleted_at')
            ->select('products.name', 'products.sku', 'products.price', DB::raw('SUM(sale_items.quantity) as total_qty'), DB::raw('SUM(sale_items.total) as total_revenue'))
            ->groupBy('sale_items.product_id', 'products.name', 'products.sku', 'products.price')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Fallback to top products overall if no sales today yet
        if ($bestSellingToday->isEmpty()) {
            $bestSellingToday = DB::table('sale_items')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->select('products.name', 'products.sku', 'products.price', DB::raw('SUM(sale_items.quantity) as total_qty'), DB::raw('SUM(sale_items.total) as total_revenue'))
                ->groupBy('sale_items.product_id', 'products.name', 'products.sku', 'products.price')
                ->orderByDesc('total_qty')
                ->limit(5)
                ->get();
        }

        // 5. Recent Sales by Current Cashier Today
        $recentCashierSales = Sale::with('items.product')
            ->where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('cashier.dashboard', compact(
            'baseCurrency',
            'todayStoreSales',
            'todayStoreTransactions',
            'todayCashierSales',
            'todayCashierTransactions',
            'paymentBreakdown',
            'bestSellingToday',
            'recentCashierSales'
        ));
    }
}
