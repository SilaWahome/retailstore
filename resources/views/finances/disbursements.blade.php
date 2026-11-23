@extends('layouts.app')

@section('title', 'Finances & Mobile Money Withdrawals')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 class="page-title">Mobile Money Withdrawals & Disbursements</h1>
        <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 0.25rem;">
            Withdraw your collected store funds directly to MTN or Airtel Uganda. Powered by ioTec Pay.
        </p>
    </div>
</div>

<!-- Financial Summary Cards -->
<div class="grid-2" style="margin-bottom: 2rem; gap: 1.5rem;">
    <div class="grid-2" style="gap: 1.5rem;">
        <!-- Available Wallet Balance -->
        <div class="glass-panel panel-body" style="display: flex; flex-direction: column; justify-content: space-between; min-height: 140px; background: var(--bg-panel); border-left: 4px solid var(--accent-emerald);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; color: var(--text-muted);">Available for Withdrawal</span>
                <span class="badge badge-received" style="font-size: 0.75rem; background: var(--accent-emerald-glow); color: var(--accent-emerald);">Ready</span>
            </div>
            <div style="margin-top: 0.75rem;">
                <h2 style="font-size: 2.2rem; font-weight: 800; color: var(--accent-emerald); line-height: 1;">UGX {{ number_format($availableBalance) }}</h2>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.4rem;">Net withdrawable sales balance</p>
            </div>
        </div>

        <!-- Total Collected -->
        <div class="glass-panel panel-body" style="display: flex; flex-direction: column; justify-content: space-between; min-height: 140px; background: var(--bg-panel); border-left: 4px solid var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; color: var(--text-muted);">Total Mobile Money Sales</span>
                <span class="badge" style="font-size: 0.75rem; background: rgba(139, 92, 246, 0.15); color: var(--primary);">Collections</span>
            </div>
            <div style="margin-top: 0.75rem;">
                <h2 style="font-size: 2.2rem; font-weight: 800; color: var(--primary); line-height: 1;">UGX {{ number_format($totalCollected) }}</h2>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.4rem;">Gross POS mobile money collected</p>
            </div>
        </div>
    </div>

    <div class="grid-2" style="gap: 1.5rem;">
        <!-- Total Withdrawn -->
        <div class="glass-panel panel-body" style="display: flex; flex-direction: column; justify-content: space-between; min-height: 140px; background: var(--bg-panel); border-left: 4px solid var(--accent-amber);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; color: var(--text-muted);">Total Disbursed</span>
                <span class="badge badge-pending" style="font-size: 0.75rem;">Payouts</span>
            </div>
            <div style="margin-top: 0.75rem;">
                <h2 style="font-size: 2.2rem; font-weight: 800; color: var(--accent-amber); line-height: 1;">UGX {{ number_format($totalWithdrawn) }}</h2>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.4rem;">Total withdrawn to mobile money</p>
            </div>
        </div>

        <!-- SaaS 5% Fee Retained -->
        <div class="glass-panel panel-body" style="display: flex; flex-direction: column; justify-content: space-between; min-height: 140px; background: var(--bg-panel);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; color: var(--text-muted);">SaaS Fee (5%)</span>
                <span class="badge" style="font-size: 0.75rem; background: rgba(255,255,255,0.05); color: var(--text-muted);">0.05 Platform Cut</span>
            </div>
            <div style="margin-top: 0.75rem;">
                <h2 style="font-size: 2.2rem; font-weight: 800; color: var(--text-muted); line-height: 1;">UGX {{ number_format($totalSaasFeesPaid) }}</h2>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.4rem;">Retained for SaaS superadmin</p>
            </div>
        </div>
    </div>
</div>

