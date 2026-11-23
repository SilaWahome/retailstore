<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Branch;
use App\Models\Currency;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $activeBranchId = session('active_branch_id', 'all');

        $query = Expense::with('user', 'branch')->orderByDesc('expense_date')->orderByDesc('created_at');
        if ($activeBranchId !== 'all') {
            $query->where('branch_id', $activeBranchId);
        }
        $expenses = $query->get();

        $baseCurrency = Currency::activeForTenant($tenantId);
        
        $todayQuery = Expense::whereDate('expense_date', Carbon::today());
        if ($activeBranchId !== 'all') {
            $todayQuery->where('branch_id', $activeBranchId);
        }
        $todayTotal = $todayQuery->sum('amount');
        
        $monthQuery = Expense::whereMonth('expense_date', Carbon::now()->month)
            ->whereYear('expense_date', Carbon::now()->year);
        if ($activeBranchId !== 'all') {
            $monthQuery->where('branch_id', $activeBranchId);
        }
        $monthTotal = $monthQuery->sum('amount');

        $branches = Branch::where('tenant_id', $tenantId)->where('is_active', true)->get();

        return view('expenses.index', compact('expenses', 'todayTotal', 'monthTotal', 'baseCurrency', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string|max:191',
            'expense_date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $activeBranchId = session('active_branch_id');
        $branchId = $request->filled('branch_id') 
            ? $request->branch_id 
            : (($activeBranchId && $activeBranchId !== 'all') ? $activeBranchId : auth()->user()->branch_id);

        Expense::create([
            'tenant_id' => auth()->user()->tenant_id,
            'branch_id' => $branchId,
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'category' => $request->category,
            'description' => $request->description,
            'expense_date' => $request->expense_date,
        ]);

        return redirect()->route('expenses.index')->with('success', 'Expense recorded successfully!');
    }
}
