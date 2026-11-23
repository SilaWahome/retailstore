@extends('layouts.app')

@section('title', 'Cashier Dashboard - Luxeshop POS')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1 class="page-title">Cashier Dashboard</h1>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
            Daily register overview for {{ Auth::user()->name }} — {{ \Carbon\Carbon::today()->format('l, F j, Y') }}
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('pos') }}" class="btn-primary" style="text-decoration: none; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem;">
            <span>Open POS Checkout</span>
        </a>
    </div>
</div>

<!-- Key Performance Metrics Row -->
<div class="grid-4" style="margin-bottom: 1.5rem;">
    <!-- Cashier Today Sales -->
    <div class="stat-card" style="border-left: 4px solid #10b981;">
        <span class="stat-label">My Sales Today</span>
        <div class="stat-value" style="color: #34d399;">
            {{ $baseCurrency->symbol }}{{ number_format($todayCashierSales, 2) }}
        </div>
        <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
            Total register receipts logged by you today
        </span>
    </div>

    <!-- Cashier Today Transactions -->
    <div class="stat-card" style="border-left: 4px solid #6366f1;">
        <span class="stat-label">My Orders Today</span>
        <div class="stat-value" style="color: #818cf8;">
            {{ number_format($todayCashierTransactions) }}
        </div>
        <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
            Completed checkouts processed today
        </span>
    </div>

    <!-- Store Overall Sales Today -->
    <div class="stat-card" style="border-left: 4px solid #f59e0b;">
        <span class="stat-label">Total Store Sales Today</span>
        <div class="stat-value" style="color: #fbbf24;">
            {{ $baseCurrency->symbol }}{{ number_format($todayStoreSales, 2) }}
        </div>
        <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
            All registers combined ({{ $todayStoreTransactions }} transactions)
        </span>
    </div>

    <!-- Payment Methods Summary -->
    <div class="stat-card" style="border-left: 4px solid #ec4899;">
        <span class="stat-label">Payment Breakdown</span>
        <div style="display: flex; flex-direction: column; gap: 0.35rem; margin-top: 0.5rem;">
            @php
                $cash = $paymentBreakdown->get('cash')->total_amount ?? 0;
                $mpesa = $paymentBreakdown->get('mpesa')->total_amount ?? 0;
                $card = $paymentBreakdown->get('card')->total_amount ?? 0;
            @endphp
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                <span style="color: var(--text-muted);">💵 Cash:</span>
                <strong style="color: var(--text-main);">{{ $baseCurrency->symbol }}{{ number_format($cash, 2) }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                <span style="color: var(--text-muted);">📱 M-Pesa:</span>
                <strong style="color: var(--text-main);">{{ $baseCurrency->symbol }}{{ number_format($mpesa, 2) }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                <span style="color: var(--text-muted);">💳 Card:</span>
                <strong style="color: var(--text-main);">{{ $baseCurrency->symbol }}{{ number_format($card, 2) }}</strong>
            </div>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- Best Selling Products Table -->
    <div class="glass-panel">
        <div class="panel-body" style="border-bottom: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">Best Selling Products</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Top items sold today.</p>
            </div>
        </div>

        <div class="custom-table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Product Item</th>
                        <th>Price</th>
                        <th>Units Sold</th>
                        <th>Total Value</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bestSellingToday as $item)
                        <tr>
                            <td>
                                <strong style="color: var(--text-main); display: block;">{{ $item->name }}</strong>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">SKU: {{ $item->sku ?: 'N/A' }}</span>
                            </td>
                            <td><span style="color: var(--text-main);">{{ $baseCurrency->symbol }}{{ number_format($item->price, 2) }}</span></td>
                            <td>
                                <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #34d399; font-weight: 700;">
                                    {{ number_format($item->total_qty) }} units
                                </span>
                            </td>
                            <td>
                                <strong style="color: var(--primary-hover);">
                                    {{ $baseCurrency->symbol }}{{ number_format($item->total_revenue, 2) }}
                                </strong>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">No items sold today yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Sales by Cashier -->
    <div class="glass-panel">
        <div class="panel-body" style="border-bottom: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">My Recent Receipts</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Last checkouts processed on your register today.</p>
            </div>
            <a href="{{ route('pos') }}" class="btn-secondary" style="font-size: 0.8rem; text-decoration: none; padding: 0.35rem 0.75rem;">+ New Sale</a>
        </div>

        <div class="custom-table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Receipt Ref</th>
                        <th>Time</th>
                        <th>Payment</th>
                        <th>Total</th>
                        @if(auth()->user()->role === 'admin')
                            <th>Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentCashierSales as $sale)
                        <tr>
                            <td>
                                <strong style="color: var(--text-main);">#{{ str_pad($sale->id, 5, '0', STR_PAD_LEFT) }}</strong>
                                @if($sale->customer_name)
                                    <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">{{ $sale->customer_name }}</span>
                                @endif
                            </td>
                            <td><span style="font-size: 0.8rem; color: var(--text-muted);">{{ $sale->created_at->format('H:i A') }}</span></td>
                            <td>
                                <span class="badge {{ $sale->payment_method === 'cash' ? 'badge-received' : ($sale->payment_method === 'mpesa' ? 'badge-pending' : '') }}" style="text-transform: uppercase; font-size: 0.7rem;">
                                    {{ $sale->payment_method }}
                                </span>
                            </td>
                            <td>
                                <strong style="color: #34d399;">
                                    {{ $baseCurrency->symbol }}{{ number_format($sale->total, 2) }}
                                </strong>
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
                            <td colspan="{{ auth()->user()->role === 'admin' ? 5 : 4 }}" style="text-align: center; color: var(--text-muted); padding: 2rem;">You haven't logged any sales today yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