<div class="grid-2" style="align-items: start; gap: 2rem; margin-bottom: 3rem;">
    <!-- Withdrawal Form -->
    <div class="glass-panel panel-body" style="padding: 1.75rem;">
        <h2 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 0.25rem;">Request Mobile Money Withdrawal</h2>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">
            Disbursements are processed via ioTec Pay. A <strong>5% (0.05)</strong> platform charge is retained for the SaaS superadmin.
        </p>

        <form action="{{ route('finances.withdraw') }}" method="POST" id="withdrawal-form">
            @csrf

            <!-- Select Network -->
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label" style="font-weight: 600;">Recipient Network *</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0.4rem;">
                    <label style="display: flex; align-items: center; gap: 0.6rem; padding: 0.75rem 1rem; background: rgba(255,255,255,0.03); border: 2px solid var(--card-border); border-radius: var(--radius-sm); cursor: pointer; transition: all 0.2s;" id="label-mtn">
                        <input type="radio" name="network" value="MTN" checked onchange="updateNetworkStyle()" style="accent-color: #facc15;">
                        <div>
                            <strong style="color: #facc15; font-size: 0.95rem;">MTN Uganda</strong>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">MoMo (077, 078, 076)</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: center; gap: 0.6rem; padding: 0.75rem 1rem; background: rgba(255,255,255,0.03); border: 2px solid var(--card-border); border-radius: var(--radius-sm); cursor: pointer; transition: all 0.2s;" id="label-airtel">
                        <input type="radio" name="network" value="Airtel" onchange="updateNetworkStyle()" style="accent-color: #ef4444;">
                        <div>
                            <strong style="color: #ef4444; font-size: 0.95rem;">Airtel Uganda</strong>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">Airtel Money (070, 075, 074)</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Recipient Phone Number -->
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label" for="phone_number">Withdrawal Phone Number *</label>
                <input type="text" name="phone_number" id="phone_number" class="form-input" placeholder="e.g. 0771234567 or 256700000000" value="{{ old('phone_number', Auth::user()->tenant->phone ?? '') }}" required style="font-size: 1rem; padding: 0.7rem;">
                <small style="color: var(--text-muted); font-size: 0.78rem;">Must be a registered mobile money number on the selected network.</small>
            </div>

            <!-- Amount to Withdraw -->
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                    <label class="form-label" for="withdraw_amount" style="margin: 0;">Amount to Withdraw (UGX) *</label>
                    <button type="button" onclick="setWithdrawMax({{ $availableBalance }})" class="btn-secondary" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; color: var(--accent-emerald);">
                        Max (UGX {{ number_format($availableBalance) }})
                    </button>
                </div>
                <input type="number" step="100" min="1000" max="{{ $availableBalance }}" name="amount" id="withdraw_amount" class="form-input" placeholder="Min. 1,000" required oninput="calcWithdrawalFee()" style="font-size: 1.15rem; font-weight: 700; padding: 0.7rem; border-color: var(--primary);">
            </div>

            <!-- Live Calculation Breakdown Card -->
            <div style="background: rgba(255,255,255,0.03); border: 1px dashed var(--card-border); border-radius: var(--radius-sm); padding: 1.2rem; margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.88rem; margin-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Requested Amount:</span>
                    <strong id="preview-requested" style="color: var(--text-main);">UGX 0</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.88rem; margin-bottom: 0.5rem;">
                    <span style="color: var(--accent-coral);">SaaS Platform Fee (5% / 0.05):</span>
                    <strong id="preview-fee" style="color: var(--accent-coral);">- UGX 0</strong>
                </div>
                <div style="border-top: 1px solid var(--card-border); padding-top: 0.6rem; display: flex; justify-content: space-between; font-size: 1.05rem; font-weight: 700;">
                    <span style="color: var(--accent-emerald);">Net You Will Receive:</span>
                    <strong id="preview-net" style="color: var(--accent-emerald);">UGX 0</strong>
                </div>
            </div>

            <button type="submit" id="btn-submit-withdraw" class="btn-primary" style="width: 100%; padding: 0.9rem; font-size: 1rem; font-weight: 700; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; cursor: pointer;" {{ $availableBalance < 1000 ? 'disabled' : '' }}>
                💸 Withdraw via Mobile Money
            </button>
            @if($availableBalance < 1000)
                <p style="color: var(--accent-amber); font-size: 0.8rem; text-align: center; margin-top: 0.6rem;">
                    Minimum available balance required is UGX 1,000 to initiate a withdrawal.
                </p>
            @endif
        </form>
    </div>

    <!-- Quick Info & Instructions -->
    <div>
        <div class="glass-panel panel-body" style="margin-bottom: 1.5rem; padding: 1.5rem;">
            <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--text-main);">
                ℹ️ How Disbursements Work
            </h3>
            <ul style="color: var(--text-muted); font-size: 0.88rem; line-height: 1.7; padding-left: 1.25rem; margin: 0;">
                <li>Withdrawals are processed instantly via the <strong>ioTec Pay</strong> disbursement gateway.</li>
                <li>Supported networks: <strong>MTN Mobile Money</strong> and <strong>Airtel Money Uganda</strong>.</li>
                <li><strong>5% platform fee (.05)</strong> is deducted automatically to support platform operations.</li>
                <li>The net 95% is deposited directly into your provided mobile wallet.</li>
                <li>Keep track of all payout references for your financial reconciliation.</li>
            </ul>
        </div>

        <div class="glass-panel panel-body" style="padding: 1.5rem; background: rgba(16, 185, 129, 0.03); border-color: rgba(16, 185, 129, 0.2);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="font-size: 1.8rem;">🔒</div>
                <div>
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--accent-emerald); margin: 0;">Bank-Grade Security</h4>
                    <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.2rem;">
                        Licensed and regulated payment processing in Uganda.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Disbursements History Table -->
