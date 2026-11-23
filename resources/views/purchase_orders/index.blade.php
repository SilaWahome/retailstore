@extends('layouts.app')

@section('title', 'Purchase Orders (XPOs) - Luxeshop POS')

@section('content')
<div class="page-header">
    <h1 class="page-title">Purchase Orders (XPOs) & Vendor Credit</h1>
    <div class="page-actions">
        <a href="#create-po-form" class="btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; background: var(--primary);">
            Create Purchase Order
        </a>
    </div>
</div>

<div class="glass-panel" style="margin-bottom: 2.5rem; overflow: hidden;">
    <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Purchase Orders & Supplier Liabilities</h2>
    </div>
    <div class="custom-table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Supplier</th>
                    <th>Items</th>
                    <th>Total Cost</th>
                    <th>Amount Paid</th>
                    <th>Vendor Balance</th>
                    <th>Payment Status</th>
                    <th>Stock Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseOrders as $po)
                    @php $vendorBal = max(0, $po->total_cost - $po->amount_paid); @endphp
                    <tr>
                        <td><strong style="color: var(--text-main);">{{ $po->po_number }}</strong></td>
                        <td><strong>{{ $po->supplier_name }}</strong></td>
                        <td>{{ $po->items->count() }} item(s)</td>
                        <td style="font-weight: 600; color: var(--text-main);">
                            {{ $baseCurrency->symbol }}{{ number_format($po->total_cost, 2) }}
                        </td>
                        <td style="font-weight: 600; color: var(--accent-emerald);">
                            {{ $baseCurrency->symbol }}{{ number_format($po->amount_paid, 2) }}
                        </td>
                        <td style="font-weight: 700; color: {{ $vendorBal > 0 ? 'var(--accent-coral)' : 'var(--accent-emerald)' }};">
                            {{ $baseCurrency->symbol }}{{ number_format($vendorBal, 2) }}
                        </td>
                        <td>
                            @if($po->payment_status === 'paid')
                                <span class="badge badge-received">PAID IN FULL</span>
                            @elseif($po->payment_status === 'partial')
                                <span class="badge badge-pending">PARTIAL PAID</span>
                            @else
                                <span class="badge badge-cancelled">UNPAID CREDIT</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $po->status }}">
                                {{ strtoupper($po->status) }}
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                                @if($po->payment_status !== 'paid')
                                    <button type="button" onclick="openPayVendorModal({{ $po->id }}, '{{ addslashes($po->po_number) }}', '{{ addslashes($po->supplier_name) }}', {{ $vendorBal }})" class="btn-primary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; width: auto; background: var(--accent-amber); border: none; margin: 0;">
                                        Pay Vendor
                                    </button>
                                @endif

                                @if($po->status === 'pending')
                                    <form action="{{ route('purchase_orders.receive', $po) }}" method="POST" onsubmit="return confirm('Marking as Received will add PO quantities to active stock. Proceed?');" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn-secondary" style="color: var(--accent-emerald); border-color: rgba(16, 185, 129, 0.2); background: rgba(16, 185, 129, 0.05); padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                                            Receive Stock
                                        </button>
                                    </form>
                                    <form action="{{ route('purchase_orders.cancel', $po) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this PO?');" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn-secondary" style="color: var(--accent-coral); border-color: rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.05); padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                                            Cancel
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    <!-- Dropdown Details of items inside the PO -->
                    <tr style="background: rgba(255,255,255,0.01);">
                        <td colspan="9" style="padding: 0.5rem 2rem; border-top: none;">
                            <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; flex-wrap: wrap; gap: 1.5rem;">
                                @foreach($po->items as $item)
                                    <div>
                                        <strong>{{ $item->product->name ?? 'Deleted Product' }}</strong> 
                                        (Qty: {{ $item->quantity }} @ {{ $baseCurrency->symbol }}{{ number_format($item->cost, 2) }})
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            No purchase orders found. Setup one using the form below.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="glass-panel" id="create-po-form" style="max-width: 800px; margin: 0 auto 3rem auto;">
    <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Create Purchase Order (XPO)</h2>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Select products, specify supplier cost, quantities, and initial deposit paid to vendor.</p>
    </div>
    <form action="{{ route('purchase_orders.store') }}" method="POST" class="panel-body" style="display: flex; flex-direction: column; gap: 1.25rem;">
        @csrf
        
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label" for="supplier_name">Supplier / Provider Name *</label>
                <input class="form-input" type="text" id="supplier_name" name="supplier_name" required placeholder="e.g. Nestle Distribution">
            </div>
            <div class="form-group">
                <label class="form-label" for="po_number">PO Number (Optional)</label>
                <input class="form-input" type="text" id="po_number" name="po_number" placeholder="e.g. XPO-5091">
            </div>
            <div class="form-group">
                <label class="form-label" for="amount_paid">Initial Payment to Vendor ({{ $baseCurrency->symbol }})</label>
                <input class="form-input" type="number" step="0.01" min="0" id="amount_paid" name="amount_paid" value="0.00" placeholder="0.00">
            </div>
        </div>

        <div style="border: 1px solid var(--card-border); border-radius: var(--radius-sm); padding: 1rem; background: var(--bg-panel);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--card-border); padding-bottom: 0.5rem;">
                <h3 style="font-size: 0.95rem; font-weight: 600; color: var(--text-main);">Order Items</h3>
                <button type="button" class="btn-secondary" id="add-item-row-btn" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; background: var(--primary); color: #fff; border: none;">Add Row</button>
            </div>
            
            <div id="po-items-container" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <!-- Row template inside container -->
                <div class="po-item-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 0.75rem; align-items: center;">
                    <div>
                        <select name="items[0][product_id]" required class="form-input" style="padding: 0.7rem; font-size: 0.9rem;">
                            <option value="">Select Product...</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}" data-cost="{{ $prod->cost }}">{{ $prod->name }} (Cost: {{ $baseCurrency->symbol }}{{ number_format($prod->cost, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <input type="number" name="items[0][quantity]" min="1" required class="form-input" placeholder="Qty" style="padding: 0.7rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <input type="number" step="0.01" name="items[0][cost]" min="0" required class="form-input" placeholder="Unit Cost" style="padding: 0.7rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <button type="button" class="btn-secondary remove-row-btn" style="color: #ef4444; border-color: transparent; background: none; font-size: 1.2rem; cursor: pointer;">🗑️</button>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-primary" style="margin-top: 0.5rem; align-self: flex-start; width: auto; padding: 0.85rem 2rem; background: var(--primary); border: none;">Save Purchase Order</button>
    </form>
