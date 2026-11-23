@extends('layouts.app')

@section('title', 'SaaS Platform Controller - Retail POS')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 class="page-title">SaaS Platform Controller</h1>
        <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 0.25rem;">
            Central platform operations, client subscriptions, financial collections (UGX), and audit records.
        </p>
    </div>
</div>

<!-- Financial & Platform Metrics Grid -->
<div class="grid-2" style="margin-bottom: 2rem; gap: 1.5rem;">
    <!-- Row 1: Core SaaS Metrics -->
    <div class="grid-2" style="gap: 1.5rem;">
        <!-- Total Tenants -->
        <div class="glass-panel panel-body" style="display: flex; flex-direction: column; justify-content: space-between; min-height: 135px; background: var(--bg-panel); border-left: 4px solid var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; color: var(--text-muted);">Total Clients</span>
                <span class="badge badge-received" style="font-size: 0.75rem;">Platform</span>
            </div>
            <div style="margin-top: 0.75rem;">
                <h2 style="font-size: 2.2rem; font-weight: 800; color: var(--text-main); line-height: 1;">{{ $totalTenants }}</h2>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.4rem;">Registered boutique accounts</p>
            </div>
        </div>

        <!-- Total Admins -->
        <div class="glass-panel panel-body" style="display: flex; flex-direction: column; justify-content: space-between; min-height: 135px; background: var(--bg-panel); border-left: 4px solid #6366f1;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; color: var(--text-muted);">Administrators</span>
                <span class="badge badge-received" style="font-size: 0.75rem;">Verified</span>
            </div>
            <div style="margin-top: 0.75rem;">
                <h2 style="font-size: 2.2rem; font-weight: 800; color: var(--text-main); line-height: 1;">{{ $totalAdmins }}</h2>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.4rem;">Active boutique managers</p>
            </div>
        </div>
    </div>

    <div class="grid-2" style="gap: 1.5rem;">
        <!-- ioTec UGX Mobile Money Collected -->
        <div class="glass-panel panel-body" style="display: flex; flex-direction: column; justify-content: space-between; min-height: 135px; background: var(--bg-panel); border-left: 4px solid var(--accent-emerald);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; color: var(--text-muted);">Mobile Money Collections</span>
                <span class="badge" style="font-size: 0.75rem; background: rgba(16, 185, 129, 0.15); color: var(--accent-emerald);">ioTec Pay</span>
            </div>
            <div style="margin-top: 0.75rem;">
                <h2 style="font-size: 2rem; font-weight: 800; color: var(--accent-emerald); line-height: 1;">UGX {{ number_format($totalIotecCollections) }}</h2>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.4rem;">Total POS collections received</p>
            </div>
        </div>

        <!-- SaaS Superadmin Commission (5%) -->
        <div class="glass-panel panel-body" style="display: flex; flex-direction: column; justify-content: space-between; min-height: 135px; background: var(--bg-panel); border-left: 4px solid var(--accent-amber);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; color: var(--text-muted);">SaaS Revenue (5% Fees)</span>
                <span class="badge badge-pending" style="font-size: 0.75rem;">0.05 Share</span>
            </div>
            <div style="margin-top: 0.75rem;">
                <h2 style="font-size: 2rem; font-weight: 800; color: var(--accent-amber); line-height: 1;">UGX {{ number_format($totalIotecSaasFees) }}</h2>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.4rem;">Earned from store withdrawals</p>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Stats Row -->
<div class="grid-2" style="margin-bottom: 2.5rem; gap: 1.5rem;">
    <!-- Gross Sales -->
    <div class="glass-panel panel-body" style="padding: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Platform Gross Volume</span>
            <div style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin-top: 0.25rem;">${{ number_format($grossSales, 2) }}</div>
        </div>
        <div style="text-align: right;">
            <span class="badge badge-received">Catalog</span>
            <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.25rem;">{{ $totalProducts }} SKUs online</div>
        </div>
    </div>

    <!-- Soft Deletes Audit Summary -->
    <div class="glass-panel panel-body" style="padding: 1.25rem; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid var(--accent-coral);">
        <div>
            <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Soft-Deleted Audit Trail</span>
            <div style="font-size: 1.6rem; font-weight: 800; color: var(--accent-coral); margin-top: 0.25rem;">{{ $totalDeletedSalesCount }} Voided Transactions</div>
        </div>
        <div style="text-align: right;">
            <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #f87171;">1-Year Validity</span>
            <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.25rem;">${{ number_format($totalDeletedSalesAmount, 2) }} preserved in log</div>
        </div>
    </div>
