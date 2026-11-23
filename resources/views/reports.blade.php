@extends('layouts.app')

@section('title', 'Reports - Retail POS')

@section('content')
<style>
    .report-menu-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.85rem 1.25rem;
        border-radius: var(--radius-sm);
        color: var(--text-muted);
        text-decoration: none;
        font-weight: 500;
        font-size: 0.9rem;
        transition: var(--transition-smooth);
        border: 1px solid transparent;
        background: transparent;
        width: 100%;
        text-align: left;
        cursor: pointer;
    }
    .report-menu-item:hover {
        color: var(--text-main);
        background: var(--menu-hover-bg);
    }
    .report-menu-item.active {
        color: #fff !important;
        background: var(--primary);
        box-shadow: 0 4px 12px var(--primary-glow);
    }
    .report-console-container {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 1.5rem;
        align-items: start;
    }
    @media (max-width: 992px) {
        .report-console-container {
            grid-template-columns: 1fr;
        }
    }
    .p-and-l-row {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--card-border);
        font-size: 0.95rem;
    }
</style>

<div class="page-header">
    <h1 class="page-title">Reports & Statements Console</h1>
    <div class="page-actions">
        <!-- Dynamic link that copies all current filters to CSV route -->
        <a id="export-btn" href="{{ route('reports.export', request()->all()) }}" class="btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; background: var(--accent-emerald); box-shadow: 0 4px 14px var(--accent-emerald-glow);">
            Export Active Report (Excel)
        </a>
    </div>
</div>

