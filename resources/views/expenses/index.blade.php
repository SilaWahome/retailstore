@extends('layouts.app')

@section('title', 'Daily Expenses - Retail Store POS')

@section('content')
<div class="page-header">
    <h1 class="page-title">Daily Expense Tracking</h1>
    <div class="page-actions">
        <a href="#log-expense-form" class="btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
            <span>💸</span> Log New Expense
        </a>
    </div>
</div>

<div class="grid-2" style="margin-bottom: 2rem;">
    <div class="glass-panel panel-body" style="display: flex; align-items: center; gap: 1rem; border-left: 5px solid var(--accent-coral);">
        <span style="font-size: 2.5rem; color: var(--accent-coral);">📉</span>
        <div>
            <h3 style="font-size: 1.5rem; font-weight: 700; color: #fff;">
                {{ number_format($todayTotal, 2) }}
            </h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Today's Total Expenses</p>
        </div>
    </div>
    <div class="glass-panel panel-body" style="display: flex; align-items: center; gap: 1rem; border-left: 5px solid var(--accent-amber);">
        <span style="font-size: 2.5rem; color: var(--accent-amber);">📅</span>
        <div>
            <h3 style="font-size: 1.5rem; font-weight: 700; color: #fff;">
                {{ number_format($monthTotal, 2) }}
            </h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">This Month's Total Expenses</p>
        </div>
    </div>
</div>

<div class="glass-panel" style="margin-bottom: 2rem; overflow: hidden;">
    <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
        <h2 style="font-size: 1.25rem; font-weight: 700;">Expense History</h2>
    </div>
    <div class="custom-table-container" style="overflow-x: auto;">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Branch</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Logged By</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                    <tr>
                        <td>{{ $expense->expense_date->format('Y-m-d') }}</td>
                        <td>
                            @if($expense->branch)
                                <span style="font-size: 0.8rem; font-weight: 600; background: rgba(139, 92, 246, 0.1); color: var(--primary); padding: 0.2rem 0.5rem; border-radius: 4px;">
                                    📍 {{ $expense->branch->name }}
                                </span>
                            @else
                                <span style="font-size: 0.8rem; color: var(--text-muted); font-style: italic;">All Branches</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--card-border);">
                                {{ strtoupper($expense->category) }}
                            </span>
                        </td>
                        <td>{{ $expense->description ?: 'N/A' }}</td>
                        <td>{{ $expense->user->name ?? 'System' }}</td>
                        <td style="font-weight: 600; color: #fca5a5;">
                            {{ number_format($expense->amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            No expenses logged yet. Add your first expense below!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="glass-panel" id="log-expense-form" style="max-width: 600px; margin: 0 auto 3rem auto;">
    <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
        <h2 style="font-size: 1.25rem; font-weight: 700;">Log Store Expense</h2>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Record payments for utilities, rent, supplies, or other business expenses.</p>
    </div>
    <form action="{{ route('expenses.store') }}" method="POST" class="panel-body" style="display: flex; flex-direction: column; gap: 1.25rem;">
        @csrf

        <div class="form-group">
            <label class="form-label" for="branch_id">Target Branch</label>
            <select class="form-input" id="branch_id" name="branch_id" style="padding: 0.75rem; font-size: 0.95rem;">
                <option value="">🏢 Default / Active Branch Context</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">📍 {{ $branch->name }} ({{ $branch->code ?? 'MAIN' }})</option>
                @endforeach
            </select>
        </div>
        
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="amount">Amount *</label>
                <input class="form-input" type="number" step="0.01" id="amount" name="amount" required placeholder="0.00">
            </div>
            <div class="form-group">
                <label class="form-label" for="expense_date">Date *</label>
                <input class="form-input" type="date" id="expense_date" name="expense_date" required value="{{ date('Y-m-d') }}">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="category">Category *</label>
            <select class="form-input" id="category" name="category" required style="padding: 0.75rem; font-size: 0.95rem;">
                <option value="utilities">Utilities (Electricity, Water, Internet)</option>
                <option value="rent">Rent / Property Lease</option>
                <option value="supplies">Store Supplies & Cleaning</option>
                <option value="salaries">Salaries & Wages</option>
                <option value="marketing">Marketing & Ads</option>
                <option value="other">Other Miscellaneous</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description / Notes</label>
            <input class="form-input" type="text" id="description" name="description" placeholder="e.g. Paid monthly internet bill">
        </div>

        <button type="submit" class="btn-primary" style="margin-top: 0.5rem; align-self: flex-start; width: auto; padding: 0.85rem 2rem;">Log Expense</button>
    </form>
</div>
@endsection