</div>

<!-- SaaS Platform Visual Analytics Charts -->
<div class="grid-2" style="margin-bottom: 2.5rem; gap: 1.5rem;">
    <!-- Subscriptions Package Shares Chart -->
    <div class="glass-panel panel-body" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; min-height: 300px;">
        <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 0.5rem;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-main);">Subscription Packages Breakdown</h3>
        </div>
        <div style="flex-grow: 1; position: relative; display: flex; align-items: center; justify-content: center;">
            <canvas id="packageDistributionChart" style="max-width: 240px; max-height: 240px;"></canvas>
        </div>
    </div>

    <!-- Client Tickets Status Breakdown -->
    <div class="glass-panel panel-body" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; min-height: 300px;">
        <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 0.5rem;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-main);">Client Tickets Statuses</h3>
        </div>
        <div style="flex-grow: 1; position: relative; display: flex; align-items: center; justify-content: center;">
            <canvas id="ticketsDistributionChart" style="max-width: 240px; max-height: 240px;"></canvas>
        </div>
    </div>
</div>

<!-- SaaS Registered Clients (Tenants) & Instant Subscription Renewal -->
<div class="glass-panel" style="margin-bottom: 3rem; overflow: hidden;">
    <div class="panel-body" style="border-bottom: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <div>
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">Manage Clients & Subscriptions</h2>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.2rem;">
                Standard validity is <strong>1 Year</strong>. Click <strong>⚡ Renew (1 Yr)</strong> for instant 1-year activation.
            </p>
        </div>
    </div>
    
    <div class="custom-table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Shop / Client</th>
                    <th>Legal Entity</th>
                    <th>Location</th>
                    <th>Plan</th>
                    <th>Expires At</th>
                    <th>Status</th>
                    <th>Quick Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenantsList as $ten)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(255,255,255,0.03); border: 1px solid var(--card-border); display: flex; align-items: center; justify-content: center;">
                                    @if($ten->logo)
                                        <img src="{{ asset('storage/' . $ten->logo) }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        <span style="font-size: 1rem; color: var(--text-muted);">S</span>
                                    @endif
                                </div>
                                <div>
                                    <strong style="color: var(--text-main); font-size: 0.9rem;">{{ $ten->name }}</strong>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $ten->phone }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $ten->business_name ?: 'Sole Proprietor' }}</td>
                        <td>{{ $ten->location ?: 'Online' }}</td>
                        <td>
                            <span class="badge" style="background: rgba(139, 92, 246, 0.1); color: #a78bfa; border: 1px solid rgba(139,92,246,0.2);">
                                {{ strtoupper($ten->subscription_package) }}
                            </span>
                        </td>
                        <td>
                            @if($ten->subscription_ends_at)
                                @php
                                    $endsAt = \Carbon\Carbon::parse($ten->subscription_ends_at);
                                    $isPast = $endsAt->isPast();
                                @endphp
                                <span style="font-size: 0.85rem; font-weight: 600; color: {{ $isPast ? 'var(--accent-coral)' : 'var(--text-main)' }};">
                                    {{ $endsAt->format('Y-m-d') }}
                                    @if($isPast)
                                        <small style="display: block; font-size: 0.72rem; color: var(--accent-coral);">Expired</small>
                                    @endif
                                </span>
                            @else
                                <span style="color: var(--text-muted);">No expiry set</span>
                            @endif
                        </td>
                        <td>
                            @if($ten->subscription_status === 'active' || $ten->subscription_status === 'trial')
                                <span class="badge badge-received" style="background: var(--accent-emerald-glow); color: var(--accent-emerald);">Active</span>
                            @elseif($ten->subscription_status === 'suspended')
                                <span class="badge badge-cancelled">Suspended</span>
                            @else
                                <span class="badge badge-pending">Expired</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap;">
                                <!-- Instant 1-Year Renewal Button -->
                                <form action="{{ route('saas.clients.renew', $ten) }}" method="POST" style="margin: 0;">
                                    @csrf
                                    <button type="submit" class="btn-primary" style="padding: 0.35rem 0.65rem; font-size: 0.78rem; width: auto; margin: 0; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; font-weight: 700;" title="Instantly activate and extend subscription for 1 Year">
                                        ⚡ Renew (1 Yr)
                                    </button>
                                </form>

                                <!-- Suspend / Activate Toggle -->
                                <form action="{{ route('saas.clients.status', $ten) }}" method="POST" style="margin: 0;">
                                    @csrf
                                    @if($ten->subscription_status === 'active' || $ten->subscription_status === 'trial')
                                        <button type="submit" class="btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.78rem; width: auto; margin: 0; color: #fff; background: var(--accent-coral); border: none;" title="Suspend store access">
                                            Suspend
                                        </button>
                                    @else
                                        <button type="submit" class="btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.78rem; width: auto; margin: 0; color: #fff; background: var(--accent-emerald); border: none;" title="Activate store access for 1 Year">
                                            ⚡ Activate
                                        </button>
                                    @endif
                                </form>

                                <!-- Custom Edit Form -->
                                <form action="{{ route('saas.clients.subscription', $ten) }}" method="POST" style="display: flex; gap: 0.25rem; align-items: center; margin: 0;">
                                    @csrf
                                    <select name="subscription_package" class="form-input" style="padding: 0.3rem 0.4rem; font-size: 0.75rem; width: auto; background: var(--bg-panel); color: var(--text-main); height: auto; margin-bottom: 0;">
                                        <option value="basic" {{ $ten->subscription_package === 'basic' ? 'selected' : '' }}>Basic</option>
                                        <option value="premium" {{ $ten->subscription_package === 'premium' ? 'selected' : '' }}>Premium</option>
                                        <option value="enterprise" {{ $ten->subscription_package === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                                    </select>
                                    
                                    <input type="date" name="subscription_ends_at" class="form-input" value="{{ $ten->subscription_ends_at ? \Carbon\Carbon::parse($ten->subscription_ends_at)->format('Y-m-d') : '' }}" required style="padding: 0.3rem; font-size: 0.75rem; width: auto; background: var(--bg-panel); color: var(--text-main); height: auto; margin-bottom: 0;">
                                    
                                    <button type="submit" class="btn-primary" style="padding: 0.3rem 0.55rem; font-size: 0.75rem; width: auto; margin: 0; background: var(--primary);">Save</button>
                                </form>

                                <!-- Login as Tenant Impersonation -->
                                <form action="{{ route('saas.impersonate', $ten) }}" method="POST" style="margin: 0;">
                                    @csrf
                                    <button type="submit" class="btn-primary" style="padding: 0.35rem 0.6rem; font-size: 0.78rem; width: auto; margin: 0; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border: none; font-weight: 600;" title="Log in to this tenant's admin account">
                                        🔑 Shop
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No registered clients found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- SOFT-DELETED TRANSACTIONS AUDIT LOG (1-YEAR VALIDITY RETENTION) -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div class="glass-panel" style="margin-bottom: 3rem; overflow: hidden; border-top: 3px solid var(--accent-coral);">
    <div class="panel-body" style="border-bottom: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <div>
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <span>🗑️ Soft-Deleted Items Audit Trail</span>
                <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">Validity: 1 Year Retention</span>
            </h2>
            <p style="color: var(--text-muted); font-size: 0.82rem; margin-top: 0.2rem;">
                All transactions voided across client stores are permanently archived here for 1 year with audit reasons and reprintable receipts.
            </p>
        </div>
        <div>
            <span class="badge badge-received" style="font-size: 0.8rem; background: rgba(255,255,255,0.05);">
                {{ $totalDeletedSalesCount }} Voided Transactions Total
            </span>
        </div>
    </div>

    <div class="custom-table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Receipt #</th>
                    <th>Client Boutique</th>
                    <th>Deleted At</th>
                    <th>Deleted By</th>
                    <th>Audit Reason</th>
                    <th>Audit Validity (1 Yr)</th>
                    <th>Gross Total</th>
                    <th>Items Sold</th>
                    <th>Receipt</th>
                </tr>
            </thead>
            <tbody>
                @forelse($softDeletedSales as $deletedSale)
                    <tr>
                        <td>
                            <strong style="color: var(--accent-coral);">#REC-{{ $deletedSale->id }}</strong>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $deletedSale->created_at->format('Y-m-d') }}</div>
                        </td>
                        <td>
                            <strong>{{ $deletedSale->tenant->name ?? 'N/A' }}</strong>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $deletedSale->branch->name ?? 'Main' }}</div>
                        </td>
                        <td>
                            <span style="font-size: 0.85rem; font-weight: 600;">{{ $deletedSale->deleted_at->format('Y-m-d H:i') }}</span>
                        </td>
                        <td>
                            <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-main);">
                                {{ $deletedSale->deletedByUser->name ?? 'Store Admin' }}
                            </div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">
                                {{ $deletedSale->deletedByUser->email ?? '' }}
                            </div>
                        </td>
                        <td style="max-width: 250px;">
                            <span style="font-size: 0.83rem; color: var(--text-muted); font-style: italic; display: block; line-height: 1.4;">
                                "{{ $deletedSale->deletion_reason ?? 'No reason stated' }}"
                            </span>
                        </td>
                        <td>
                            <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: var(--accent-emerald); font-size: 0.75rem; border: 1px solid rgba(16, 185, 129, 0.2);">
                                Valid until {{ $deletedSale->deleted_at->addYear()->format('Y-m-d') }}
                            </span>
                        </td>
                        <td>
                            <strong style="color: var(--accent-coral); font-size: 0.95rem;">
                                {{ $deletedSale->currency_code }} {{ number_format($deletedSale->total, 2) }}
                            </strong>
                        </td>
                        <td>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">
                                {{ $deletedSale->items->count() }} item(s)
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('saas.sales.print-deleted', $deletedSale->id) }}" target="_blank" class="btn-primary" style="padding: 0.35rem 0.7rem; font-size: 0.78rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem; background: var(--primary);">
                                🖨️ Print Receipt
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                            No soft-deleted transactions logged across boutiques.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($softDeletedSales->hasPages())
        <div style="padding: 1rem; border-top: 1px solid var(--card-border);">
            {{ $softDeletedSales->appends(request()->query())->links() }}
        </div>
    @endif