<!-- Filters Panel -->
<div class="glass-panel panel-body" style="margin-bottom: 2rem; padding: 1.5rem;">
    <h3 style="font-size: 1rem; font-weight: 600; color: var(--text-main); margin-bottom: 1rem;">Console Period & Parameters Filters</h3>
    <form action="{{ route('reports.index') }}" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto; gap: 1rem; align-items: flex-end;">
        @if(request('period'))
            <input type="hidden" name="period" value="{{ request('period') }}">
        @endif
        @if(request('report_type'))
            <input type="hidden" name="report_type" id="report_type_input" value="{{ request('report_type') }}">
        @endif

        <!-- Filter by Month -->
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" for="month">Select Month (Calendar)</label>
            <input type="month" name="month" id="month" class="form-input" value="{{ request('month') }}" onclick="this.showPicker()" style="padding: 0.6rem;">
        </div>

        <!-- Filter by Day -->
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" for="day">Select Date (Calendar)</label>
            <input type="date" name="day" id="day" class="form-input" value="{{ request('day') }}" onclick="this.showPicker()" style="padding: 0.6rem;">
        </div>

        <!-- Filter by Category -->
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" for="category">Category</label>
            <select name="category" id="category" class="form-input" style="padding: 0.6rem; font-size: 0.9rem;">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>

        <!-- Filter by Seller (User) -->
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" for="seller_id">Seller / Cashier</label>
            <select name="seller_id" id="seller_id" class="form-input" style="padding: 0.6rem; font-size: 0.9rem;">
                <option value="">All Cashiers</option>
                @foreach($sellers as $seller)
                    <option value="{{ $seller->id }}" {{ request('seller_id') == $seller->id ? 'selected' : '' }}>
                        {{ $seller->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Filter by Transaction Status -->
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" for="transaction_status">Transaction Status</label>
            <select name="transaction_status" id="transaction_status" class="form-input" style="padding: 0.6rem; font-size: 0.9rem;">
                <option value="active" {{ request('transaction_status', 'active') === 'active' ? 'selected' : '' }}>Active Only</option>
                <option value="all" {{ request('transaction_status') === 'all' ? 'selected' : '' }}>All (Incl. Voided)</option>
                <option value="deleted" {{ request('transaction_status') === 'deleted' ? 'selected' : '' }}>Soft-Deleted Only</option>
            </select>
        </div>

        <!-- Filter Action Buttons -->
        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
            <button type="submit" class="btn-primary" style="padding: 0.65rem 1.25rem; font-size: 0.9rem; width: auto; margin-top: 0; background: var(--primary); border: none;">Apply Filter</button>
            <a href="{{ route('reports.index', ['report_type' => request('report_type')]) }}" class="btn-secondary" style="padding: 0.65rem 1.25rem; font-size: 0.9rem; text-decoration: none; text-align: center;">Reset</a>
        </div>
    </form>
</div>

<!-- Main Report Console Layout -->
<div class="report-console-container">
    <!-- Left column: Reports Sidebar -->
    <div class="glass-panel panel-body" style="display: flex; flex-direction: column; gap: 0.35rem; padding: 1rem;">
        <h4 style="font-size: 0.85rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem; padding-left: 0.5rem;">Report Directories</h4>
        
        <button type="button" id="menu-sales_report" class="report-menu-item" onclick="switchReport('sales_report')">
            Sales Report
        </button>
        <button type="button" id="menu-debtors_report" class="report-menu-item" onclick="switchReport('debtors_report')">
            Customer Debtors Report
        </button>
        <button type="button" id="menu-item_wise_sales" class="report-menu-item" onclick="switchReport('item_wise_sales')">
            Item Wise Sales
        </button>
        <button type="button" id="menu-profit_loss" class="report-menu-item" onclick="switchReport('profit_loss')">
            Profit & Loss Report
        </button>
        <button type="button" id="menu-tpla_report" class="report-menu-item" onclick="switchReport('tpla_report')">
            Trading, Profit & Loss (TPLA)
        </button>
        <button type="button" id="menu-balance_sheet_report" class="report-menu-item" onclick="switchReport('balance_sheet_report')">
            Balance Sheet Statement
        </button>
        <button type="button" id="menu-expense_report" class="report-menu-item" onclick="switchReport('expense_report')">
            Expense Report
        </button>
        <button type="button" id="menu-purchases_report" class="report-menu-item" onclick="switchReport('purchases_report')">
            Purchases Report
        </button>
        <button type="button" id="menu-attendance_report" class="report-menu-item" onclick="switchReport('attendance_report')">
            Attendance Report
        </button>
        <button type="button" id="menu-payroll_report" class="report-menu-item" onclick="switchReport('payroll_report')">
            Payroll Report
        </button>
        <button type="button" id="menu-tax_report" class="report-menu-item" onclick="switchReport('tax_report')">
            Tax Report
        </button>
        <button type="button" id="menu-staff_sales" class="report-menu-item" onclick="switchReport('staff_sales')">
            Staff Sales Report
        </button>
        <button type="button" id="menu-inventory_report" class="report-menu-item" onclick="switchReport('inventory_report')">
            Inventory Report
        </button>

        <div style="border-top: 1px solid var(--card-border); margin-top: 1rem; padding-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.8rem; color: var(--text-muted);">
            <div><strong>Active Period:</strong></div>
            <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                <a href="{{ route('reports.index', ['period' => 'daily', 'report_type' => request('report_type')]) }}" class="btn-secondary {{ request('period') === 'daily' ? 'active' : '' }}" style="font-size: 0.75rem; padding: 0.25rem 0.5rem; text-decoration: none;">Daily</a>
                <a href="{{ route('reports.index', ['period' => 'weekly', 'report_type' => request('report_type')]) }}" class="btn-secondary {{ request('period') === 'weekly' ? 'active' : '' }}" style="font-size: 0.75rem; padding: 0.25rem 0.5rem; text-decoration: none;">Weekly</a>
                <a href="{{ route('reports.index', ['period' => 'monthly', 'report_type' => request('report_type')]) }}" class="btn-secondary {{ request('period') === 'monthly' ? 'active' : '' }}" style="font-size: 0.75rem; padding: 0.25rem 0.5rem; text-decoration: none;">Monthly</a>
                <a href="{{ route('reports.index', ['period' => 'all', 'report_type' => request('report_type')]) }}" class="btn-secondary {{ request('period') === 'all' || !request('period') ? 'active' : '' }}" style="font-size: 0.75rem; padding: 0.25rem 0.5rem; text-decoration: none;">All</a>
            </div>
        </div>
    </div>

    <!-- Right column: Selected Report Content Pane -->
    <div class="glass-panel" style="overflow: hidden; padding: 1.5rem; min-height: 500px; display: flex; flex-direction: column;">
        
        <!-- 1. Sales Report -->
        <div id="sales_report" class="report-pane" style="display: none;">
            <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 0.75rem; margin-bottom: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Sales Transactions Report</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Lists all retail transactions matching current search scopes.</p>
            </div>
            <div class="custom-table-container">
                <table class="custom-table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>Sale ID</th>
                            <th>Date & Time</th>
                            <th>Cashier</th>
                            <th>Customer Name</th>
                            <th>Payment Method</th>
                            <th>Total Revenue</th>
                            @if(auth()->user()->role === 'admin')
                                <th>Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @php $sumSales = 0; @endphp
                        @forelse($sales as $sale)
                            @php $sumSales += $sale->total; @endphp
                            <tr style="{{ $sale->trashed() ? 'background: rgba(239, 68, 68, 0.05); opacity: 0.9;' : '' }}">
                                <td>
                                    <code>#{{ $sale->id }}</code>
                                    @if($sale->trashed())
                                        <div style="font-size: 0.65rem; color: #ef4444; font-weight: 700;">[VOIDED]</div>
                                    @endif
                                </td>
                                <td>{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                                <td><strong>{{ $sale->user->name ?? 'System' }}</strong></td>
                                <td>{{ $sale->customer_name ?: 'Walk-in Customer' }}</td>
                                <td>
                                    @if($sale->trashed())
                                        <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">
                                            ⚠️ SOFT DELETED
                                        </span>
                                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.25rem; max-width: 220px; word-break: break-word;">
                                            <strong>Reason:</strong> {{ $sale->deletion_reason ?: 'N/A' }}
                                        </div>
                                        @if($sale->deletedByUser)
                                            <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 0.1rem;">
                                                By: {{ $sale->deletedByUser->name }} ({{ $sale->deleted_at?->format('M d, H:i') }})
                                            </div>
                                        @endif
                                    @elseif($sale->payment_method === 'debt')
                                        <span class="badge badge-pending">DEBT</span>
                                    @else
                                        <span class="badge badge-received">{{ strtoupper($sale->payment_method) }}</span>
                                    @endif
                                </td>
                                <td style="font-weight: 600; color: {{ $sale->trashed() ? '#94a3b8; text-decoration: line-through;' : 'var(--primary-hover);' }}">
                                    {{ $baseCurrency->symbol }}{{ number_format($sale->total, 2) }}
                                </td>
                                @if(auth()->user()->role === 'admin')
                                    <td>
                                        <div style="display: flex; gap: 0.35rem; align-items: center;">
                                            @if($sale->trashed())
                                                <form action="{{ route('sales.restore', $sale->id) }}" method="POST" onsubmit="return confirm('Restore Sale #{{ $sale->id }} back to active transactions?');" style="margin: 0; display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn-secondary" style="padding: 0.2rem 0.45rem; font-size: 0.72rem; color: #34d399; border-color: rgba(52, 211, 153, 0.4); display: inline-flex; align-items: center; gap: 0.2rem; cursor: pointer;" title="Restore this soft-deleted transaction">
                                                        ↩️ Restore
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('pos', ['edit_sale' => $sale->id]) }}" class="btn-secondary" style="padding: 0.2rem 0.45rem; font-size: 0.72rem; text-decoration: none; color: #f59e0b; border-color: rgba(245, 158, 11, 0.4); display: inline-flex; align-items: center; gap: 0.2rem;">
                                                    ✏️ Edit
                                                </a>
                                                <button type="button" class="btn-secondary" onclick="openSoftDeleteModal({{ $sale->id }}, '{{ $baseCurrency->symbol }}{{ number_format($sale->total, 2) }}', '{{ addslashes($sale->customer_name ?: 'Walk-in Customer') }}', '{{ $sale->created_at->format('Y-m-d H:i') }}')" style="padding: 0.2rem 0.45rem; font-size: 0.72rem; color: #ef4444; border-color: rgba(239, 68, 68, 0.4); display: inline-flex; align-items: center; gap: 0.2rem; cursor: pointer;" title="Soft delete transaction with audit authentication">
                                                    🗑️ Delete
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ auth()->user()->role === 'admin' ? 7 : 6 }}" style="text-align: center; color: var(--text-muted); padding: 2rem;">No sales records matching selection filters.</td></tr>
                        @endforelse
                    </tbody>
                    @if($sales->isNotEmpty())
                        <tfoot>
                            <tr style="font-weight: 700; border-top: 2px solid var(--card-border);">
                                <td colspan="5" style="text-align: right; padding: 1rem;">Total Sales Revenue:</td>
                                <td style="color: var(--primary-hover); padding: 1rem;">{{ $baseCurrency->symbol }}{{ number_format($sumSales, 2) }}</td>
                                @if(auth()->user()->role === 'admin')
                                    <td></td>
                                @endif
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- 2. Customer Debtors Report -->
        <div id="debtors_report" class="report-pane" style="display: none;">
            <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 0.75rem; margin-bottom: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Customer Debtors & Credit Report</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Lists all customer credit orders with outstanding balances and contact phone numbers.</p>
            </div>
            <div class="custom-table-container">
                <table class="custom-table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>Sale ID</th>
                            <th>Date & Time</th>
                            <th>Debtor Name</th>
                            <th>Contact Phone</th>
                            <th>Cashier</th>
                            <th>Order Total</th>
                            <th>Amount Paid</th>
                            <th>Debt Balance</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($debtorsSales as $debt)
                            @php $debtBal = $debt->total - $debt->amount_paid; @endphp
                            <tr>
                                <td><code>#{{ $debt->id }}</code></td>
                                <td>{{ $debt->created_at->format('Y-m-d H:i') }}</td>
                                <td><strong style="color: var(--text-main);">{{ $debt->customer_name ?: 'Unknown Customer' }}</strong></td>
                                <td>{{ $debt->customer_phone ?: 'N/A' }}</td>
                                <td>{{ $debt->user->name ?? 'System' }}</td>
                                <td>{{ $baseCurrency->symbol }}{{ number_format($debt->total, 2) }}</td>
                                <td>{{ $baseCurrency->symbol }}{{ number_format($debt->amount_paid, 2) }}</td>
                                <td style="font-weight: 700; color: var(--accent-amber);">{{ $baseCurrency->symbol }}{{ number_format($debtBal, 2) }}</td>
                                <td>
                                    @if($debt->payment_status === 'paid')
                                        <span class="badge badge-received">SETTLED</span>
                                    @else
                                        <span class="badge badge-pending">UNPAID DEBT</span>
                                    @endif
                                </td>
                                <td style="display: flex; gap: 0.35rem; align-items: center;">
                                    @if($debt->payment_status !== 'paid')
                                        <form action="{{ route('sales.settle-debt', $debt) }}" method="POST" onsubmit="return confirm('Mark debt for Sale #{{ $debt->id }} as fully settled?');" style="margin: 0;">
                                            @csrf
                                            <button type="submit" class="btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; width: auto; background: var(--accent-emerald); border: none;">Settle Debt</button>
                                        </form>
                                    @else
                                        <span style="font-size: 0.75rem; color: var(--text-muted);">Fully Paid</span>
                                    @endif
                                    @if(auth()->user()->role === 'admin')
                                        <a href="{{ route('pos', ['edit_sale' => $debt->id]) }}" class="btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; text-decoration: none; color: #f59e0b; border-color: rgba(245, 158, 11, 0.4); display: inline-flex; align-items: center; gap: 0.2rem;">
                                            ✏️ Edit Sale
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 2rem;">No customer credit debts logged.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. Item Wise Sales -->
        <div id="item_wise_sales" class="report-pane" style="display: none;">
            <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 0.75rem; margin-bottom: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Item Wise Sales Report</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Breakdown of pieces sold, cogs cost, and net margins per product catalog.</p>
            </div>
            <div class="custom-table-container">
                <table class="custom-table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>Product Item</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Pieces Sold</th>
                            <th>Cost of Goods (COGS)</th>
                            <th>Total Revenue</th>
                            <th>Net Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sumQty = 0; $sumCogs = 0; $sumRev = 0; @endphp
                        @forelse($inventorySales as $row)
                            @php
                                $sumQty += $row->total_qty;
                                $sumCogs += $row->total_cost;
                                $sumRev += $row->total_revenue;
                            @endphp
                            <tr>
                                <td><strong>{{ $row->product_name }}</strong></td>
                                <td><code>{{ $row->product_sku }}</code></td>
                                <td>{{ $row->product_category }}</td>
                                <td>{{ $row->total_qty }} pieces</td>
                                <td>{{ $baseCurrency->symbol }}{{ number_format($row->total_cost, 2) }}</td>
                                <td style="font-weight: 600; color: var(--primary-hover);">{{ $baseCurrency->symbol }}{{ number_format($row->total_revenue, 2) }}</td>
                                <td style="font-weight: 600; color: var(--accent-emerald);">{{ $baseCurrency->symbol }}{{ number_format($row->total_revenue - $row->total_cost, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No items sold matching filters.</td></tr>
                        @endforelse
                    </tbody>
                    @if($inventorySales->isNotEmpty())
                        <tfoot>
                            <tr style="font-weight: 700; border-top: 2px solid var(--card-border);">
                                <td colspan="3" style="text-align: right; padding: 1rem;">Totals:</td>
                                <td style="padding: 1rem;">{{ $sumQty }} pieces</td>
                                <td style="padding: 1rem;">{{ $baseCurrency->symbol }}{{ number_format($sumCogs, 2) }}</td>
                                <td style="padding: 1rem; color: var(--primary-hover);">{{ $baseCurrency->symbol }}{{ number_format($sumRev, 2) }}</td>
                                <td style="padding: 1rem; color: var(--accent-emerald);">{{ $baseCurrency->symbol }}{{ number_format($sumRev - $sumCogs, 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- 4. Profit & Loss Report -->
        <div id="profit_loss" class="report-pane" style="display: none;">
            <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 0.75rem; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Profit & Loss Statement (P&L)</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Periodic operating financials, costs, outflows, and net profit margins.</p>
            </div>
            
            @php
                $grossSales = $sales->sum('total');
                $cogsVal = $inventorySales->sum('total_cost');
                $grossProfit = $grossSales - $cogsVal;
                $operatingExpenses = $expenses->sum('amount');
                $netPAndL = $grossProfit - $operatingExpenses;
            @endphp
            
            <div style="background: rgba(255,255,255,0.01); border: 1px solid var(--card-border); border-radius: var(--radius-sm); padding: 2rem; max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; gap: 0.5rem;">
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main);">{{ Auth::user()->tenant->name ?? 'Store' }} — Statement of Income</h3>
                    <p style="font-size: 0.80rem; color: var(--text-muted);">Period: {{ request('period') ?: 'All Time' }}</p>
                </div>

                <div class="p-and-l-row">
                    <span style="font-weight: 600; color: var(--text-main);">GROSS SALES REVENUE</span>
                    <strong style="color: var(--primary-hover);">{{ $baseCurrency->symbol }}{{ number_format($grossSales, 2) }}</strong>
                </div>
                <div class="p-and-l-row">
                    <span style="color: var(--text-muted); padding-left: 1rem;">Cost of Goods Sold (COGS)</span>
                    <span style="color: var(--text-main); font-weight: 500;">({{ $baseCurrency->symbol }}{{ number_format($cogsVal, 2) }})</span>
                </div>
                
                <div class="p-and-l-row" style="border-bottom: 2px solid var(--card-border); padding-top: 1rem; padding-bottom: 1rem;">
                    <span style="font-weight: 700; color: var(--text-main);">GROSS PROFIT</span>
                    <strong style="color: var(--accent-emerald);">{{ $baseCurrency->symbol }}{{ number_format($grossProfit, 2) }}</strong>
                </div>

                <div class="p-and-l-row">
                    <span style="color: var(--text-muted); padding-left: 1rem;">Operating Store Expenses</span>
                    <span style="color: #fca5a5; font-weight: 500;">({{ $baseCurrency->symbol }}{{ number_format($operatingExpenses, 2) }})</span>
                </div>

                <div class="p-and-l-row" style="border-top: 2px solid var(--card-border); margin-top: 0.5rem; padding-top: 1rem; font-size: 1.1rem;">
                    <span style="font-weight: 800; color: var(--text-main);">NET OPERATING PROFIT</span>
                    <strong style="color: {{ $netPAndL >= 0 ? 'var(--accent-emerald)' : 'var(--accent-coral)' }}; font-size: 1.25rem;">
                        {{ $baseCurrency->symbol }}{{ number_format($netPAndL, 2) }}
                    </strong>
                </div>
            </div>
        </div>

        <!-- 5. Expense Report -->
        <div id="expense_report" class="report-pane" style="display: none;">
            <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 0.75rem; margin-bottom: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Store Expenses Report</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Outflows grouped by date, category, and logged by users.</p>
            </div>
            <div class="custom-table-container">
                <table class="custom-table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Logged By</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sumExp = 0; @endphp
                        @forelse($expenses as $exp)
                            @php $sumExp += $exp->amount; @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($exp->expense_date)->format('Y-m-d') }}</td>
                                <td><span class="badge badge-pending">{{ strtoupper($exp->category) }}</span></td>
                                <td>{{ $exp->description ?: 'N/A' }}</td>
                                <td>{{ $exp->user->name ?? 'System' }}</td>
                                <td style="font-weight: 600; color: #fca5a5;">{{ $baseCurrency->symbol }}{{ number_format($exp->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No expense logs recorded.</td></tr>
                        @endforelse
                    </tbody>
                    @if($expenses->isNotEmpty())
                        <tfoot>
                            <tr style="font-weight: 700; border-top: 2px solid var(--card-border);">
                                <td colspan="4" style="text-align: right; padding: 1rem;">Total Expenses Outflow:</td>
                                <td style="color: #fca5a5; padding: 1rem;">{{ $baseCurrency->symbol }}{{ number_format($sumExp, 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- 6. Purchases Report -->
        <div id="purchases_report" class="report-pane" style="display: none;">
            <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 0.75rem; margin-bottom: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Purchases Orders Report (XPOs)</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Inventory stock procurement logs and supplier order volumes.</p>
            </div>
            <div class="custom-table-container">
                <table class="custom-table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Date Created</th>
                            <th>Supplier Name</th>
                            <th>Status</th>
                            <th>Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sumPO = 0; @endphp
                        @forelse($purchases as $po)
                            @php $sumPO += $po->total_cost; @endphp
                            <tr>
                                <td><code>{{ $po->po_number }}</code></td>
                                <td>{{ $po->created_at->format('Y-m-d') }}</td>
                                <td><strong>{{ $po->supplier_name }}</strong></td>
                                <td>
                                    @if($po->status === 'received')
                                        <span class="badge badge-received">RECEIVED</span>
                                    @else
                                        <span class="badge badge-pending">PENDING</span>
                                    @endif
                                </td>
                                <td style="font-weight: 600;">{{ $baseCurrency->symbol }}{{ number_format($po->total_cost, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No purchase orders found.</td></tr>
                        @endforelse
                    </tbody>
                    @if($purchases->isNotEmpty())
                        <tfoot>
                            <tr style="font-weight: 700; border-top: 2px solid var(--card-border);">
                                <td colspan="4" style="text-align: right; padding: 1rem;">Total Procurement Spend:</td>
                                <td style="padding: 1rem;">{{ $baseCurrency->symbol }}{{ number_format($sumPO, 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- 7. Attendance Report -->
        <div id="attendance_report" class="report-pane" style="display: none;">
            <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 0.75rem; margin-bottom: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Staff Attendance Logs</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Daily check-in / check-out records and tardiness status tracking.</p>
            </div>
            <div class="custom-table-container">
                <table class="custom-table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee Name</th>
                            <th>Check-in Time</th>
                            <th>Check-out Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $att)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($att->date)->format('Y-m-d') }}</td>
                                <td><strong>{{ $att->user->name ?? 'Staff' }}</strong></td>
                                <td><code>{{ $att->check_in ? substr($att->check_in, 0, 5) : 'N/A' }}</code></td>
                                <td><code>{{ $att->check_out ? substr($att->check_out, 0, 5) : 'On Shift' }}</code></td>
                                <td>
                                    @if($att->status === 'late')
                                        <span class="badge badge-cancelled">LATE CHECK-IN</span>
                                    @else
                                        <span class="badge badge-received">ON TIME</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No attendance records matching selection filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 8. Payroll Report -->
        <div id="payroll_report" class="report-pane" style="display: none;">
            <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 0.75rem; margin-bottom: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Staff Payroll & Commission Report</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Base salaries, 2% sales commissions, tardiness penalty deductions, and net payouts.</p>
            </div>
            <div class="custom-table-container">
                <table class="custom-table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Role</th>
                            <th>Base Salary</th>
                            <th>Sales Volume</th>
                            <th>Commission (2%)</th>
                            <th>Late Deductions</th>
                            <th>Net Payout</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payroll as $pay)
                            <tr>
                                <td><strong>{{ $pay->name }}</strong></td>
                                <td><span class="badge badge-received">{{ strtoupper($pay->role) }}</span></td>
                                <td>{{ $baseCurrency->symbol }}{{ number_format($pay->base_salary, 2) }}</td>
                                <td>{{ $baseCurrency->symbol }}{{ number_format($pay->sales_volume, 2) }} ({{ $pay->sales_count }} sales)</td>
                                <td style="color: var(--accent-emerald); font-weight: 600;">+{{ $baseCurrency->symbol }}{{ number_format($pay->commission, 2) }}</td>
                                <td style="color: #fca5a5;">-{{ $baseCurrency->symbol }}{{ number_format($pay->deductions, 2) }} ({{ $pay->late_count }} late)</td>
                                <td style="font-weight: 800; color: var(--primary-hover); font-size: 0.95rem;">{{ $baseCurrency->symbol }}{{ number_format($pay->net_salary, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 9. Tax Report -->
        <div id="tax_report" class="report-pane" style="display: none;">
            <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 0.75rem; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Tax Collections Report</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Summary of taxable sales transactions and total government tax liability.</p>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
                <div class="glass-panel panel-body" style="background: rgba(255,255,255,0.01);">
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.5rem;">Taxable Sales Base</p>
                    <h3 style="font-size: 1.8rem; font-weight: 800; color: var(--text-main);">{{ $baseCurrency->symbol }}{{ number_format($taxReport['total_sales_taxable'], 2) }}</h3>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem;">Net taxable transaction subtotal</p>
                </div>

                <div class="glass-panel panel-body" style="background: rgba(16, 185, 129, 0.03); border-color: rgba(16, 185, 129, 0.2);">
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.5rem;">Total Sales Tax Collected</p>
                    <h3 style="font-size: 1.8rem; font-weight: 800; color: var(--accent-emerald);">{{ $baseCurrency->symbol }}{{ number_format($taxReport['total_tax_collected'], 2) }}</h3>
                    <p style="font-size: 0.75rem; color: var(--accent-emerald); margin-top: 0.5rem;">Collected via store checkout taxes</p>
                </div>
            </div>
        </div>

        <!-- 10. Staff Sales Report -->
        <div id="staff_sales" class="report-pane" style="display: none;">
            <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 0.75rem; margin-bottom: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Staff Performance Sales Report</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Staff ticket counts, sales totals, and revenue contribution ranking.</p>
            </div>
            <div class="custom-table-container">
                <table class="custom-table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>Staff Name</th>
                            <th>Role</th>
                            <th>Tickets Handled</th>
                            <th>Total Revenue Volume</th>
                            <th>Commission Earned</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($staffSalesReport as $stf)
                            <tr>
                                <td><strong>{{ $stf->staff_name }}</strong></td>
                                <td><span class="badge badge-received">{{ strtoupper($stf->staff_role) }}</span></td>
                                <td>{{ $stf->sales_count }} tickets</td>
                                <td style="font-weight: 600; color: var(--primary-hover);">{{ $baseCurrency->symbol }}{{ number_format($stf->total_revenue, 2) }}</td>
                                <td style="color: var(--accent-emerald); font-weight: 600;">{{ $baseCurrency->symbol }}{{ number_format($stf->total_revenue * 0.02, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No staff performance logs available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 11. Inventory Report -->
        <div id="inventory_report" class="report-pane" style="display: none;">
            <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 0.75rem; margin-bottom: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Inventory Valuation & Stock Report</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Warehouse quantity counts, unit costs, selling prices, and asset valuations.</p>
            </div>
            <div class="custom-table-container">
                <table class="custom-table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>Product Item</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>In Stock</th>
                            <th>Cost Price</th>
                            <th>Selling Price</th>
                            <th>Total Cost Valuation</th>
                            <th>Total Retail Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totCostVal = 0; $totRetailVal = 0; @endphp
                        @forelse($inventory as $item)
                            @php
                                $cVal = $item->quantity * $item->cost;
                                $rVal = $item->quantity * $item->price;
                                $totCostVal += $cVal;
                                $totRetailVal += $rVal;
                            @endphp
                            <tr>
                                <td><strong>{{ $item->name }}</strong></td>
                                <td><code>{{ $item->sku }}</code></td>
                                <td>{{ $item->category }}</td>
                                <td>
                                    @if($item->quantity <= $item->low_stock_threshold)
                                        <span class="badge badge-cancelled">{{ $item->quantity }} left</span>
                                    @else
                                        <span class="badge badge-received">{{ $item->quantity }} units</span>
                                    @endif
                                </td>
                                <td>{{ $baseCurrency->symbol }}{{ number_format($item->cost, 2) }}</td>
                                <td>{{ $baseCurrency->symbol }}{{ number_format($item->price, 2) }}</td>
                                <td>{{ $baseCurrency->symbol }}{{ number_format($cVal, 2) }}</td>
                                <td style="font-weight: 600; color: var(--accent-emerald);">{{ $baseCurrency->symbol }}{{ number_format($rVal, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">No inventory items registered.</td></tr>
                        @endforelse
                    </tbody>
                    @if($inventory->isNotEmpty())
                        <tfoot>
                            <tr style="font-weight: 700; border-top: 2px solid var(--card-border);">
                                <td colspan="6" style="text-align: right; padding: 1rem;">Total Warehouse Asset Valuations:</td>
                                <td style="padding: 1rem;">{{ $baseCurrency->symbol }}{{ number_format($totCostVal, 2) }}</td>
                                <td style="padding: 1rem; color: var(--accent-emerald);">{{ $baseCurrency->symbol }}{{ number_format($totRetailVal, 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- 12. Trading, Profit & Loss Account (TPLA) -->
        <div id="tpla_report" class="report-pane" style="display: none;">
            <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 0.75rem; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Trading, Profit & Loss Account (TPLA)</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Formal corporate financial statement recording trading turnover, COGS, operating overheads, and net profit after sales tax liabilities.</p>
            </div>
            
            <div style="background: rgba(255,255,255,0.01); border: 1px solid var(--card-border); border-radius: var(--radius-sm); padding: 2rem; max-width: 680px; margin: 0 auto; display: flex; flex-direction: column; gap: 0.5rem;">
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">{{ Auth::user()->tenant->name ?? 'Store' }} — Trading & Profit & Loss Account</h3>
                    <p style="font-size: 0.80rem; color: var(--text-muted);">Reporting Period Scope: {{ request('period') ?: 'All Time' }}</p>
                </div>

                <div style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase; color: var(--primary); margin-top: 0.5rem;">Trading Account Portion</div>
                
                <div class="p-and-l-row">
                    <span style="font-weight: 600; color: var(--text-main);">GROSS SALES REVENUE (SUBTOTAL)</span>
                    <strong style="color: var(--primary-hover);">{{ $baseCurrency->symbol }}{{ number_format($tplaReport['gross_sales_revenue'], 2) }}</strong>
                </div>
                <div class="p-and-l-row">
                    <span style="color: var(--text-muted); padding-left: 1rem;">Less: Cost of Goods Sold (COGS)</span>
                    <span style="color: #fca5a5; font-weight: 500;">-{{ $baseCurrency->symbol }}{{ number_format($tplaReport['cogs_total'], 2) }}</span>
                </div>
                <div class="p-and-l-row" style="border-bottom: 2px solid var(--card-border); padding-top: 0.75rem; padding-bottom: 0.75rem; background: rgba(16, 185, 129, 0.03);">
                    <span style="font-weight: 700; color: var(--text-main);">GROSS TRADING PROFIT</span>
                    <strong style="color: var(--accent-emerald);">{{ $baseCurrency->symbol }}{{ number_format($tplaReport['gross_profit'], 2) }}</strong>
                </div>

                <div style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase; color: var(--accent-amber); margin-top: 1rem;">Profit & Loss Account Portion</div>

                <div class="p-and-l-row">
                    <span style="color: var(--text-muted); padding-left: 1rem;">Less: Store Operating Expenses</span>
                    <span style="color: #fca5a5; font-weight: 500;">-{{ $baseCurrency->symbol }}{{ number_format($tplaReport['total_operating_expenses'], 2) }}</span>
                </div>
                <div class="p-and-l-row" style="border-bottom: 1px solid var(--card-border);">
                    <span style="font-weight: 600; color: var(--text-main);">NET OPERATING PROFIT BEFORE TAX</span>
                    <strong style="color: {{ $tplaReport['net_profit_before_tax'] >= 0 ? 'var(--primary-hover)' : 'var(--accent-coral)' }};">
                        {{ $baseCurrency->symbol }}{{ number_format($tplaReport['net_profit_before_tax'], 2) }}
                    </strong>
                </div>
                <div class="p-and-l-row">
                    <span style="color: var(--text-muted); padding-left: 1rem;">Less: Sales Tax Liabilities / Collections</span>
                    <span style="color: #fca5a5; font-weight: 500;">-{{ $baseCurrency->symbol }}{{ number_format($tplaReport['tax_collected_total'], 2) }}</span>
                </div>

                <div class="p-and-l-row" style="border-top: 2px solid var(--card-border); margin-top: 0.75rem; padding-top: 1rem; font-size: 1.15rem; background: rgba(255,255,255,0.02);">
                    <span style="font-weight: 800; color: var(--text-main);">NET PROFIT AFTER TAX (RETAINED EARNINGS)</span>
                    <strong style="color: {{ $tplaReport['net_profit_after_tax'] >= 0 ? 'var(--accent-emerald)' : 'var(--accent-coral)' }}; font-size: 1.3rem;">
                        {{ $baseCurrency->symbol }}{{ number_format($tplaReport['net_profit_after_tax'], 2) }}
                    </strong>
                </div>
            </div>
        </div>

        <!-- 13. Balance Sheet Statement -->
        <div id="balance_sheet_report" class="report-pane" style="display: none;">
            <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 0.75rem; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Corporate Balance Sheet Statement</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Financial position statement balancing store assets against accounts payable liabilities and owner equity capital.</p>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
                <!-- Assets Side -->
                <div class="glass-panel panel-body" style="border-color: rgba(16, 185, 129, 0.3);">
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--accent-emerald); border-bottom: 1px solid var(--card-border); padding-bottom: 0.75rem; margin-bottom: 1rem;">ASSETS (Current & Capital)</h3>
                    
                    <div class="p-and-l-row">
                        <span>Cash & Cash Equivalents</span>
                        <strong style="color: var(--text-main);">{{ $baseCurrency->symbol }}{{ number_format($formalBalanceSheet['cash_on_hand'], 2) }}</strong>
                    </div>
                    <div class="p-and-l-row">
                        <span>Customer Debts Receivable</span>
                        <strong style="color: var(--accent-amber);">{{ $baseCurrency->symbol }}{{ number_format($formalBalanceSheet['customer_debts_receivable'], 2) }}</strong>
                    </div>
                    <div class="p-and-l-row">
                        <span>Warehouse Inventory Asset Valuation</span>
                        <strong style="color: var(--text-main);">{{ $baseCurrency->symbol }}{{ number_format($formalBalanceSheet['inventory_valuation_cost'], 2) }}</strong>
                    </div>

                    <div class="p-and-l-row" style="border-top: 2px solid var(--card-border); margin-top: 1rem; padding-top: 1rem; font-size: 1.1rem; background: rgba(16, 185, 129, 0.05);">
                        <span style="font-weight: 800; color: var(--text-main);">TOTAL ASSETS</span>
                        <strong style="color: var(--accent-emerald); font-size: 1.25rem;">{{ $baseCurrency->symbol }}{{ number_format($formalBalanceSheet['total_current_assets'], 2) }}</strong>
                    </div>
                </div>

                <!-- Liabilities & Owner Equity Side -->
                <div class="glass-panel panel-body" style="border-color: rgba(245, 158, 11, 0.3);">
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--accent-amber); border-bottom: 1px solid var(--card-border); padding-bottom: 0.75rem; margin-bottom: 1rem;">LIABILITIES & OWNER EQUITY</h3>
                    
                    <div class="p-and-l-row">
                        <span>Accounts Payable (Supplier PO Balances)</span>
                        <strong style="color: #fca5a5;">{{ $baseCurrency->symbol }}{{ number_format($formalBalanceSheet['accounts_payable_suppliers'], 2) }}</strong>
                    </div>
                    <div class="p-and-l-row">
                        <span>Sales Tax Payable (Government Liability)</span>
                        <strong style="color: #fca5a5;">{{ $baseCurrency->symbol }}{{ number_format($formalBalanceSheet['tax_payable_government'], 2) }}</strong>
                    </div>
                    <div class="p-and-l-row" style="border-bottom: 1px solid var(--card-border);">
                        <span style="font-weight: 600;">Total Liabilities</span>
                        <strong style="color: #fca5a5;">{{ $baseCurrency->symbol }}{{ number_format($formalBalanceSheet['total_current_liabilities'], 2) }}</strong>
                    </div>

                    <div class="p-and-l-row">
                        <span>Owner Equity & Retained Capital</span>
                        <strong style="color: var(--primary-hover);">{{ $baseCurrency->symbol }}{{ number_format($formalBalanceSheet['owners_equity'], 2) }}</strong>
                    </div>

                    <div class="p-and-l-row" style="border-top: 2px solid var(--card-border); margin-top: 1rem; padding-top: 1rem; font-size: 1.1rem; background: rgba(245, 158, 11, 0.05);">
                        <span style="font-weight: 800; color: var(--text-main);">TOTAL LIABILITIES & EQUITY</span>
                        <strong style="color: var(--accent-amber); font-size: 1.25rem;">{{ $baseCurrency->symbol }}{{ number_format($formalBalanceSheet['total_liabilities_and_equity'], 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
    function switchReport(type) {
        // Hide all panes
        document.querySelectorAll('.report-pane').forEach(el => el.style.display = 'none');
        // Deactivate all sidebar items
        document.querySelectorAll('.report-menu-item').forEach(el => el.classList.remove('active'));

        // Show target pane
        const targetPane = document.getElementById(type);
        const targetBtn = document.getElementById(`menu-${type}`);

        if (targetPane && targetBtn) {
            targetPane.style.display = 'block';
            targetBtn.classList.add('active');
        }

        // Update hidden input and export button URL
        const input = document.getElementById('report_type_input');
        if (input) input.value = type;

        const exportBtn = document.getElementById('export-btn');
        if (exportBtn) {
            const currentUrl = new URL(exportBtn.href);
            currentUrl.searchParams.set('report_type', type);
            exportBtn.href = currentUrl.toString();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Set initial active report type from URL parameter or default to sales_report
        const urlParams = new URLSearchParams(window.location.search);
        const activeType = urlParams.get('report_type') || 'sales_report';
        switchReport(activeType);
    });
</script>
@endsection
