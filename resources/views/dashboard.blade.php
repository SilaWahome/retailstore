@extends('layouts.app')

@section('title', 'Admin Dashboard - Luxeshop POS')

@section('content')
<div class="page-header">
    <h1 class="page-title">Admin Dashboard</h1>
</div>

<!-- Financial Summary Grid -->
<div class="grid-3" style="margin-bottom: 1.5rem;">
    <!-- Sales Revenue -->
    <div class="glass-panel panel-body" style="border-left: 5px solid var(--primary); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500; margin-bottom: 0.25rem;">Total Sales Revenue</p>
            <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main);">{{ $baseCurrency->symbol }}{{ number_format($totalSales, 2) }}</h2>
        </div>
    </div>

    <!-- Expenses -->
    <div class="glass-panel panel-body" style="border-left: 5px solid var(--accent-coral); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500; margin-bottom: 0.25rem;">Total Store Expenses</p>
            <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main);">{{ $baseCurrency->symbol }}{{ number_format($totalExpenses, 2) }}</h2>
        </div>
    </div>

    <!-- Outstanding Customer Debts -->
    <div class="glass-panel panel-body" style="border-left: 5px solid var(--accent-amber); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500; margin-bottom: 0.25rem;">Outstanding Customer Debts</p>
            <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--accent-amber);">{{ $baseCurrency->symbol }}{{ number_format($totalOutstandingDebts, 2) }}</h2>
            <span style="font-size: 0.75rem; color: var(--text-muted);">{{ $debtorsCount }} unpaid debtor sale(s)</span>
        </div>
    </div>
</div>

<div class="grid-2" style="margin-bottom: 2rem;">
    <!-- COGS -->
    <div class="glass-panel panel-body" style="border-left: 5px solid #6366f1; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500; margin-bottom: 0.25rem;">Cost of Goods Sold (COGS)</p>
            <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main);">{{ $baseCurrency->symbol }}{{ number_format($totalCogs, 2) }}</h2>
        </div>
    </div>

    <!-- Net Profit -->
    <div class="glass-panel panel-body" style="border-left: 5px solid var(--accent-emerald); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500; margin-bottom: 0.25rem;">Net Operating Profit</p>
            <h2 style="font-size: 1.6rem; font-weight: 800; color: {{ $netProfit >= 0 ? 'var(--accent-emerald)' : 'var(--accent-coral)' }};">
                {{ $baseCurrency->symbol }}{{ number_format($netProfit, 2) }}
            </h2>
        </div>
    </div>
</div>

<!-- Visual Analytics Charts Grid -->
<div class="grid-2" style="margin-bottom: 2rem; gap: 1.5rem; display: grid; grid-template-columns: 2fr 1fr;">
    <!-- Sales Revenue Line Trend -->
    <div class="glass-panel panel-body" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; min-height: 350px;">
        <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-main);">Sales Revenue Trend (Last 15 Days)</h3>
            <span style="font-size: 0.75rem; color: var(--text-muted);">Daily cumulative volume</span>
        </div>
        <div style="flex-grow: 1; position: relative;">
            <canvas id="salesTrendChart" style="width: 100%; height: 100%; min-height: 250px;"></canvas>
        </div>
    </div>

    <!-- Product Category Doughnut Share -->
    <div class="glass-panel panel-body" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; min-height: 350px;">
        <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 0.5rem;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-main);">Sales by Product Category</h3>
        </div>
        <div style="flex-grow: 1; position: relative; display: flex; align-items: center; justify-content: center;">
            <canvas id="categorySalesChart" style="max-width: 250px; max-height: 250px;"></canvas>
        </div>
    </div>
</div>

<!-- Low Stock Alerts -->
@if($lowStockProducts->isNotEmpty())
<div class="glass-panel" style="margin-bottom: 2rem; border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.02);">
    <div class="panel-body" style="border-bottom: 1px solid rgba(239, 68, 68, 0.15); display: flex; align-items: center; gap: 0.5rem;">
        <h2 style="font-size: 1.15rem; font-weight: 700; color: #fca5a5;">Low Stock Alerts</h2>
    </div>
    <div class="custom-table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Branch</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Available Stock</th>
                    <th>Alert Limit</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lowStockProducts as $prod)
                    <tr>
                        <td><strong>{{ $prod->name }}</strong></td>
                        <td>
                            @if($prod->branch)
                                <span style="font-size: 0.75rem; font-weight: 600; background: rgba(139, 92, 246, 0.1); color: var(--primary); padding: 0.15rem 0.4rem; border-radius: 4px;">
                                    📍 {{ $prod->branch->name }}
                                </span>
                            @else
                                <span style="font-size: 0.75rem; color: var(--text-muted);">All Branches</span>
                            @endif
                        </td>
                        <td><code>{{ $prod->sku }}</code></td>
                        <td>{{ $prod->category }}</td>
                        <td><span class="badge badge-cancelled">{{ $prod->quantity }} left</span></td>
                        <td>{{ $prod->low_stock_threshold }}</td>
                        <td>
                            <a href="{{ route('products.index') }}" style="color: var(--primary); text-decoration: none; font-size: 0.85rem; font-weight: 600;">Restock</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Grid 2: Statistics rankings -->