</div>

<!-- Pay Vendor Installment Modal -->
<div id="pay-vendor-modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); align-items: center; justify-content: center;">
    <div class="glass-panel" style="width: 100%; max-width: 450px; background: var(--select-bg); margin: auto; padding: 2rem; border-radius: var(--radius-md); box-shadow: 0 10px 30px rgba(0,0,0,0.5); display: flex; flex-direction: column; gap: 1.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--card-border); padding-bottom: 1rem;">
            <h2 style="font-size: 1.2rem; font-weight: 700; color: var(--text-main);">Record Vendor Payment</h2>
            <button type="button" onclick="closePayVendorModal()" class="btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; border: none; background: transparent; color: var(--text-muted);">✕ Close</button>
        </div>
        <form id="pay-vendor-form" method="POST" style="display: flex; flex-direction: column; gap: 1.25rem;">
            @csrf
            
            <div>
                <p style="font-size: 0.9rem; color: var(--text-main); font-weight: 600;" id="modal-po-title"></p>
                <p style="font-size: 0.85rem; color: var(--accent-coral); font-weight: 700; margin-top: 0.25rem;" id="modal-vendor-bal"></p>
            </div>

            <div class="form-group">
                <label class="form-label" for="payment_amount">Payment Amount Received by Vendor ({{ $baseCurrency->symbol }}) *</label>
                <input class="form-input" type="number" step="0.01" min="0.01" id="payment_amount" name="payment_amount" required placeholder="e.g. 500000">
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 0.5rem;">
                <button type="button" onclick="closePayVendorModal()" class="btn-secondary" style="width: auto;">Cancel</button>
                <button type="submit" class="btn-primary" style="width: auto; background: var(--accent-emerald); border: none;">Record Payment</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openPayVendorModal(poId, poNum, supplier, balance) {
        document.getElementById('pay-vendor-form').action = `/purchase-orders/${poId}/pay-vendor`;
        document.getElementById('modal-po-title').textContent = `PO #${poNum} — Supplier: ${supplier}`;
        document.getElementById('modal-vendor-bal').textContent = `Remaining Outstanding Vendor Balance: {{ $baseCurrency->symbol }}${balance.toFixed(2)}`;
        document.getElementById('payment_amount').value = balance.toFixed(2);
        document.getElementById('pay-vendor-modal').style.display = 'flex';
    }

    function closePayVendorModal() {
        document.getElementById('pay-vendor-modal').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function() {
        let rowIdx = 1;
        const container = document.getElementById('po-items-container');
        const addBtn = document.getElementById('add-item-row-btn');

        // Autofill unit cost on product select
        container.addEventListener('change', function(e) {
            if(e.target.tagName === 'SELECT') {
                const selectedOpt = e.target.options[e.target.selectedIndex];
                const cost = selectedOpt.getAttribute('data-cost');
                if (cost) {
                    const row = e.target.closest('.po-item-row');
                    const costInput = row.querySelector('input[name*="[cost]"]');
                    if (costInput) costInput.value = parseFloat(cost).toFixed(2);
                }
            }
        });

        // Add item row
        addBtn.addEventListener('click', function() {
            const template = `
                <div class="po-item-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 0.75rem; align-items: center;">
                    <div>
                        <select name="items[${rowIdx}][product_id]" required class="form-input" style="padding: 0.7rem; font-size: 0.9rem;">
                            <option value="">Select Product...</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}" data-cost="{{ $prod->cost }}">{{ $prod->name }} (Cost: {{ $baseCurrency->symbol }}{{ number_format($prod->cost, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <input type="number" name="items[${rowIdx}][quantity]" min="1" required class="form-input" placeholder="Qty" style="padding: 0.7rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <input type="number" step="0.01" name="items[${rowIdx}][cost]" min="0" required class="form-input" placeholder="Unit Cost" style="padding: 0.7rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <button type="button" class="btn-secondary remove-row-btn" style="color: #ef4444; border-color: transparent; background: none; font-size: 1.2rem; cursor: pointer;">🗑️</button>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', template);
            rowIdx++;
        });

        // Remove item row
        container.addEventListener('click', function(e) {
            if(e.target.classList.contains('remove-row-btn')) {
                const rows = container.querySelectorAll('.po-item-row');
                if(rows.length > 1) {
                    e.target.closest('.po-item-row').remove();
                } else {
                    alert('At least one item is required in the Purchase Order.');
                }
            }
        });
    });
</script>
@endsection