<div class="glass-panel" style="margin-bottom: 3rem; overflow: hidden;">
    <div class="panel-body" style="border-bottom: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">Disbursement / Payout History</h2>
        <span style="font-size: 0.82rem; color: var(--text-muted);">Showing recent withdrawals</span>
    </div>

    <div class="custom-table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Reference / ID</th>
                    <th>Date & Time</th>
                    <th>Network</th>
                    <th>Recipient Phone</th>
                    <th>Gross Amount</th>
                    <th>SaaS Fee (5%)</th>
                    <th>Net Disbursed</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($disbursements as $item)
                    <tr>
                        <td><code>{{ $item->external_id }}</code></td>
                        <td>{{ $item->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            @if(strtoupper($item->network) === 'MTN')
                                <span class="badge" style="background: rgba(250, 204, 21, 0.15); color: #facc15; border: 1px solid rgba(250, 204, 21, 0.3);">MTN MoMo</span>
                            @else
                                <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">Airtel Money</span>
                            @endif
                        </td>
                        <td><strong>{{ $item->phone_number }}</strong></td>
                        <td><strong>UGX {{ number_format($item->amount) }}</strong></td>
                        <td style="color: var(--accent-coral);">UGX {{ number_format($item->fee) }}</td>
                        <td style="color: var(--accent-emerald); font-weight: 700;">UGX {{ number_format($item->net_amount) }}</td>
                        <td>
                            @if($item->status === 'complete')
                                <span class="badge badge-received" style="background: var(--accent-emerald-glow); color: var(--accent-emerald);">Complete</span>
                            @elseif($item->status === 'pending')
                                <span class="badge badge-pending">Pending</span>
                            @else
                                <span class="badge badge-cancelled">Failed</span>
                            @endif
                        </td>
                        <td>
                            @if($item->status === 'pending')
                                <a href="{{ route('finances.check-status', $item) }}" class="btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.78rem;">
                                    🔄 Refresh Status
                                </a>
                            @else
                                <span style="font-size: 0.8rem; color: var(--text-muted);">Settled</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                            No disbursements recorded yet. Initiate your first withdrawal above.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($disbursements->hasPages())
        <div style="padding: 1rem; border-top: 1px solid var(--card-border);">
            {{ $disbursements->links() }}
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    function updateNetworkStyle() {
        const isMtn = document.querySelector('input[name="network"]:checked').value === 'MTN';
        const labelMtn = document.getElementById('label-mtn');
        const labelAirtel = document.getElementById('label-airtel');
        if (isMtn) {
            labelMtn.style.borderColor = '#facc15';
            labelAirtel.style.borderColor = 'var(--card-border)';
        } else {
            labelAirtel.style.borderColor = '#ef4444';
            labelMtn.style.borderColor = 'var(--card-border)';
        }
    }

    function setWithdrawMax(maxAmount) {
        document.getElementById('withdraw_amount').value = maxAmount;
        calcWithdrawalFee();
    }

    function calcWithdrawalFee() {
        const val = parseFloat(document.getElementById('withdraw_amount').value) || 0;
        const fee = Math.round(val * 0.05);
        const net = Math.max(0, val - fee);

        document.getElementById('preview-requested').textContent = 'UGX ' + val.toLocaleString();
        document.getElementById('preview-fee').textContent = '- UGX ' + fee.toLocaleString();
        document.getElementById('preview-net').textContent = 'UGX ' + net.toLocaleString();
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateNetworkStyle();
        calcWithdrawalFee();
    });
</script>
@endsection
