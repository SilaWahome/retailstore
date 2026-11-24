@php
    $billingTenant = Auth::user()?->tenant;
    if ($billingTenant) {
        $billingTenant->refresh();
        $monthlyFeeUgx = $billingTenant->effectiveMonthlyFeeUgx();
        $pendingBillUgx = $billingTenant->pendingBillAmountUgx();
        $payableUgx = $pendingBillUgx > 0 ? $pendingBillUgx : $monthlyFeeUgx;
        $isDue = $pendingBillUgx > 0;
    }
@endphp

@if($billingTenant && Auth::user()?->role !== 'superadmin')
<div class="glass-panel" style="margin-bottom: 1.5rem; border: 1px solid {{ $isDue ? 'rgba(245, 158, 11, 0.5)' : 'var(--card-border)' }};">
    <div class="panel-body" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1.25rem;">
        <div style="flex: 1; min-width: 260px;">
            <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                <h2 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin: 0;">
                    Subscription & Pending Bill
                </h2>
                <span class="badge {{ $billingTenant->subscription_status === 'active' ? 'badge-received' : 'badge-pending' }}" style="text-transform: uppercase;">
                    {{ $billingTenant->subscription_status ?? 'trial' }}
                </span>
            </div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.35rem;">
                Monthly Rate: <strong style="color: var(--text-main);">UGX {{ number_format($monthlyFeeUgx, 0) }}/mo</strong>
                (Base pay: UGX 20,000/mo)
                @if($billingTenant->subscription_ends_at)
                    • Valid Until: <strong style="color: var(--text-main);">{{ \Carbon\Carbon::parse($billingTenant->subscription_ends_at)->format('Y-m-d') }}</strong>
                @endif
            </p>
            <div style="margin-top: 0.65rem; display: inline-flex; align-items: center; gap: 0.6rem; padding: 0.5rem 0.9rem; border-radius: var(--radius-sm); background: {{ $isDue ? 'rgba(245, 158, 11, 0.14)' : 'rgba(16, 185, 129, 0.12)' }}; border: 1px solid {{ $isDue ? 'rgba(245, 158, 11, 0.35)' : 'rgba(16, 185, 129, 0.3)' }};">
                <span style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted);">Pending Bill:</span>
                <span style="font-size: 1.15rem; font-weight: 800; color: {{ $isDue ? '#fbbf24' : '#34d399' }};">
                    UGX {{ number_format($pendingBillUgx, 0) }}
                </span>
            </div>
        </div>

        <form id="sub-bill-pay-form" onsubmit="return submitSubscriptionBillPayment(event)" style="display: flex; flex-wrap: wrap; align-items: end; gap: 0.75rem;">
            <div class="form-group" style="margin-bottom: 0; min-width: 210px;">
                <label class="form-label" for="billing_momo_phone" style="font-size: 0.78rem; margin-bottom: 0.25rem;">MTN / Airtel MoMo Number</label>
                <input
                    type="text"
                    id="billing_momo_phone"
                    class="form-input"
                    value="{{ $billingTenant->phone }}"
                    placeholder="e.g. 0772123456"
                    required
                    style="padding: 0.6rem 0.8rem; font-size: 0.9rem;"
                >
            </div>
            <button
                type="submit"
                id="sub-bill-pay-btn"
                class="btn-primary"
                style="padding: 0.65rem 1.25rem; width: auto; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; font-weight: 700; cursor: pointer;"
            >
                {{ $isDue ? 'Pay Pending Bill (UGX ' . number_format($payableUgx, 0) . ')' : 'Renew 1 Month (UGX ' . number_format($payableUgx, 0) . ')' }}
            </button>
        </form>
    </div>
    <div id="sub-bill-status-box" style="display: none; padding: 0 1.25rem 1rem 1.25rem; font-size: 0.85rem; font-weight: 600;"></div>
</div>

<script>
function submitSubscriptionBillPayment(e) {
    e.preventDefault();
    const phone = document.getElementById('billing_momo_phone').value.trim();
    const btn = document.getElementById('sub-bill-pay-btn');
    const box = document.getElementById('sub-bill-status-box');
    const originalText = btn.textContent;

    btn.disabled = true;
    btn.textContent = 'Sending Prompt...';
    box.style.display = 'block';
    box.style.color = '#fbbf24';
    box.textContent = 'Sending Mobile Money prompt to ' + phone + '...';

    fetch('{{ route('billing.pay') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ phone_number: phone })
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (!ok || !data.success) {
            box.style.color = '#f87171';
            box.textContent = data.message || 'Could not initiate Mobile Money payment.';
            btn.disabled = false;
            btn.textContent = originalText;
            return;
        }

        box.style.color = '#60a5fa';
        box.textContent = data.message + ' Waiting for PIN confirmation...';
        pollSubscriptionBillStatus(data.transaction_id, btn, box, originalText, 0);
    })
    .catch(() => {
        box.style.color = '#f87171';
        box.textContent = 'Network error while sending Mobile Money prompt.';
        btn.disabled = false;
        btn.textContent = originalText;
    });

    return false;
}

function pollSubscriptionBillStatus(txId, btn, box, originalText, attempts) {
    if (attempts >= 30) {
        box.style.color = '#fbbf24';
        box.textContent = 'Still waiting for confirmation. Please approve the prompt on your phone and refresh.';
        btn.disabled = false;
        btn.textContent = originalText;
        return;
    }

    setTimeout(() => {
        fetch('{{ url('/billing/status') }}/' + txId, {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'complete') {
                box.style.color = '#34d399';
                box.textContent = data.message || 'Payment confirmed! Reloading...';
                setTimeout(() => window.location.reload(), 1200);
            } else if (data.status === 'failed') {
                box.style.color = '#f87171';
                box.textContent = data.message || 'Payment was declined or failed.';
                btn.disabled = false;
                btn.textContent = originalText;
            } else {
                pollSubscriptionBillStatus(txId, btn, box, originalText, attempts + 1);
            }
        })
        .catch(() => pollSubscriptionBillStatus(txId, btn, box, originalText, attempts + 1));
    }, 3000);
}
</script>
@endif