<div class="grid-2" style="margin-bottom: 2rem;">
    <!-- Top Products -->
    <div class="glass-panel">
        <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
            <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Top Selling Products</h2>
        </div>
        <div class="custom-table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProducts as $item)
                        <tr>
                            <td>
                                <div><strong>{{ $item->name }}</strong></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $item->sku }}</div>
                            </td>
                            <td>{{ $item->total_qty }} unit(s)</td>
                            <td style="font-weight: 600; color: var(--primary-hover);">{{ $baseCurrency->symbol }}{{ number_format($item->total_revenue, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No sales data available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Sellers / Cashiers -->
    <div class="glass-panel">
        <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
            <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Top Sellers (Cashiers)</h2>
        </div>
        <div class="custom-table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Seller Name</th>
                        <th>Ticket Count</th>
                        <th>Total Volume</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topSellers as $seller)
                        <tr>
                            <td><strong>{{ $seller->name }}</strong></td>
                            <td>{{ $seller->sales_count }} tickets</td>
                            <td style="font-weight: 600; color: var(--accent-emerald);">{{ $baseCurrency->symbol }}{{ number_format($seller->total_revenue, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No sales data available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Outstanding Customer Debts & Credit Directory -->
<div class="glass-panel" style="margin-bottom: 2rem; border-color: rgba(245, 158, 11, 0.3);">
    <div class="panel-body" style="border-bottom: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">Outstanding Customer Debts Directory</h2>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Customers with unpaid credit orders and outstanding balances.</p>
        </div>
        <a href="{{ route('reports.index') }}" class="btn-secondary" style="font-size: 0.8rem; text-decoration: none;">View Debtors Report</a>
    </div>
    <div class="custom-table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Sale ID</th>
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
                @forelse($unpaidSales as $debtSale)
                    @php $debtBal = $debtSale->total - $debtSale->amount_paid; @endphp
                    <tr>
                        <td><strong>#{{ $debtSale->id }}</strong></td>
                        <td><strong style="color: var(--text-main);">{{ $debtSale->customer_name ?: 'Unknown Customer' }}</strong></td>
                        <td><span>{{ $debtSale->customer_phone ?: 'N/A' }}</span></td>
                        <td>{{ $debtSale->user->name ?? 'System' }}</td>
                        <td>{{ $baseCurrency->symbol }}{{ number_format($debtSale->total, 2) }}</td>
                        <td>{{ $baseCurrency->symbol }}{{ number_format($debtSale->amount_paid, 2) }}</td>
                        <td style="font-weight: 700; color: var(--accent-amber);">{{ $baseCurrency->symbol }}{{ number_format($debtBal, 2) }}</td>
                        <td><span class="badge badge-pending">UNPAID DEBT</span></td>
                        <td style="display: flex; gap: 0.35rem; align-items: center;">
                            <form action="{{ route('sales.settle-debt', $debtSale) }}" method="POST" onsubmit="return confirm('Mark debt for Sale #{{ $debtSale->id }} as fully settled?');" style="margin: 0;">
                                @csrf
                                <button type="submit" class="btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; width: auto; background: var(--accent-emerald); border: none;">Settle Debt</button>
                            </form>
                            @if(auth()->user()->role === 'admin')
                                <a href="{{ route('pos', ['edit_sale' => $debtSale->id]) }}" class="btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; text-decoration: none; color: #f59e0b; border-color: rgba(245, 158, 11, 0.4); display: inline-flex; align-items: center; gap: 0.2rem;">
                                    ✏️ Edit Sale
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No active customer debts recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Grid 2: Recent transactions -->
<div class="grid-2" style="margin-bottom: 3rem;">
    <!-- Recent Sales -->
    <div class="glass-panel">
        <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
            <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Recent Sales</h2>
        </div>
        <div class="custom-table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Branch</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Payment Method</th>
                        @if(auth()->user()->role === 'admin')
                            <th>Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSales as $sale)
                        <tr>
                            <td><strong>#{{ $sale->id }}</strong><div style="font-size: 0.7rem; color: var(--text-muted);">{{ $sale->created_at->diffForHumans() }}</div></td>
                            <td>
                                @if($sale->branch)
                                    <span style="font-size: 0.75rem; font-weight: 600; background: rgba(139, 92, 246, 0.1); color: var(--primary); padding: 0.15rem 0.4rem; border-radius: 4px;">
                                        📍 {{ $sale->branch->name }}
                                    </span>
                                @else
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">Main</span>
                                @endif
                            </td>
                            <td>{{ $sale->customer_name ?: 'Walk-in Customer' }}</td>
                            <td style="font-weight: 600; color: var(--primary-hover);">{{ $baseCurrency->symbol }}{{ number_format($sale->total, 2) }}</td>
                            <td>
                                @if($sale->payment_method === 'debt')
                                    <span class="badge badge-pending">DEBT</span>
                                @else
                                    <span class="badge badge-received">{{ strtoupper($sale->payment_method) }}</span>
                                @endif
                            </td>
                            @if(auth()->user()->role === 'admin')
                                <td>
                                    <div style="display: flex; gap: 0.35rem; align-items: center;">
                                        <a href="{{ route('pos', ['edit_sale' => $sale->id]) }}" class="btn-secondary" style="padding: 0.2rem 0.45rem; font-size: 0.72rem; text-decoration: none; color: #f59e0b; border-color: rgba(245, 158, 11, 0.4); display: inline-flex; align-items: center; gap: 0.2rem;">
                                            ✏️ Edit
                                        </a>
                                        <button type="button" class="btn-secondary" onclick="openSoftDeleteModal({{ $sale->id }}, '{{ $baseCurrency->symbol }}{{ number_format($sale->total, 2) }}', '{{ addslashes($sale->customer_name ?: 'Walk-in Customer') }}', '{{ $sale->created_at->format('Y-m-d H:i') }}')" style="padding: 0.2rem 0.45rem; font-size: 0.72rem; color: #ef4444; border-color: rgba(239, 68, 68, 0.4); display: inline-flex; align-items: center; gap: 0.2rem; cursor: pointer;" title="Soft delete transaction with audit authentication">
                                            🗑️ Delete
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->role === 'admin' ? 6 : 5 }}" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No sales transacted.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Expenses -->
    <div class="glass-panel">
        <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
            <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Recent Expenses</h2>
        </div>
        <div class="custom-table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentExpenses as $exp)
                        <tr>
                            <td>
                                <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-main); border: 1px solid var(--card-border);">
                                    {{ strtoupper($exp->category) }}
                                </span>
                                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem;">{{ $exp->expense_date->format('Y-m-d') }}</div>
                            </td>
                            <td>{{ $exp->description ?: 'N/A' }}</td>
                            <td style="font-weight: 600; color: #fca5a5;">{{ $baseCurrency->symbol }}{{ number_format($exp->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No expenses logged.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // 1. Sales Trend Line Chart
        const salesTrendRaw = @json($salesTrend);
        const labels = salesTrendRaw.map(item => item.date);
        const data = salesTrendRaw.map(item => parseFloat(item.total));

        const ctx1 = document.getElementById('salesTrendChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Sales ({{ $baseCurrency->symbol }})',
                    data: data,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.15)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#6366f1',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: 'var(--text-muted)', font: { size: 10 } }
                    },
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: 'var(--text-muted)', font: { size: 10 } }
                    }
                }
            }
        });

        // 2. Category Sales Doughnut Chart
        const categoryRaw = @json($categorySales);
        const catLabels = categoryRaw.map(item => item.category);
        const catData = categoryRaw.map(item => parseFloat(item.total));

        const ctx2 = document.getElementById('categorySalesChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catData,
                    backgroundColor: [
                        '#6366f1', // Indigo
                        '#10b981', // Emerald
                        '#f59e0b', // Amber
                        '#ec4899', // Pink
                        '#8b5cf6', // Violet
                        '#3b82f6'  // Blue
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'var(--text-muted)',
                            font: { size: 10 },
                            padding: 8
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