</div>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- IOTEC MOBILE MONEY TRANSACTIONS HUB (COLLECTIONS & DISBURSEMENTS) -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div class="glass-panel" style="margin-bottom: 3rem; overflow: hidden; border-top: 3px solid var(--accent-emerald);">
    <div class="panel-body" style="border-bottom: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <div>
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <span>📱 ioTec Mobile Money Hub (UGX)</span>
                <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: var(--accent-emerald);">MTN & Airtel Uganda</span>
            </h2>
            <p style="color: var(--text-muted); font-size: 0.82rem; margin-top: 0.2rem;">
                All Mobile Money POS collections (+500 UGX fee) and Store Admin withdrawals (5% platform commission retained).
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: var(--accent-emerald); font-size: 0.8rem;">
                Collected: UGX {{ number_format($totalIotecCollections) }}
            </span>
            <span class="badge" style="background: rgba(245, 158, 11, 0.1); color: var(--accent-amber); font-size: 0.8rem;">
                Platform 5%: UGX {{ number_format($totalIotecSaasFees) }}
            </span>
        </div>
    </div>

    <div class="custom-table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Reference / External ID</th>
                    <th>Date</th>
                    <th>Shop / Boutique</th>
                    <th>Transaction Type</th>
                    <th>Network & Phone</th>
                    <th>Gross (UGX)</th>
                    <th>Fee / SaaS Cut</th>
                    <th>Net Amount (UGX)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($iotecTransactions as $tx)
                    <tr>
                        <td>
                            <code>{{ $tx->external_id }}</code>
                            @if($tx->sale_id)
                                <div style="font-size: 0.72rem; color: var(--text-muted);">Sale #{{ $tx->sale_id }}</div>
                            @endif
                        </td>
                        <td>{{ $tx->created_at->format('Y-m-d H:i') }}</td>
                        <td><strong>{{ $tx->tenant->name ?? 'Platform' }}</strong></td>
                        <td>
                            @if($tx->type === 'collection')
                                <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: var(--accent-emerald);">
                                    📥 POS Collection
                                </span>
                            @else
                                <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: var(--accent-amber);">
                                    📤 Store Withdrawal
                                </span>
                            @endif
                        </td>
                        <td>
                            <div style="font-size: 0.88rem; font-weight: 600;">{{ $tx->phone_number }}</div>
                            <span class="badge" style="font-size: 0.7rem; background: {{ strtoupper($tx->network) === 'MTN' ? 'rgba(250, 204, 21, 0.15)' : 'rgba(239, 68, 68, 0.15)' }}; color: {{ strtoupper($tx->network) === 'MTN' ? '#facc15' : '#f87171' }};">
                                {{ $tx->network }}
                            </span>
                        </td>
                        <td><strong>UGX {{ number_format($tx->amount) }}</strong></td>
                        <td style="color: {{ $tx->type === 'disbursement' ? 'var(--accent-amber)' : 'var(--text-muted)' }}; font-weight: 600;">
                            @if($tx->type === 'disbursement')
                                UGX {{ number_format($tx->fee) }} (5%)
                            @else
                                + UGX {{ number_format($tx->fee) }} (Surcharge)
                            @endif
                        </td>
                        <td style="font-weight: 700; color: var(--accent-emerald);">
                            UGX {{ number_format($tx->net_amount) }}
                        </td>
                        <td>
                            @if($tx->status === 'complete')
                                <span class="badge badge-received" style="background: var(--accent-emerald-glow); color: var(--accent-emerald);">Complete</span>
                            @elseif($tx->status === 'pending')
                                <span class="badge badge-pending">Pending</span>
                            @else
                                <span class="badge badge-cancelled">Failed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                            No ioTec mobile money transactions recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($iotecTransactions->hasPages())
        <div style="padding: 1rem; border-top: 1px solid var(--card-border);">
            {{ $iotecTransactions->appends(request()->query())->links() }}
        </div>
    @endif
</div>

<!-- Support Tickets (from clients to SaaS) -->
<div class="glass-panel" style="margin-bottom: 2.5rem; overflow: hidden;">
    <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
        <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">Pending Support Tickets from Clients</h2>
    </div>
    <div class="custom-table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Date</th>
                    <th>Client Shop</th>
                    <th>Submitted By</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ticketsList as $ticket)
                    <tr>
                        <td><code>#{{ $ticket->id }}</code></td>
                        <td>{{ $ticket->created_at->format('Y-m-d H:i') }}</td>
                        <td><strong>{{ $ticket->tenant->name ?? 'N/A' }}</strong></td>
                        <td>{{ $ticket->user->name ?? 'System' }} ({{ $ticket->user->email ?? '' }})</td>
                        <td><strong>{{ $ticket->subject }}</strong></td>
                        <td><span style="font-size: 0.85rem; color: var(--text-muted);">{{ $ticket->message }}</span></td>
                        <td>
                            @if($ticket->status === 'open')
                                <span class="badge badge-pending">Open</span>
                            @else
                                <span class="badge badge-received" style="background: var(--accent-emerald-glow); color: var(--accent-emerald);">Resolved</span>
                            @endif
                        </td>
                        <td>
                            @if($ticket->status === 'open')
                                <form action="{{ route('saas.tickets.resolve', $ticket) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn-primary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; width: auto; margin: 0; background: var(--accent-emerald);">Resolve</button>
                                </form>
                            @else
                                <span style="font-size: 0.8rem; color: var(--text-muted);">No action needed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">No support tickets logged.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // 1. Subscription Package Share
        const packageRaw = @json($packageDistribution);
        const pkgLabels = packageRaw.map(item => (item.subscription_package || 'Basic').toUpperCase());
        const pkgData = packageRaw.map(item => parseInt(item.count));

        const ctx1 = document.getElementById('packageDistributionChart');
        if (ctx1) {
            new Chart(ctx1.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: pkgLabels,
                    datasets: [{
                        data: pkgData,
                        backgroundColor: ['#8b5cf6', '#10b981', '#f59e0b'],
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
        }

        // 2. Tickets Status Share
        const ticketsRaw = @json($ticketStatusDistribution);
        const tktLabels = ticketsRaw.map(item => (item.status || 'Open').toUpperCase());
        const tktData = ticketsRaw.map(item => parseInt(item.count));

        const ctx2 = document.getElementById('ticketsDistributionChart');
        if (ctx2) {
            new Chart(ctx2.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: tktLabels,
                    datasets: [{
                        data: tktData,
                        backgroundColor: ['#f59e0b', '#10b981', '#ef4444'],
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
        }
    });
</script>
@endsection
