@extends('layouts.app')

@section('title', 'Quotations - Luxeshop POS')

@section('content')
<div class="page-header">
    <h1 class="page-title">Quotations (RFQs from Providers)</h1>
    <div class="page-actions">
        <a href="#create-quotation-form" class="btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
            <span>➕</span> Create Quotation
        </a>
    </div>
</div>

<div class="glass-panel" style="margin-bottom: 2rem; overflow: hidden;">
    <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
        <h2 style="font-size: 1.25rem; font-weight: 700;">Received Quotations</h2>
    </div>
    <div class="custom-table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Quote Number</th>
                    <th>Provider / Supplier</th>
                    <th>Items Count</th>
                    <th>Total Quoted</th>
                    <th>Valid Until</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quotations as $quote)
                    <tr>
                        <td><strong style="color: #fff;">{{ $quote->quote_number }}</strong></td>
                        <td>{{ $quote->provider_name }}</td>
                        <td>{{ $quote->items->count() }} item(s)</td>
                        <td style="font-weight: 600; color: var(--primary-hover);">
                            {{ number_format($quote->total_amount, 2) }}
                        </td>
                        <td>
                            <span style="color: {{ \Carbon\Carbon::parse($quote->valid_until)->isPast() ? '#ef4444' : 'var(--text-muted)' }}">
                                {{ $quote->valid_until->format('Y-m-d') }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $quote->status === 'approved' ? 'received' : ($quote->status === 'expired' ? 'cancelled' : 'pending') }}">
                                {{ strtoupper($quote->status) }}
                            </span>
                        </td>
                        <td>
                            <form action="{{ route('quotations.status', $quote) }}" method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
                                @csrf
                                <select name="status" class="form-input" style="padding: 0.35rem 0.6rem; font-size: 0.8rem; background: rgba(0,0,0,0.2); border: 1px solid var(--card-border); color: #fff; width: auto;" onchange="this.form.submit()">
                                    <option value="pending" {{ $quote->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ $quote->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="expired" {{ $quote->status === 'expired' ? 'selected' : '' }}>Expired</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                    <!-- Detail row -->
                    <tr style="background: rgba(255,255,255,0.01);">
                        <td colspan="7" style="padding: 0.5rem 2rem; border-top: none;">
                            <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; flex-wrap: wrap; gap: 1.5rem;">
                                @foreach($quote->items as $item)
                                    <div>
                                        📝 <strong>{{ $item->product_name }}</strong> 
                                        (Qty: {{ $item->quantity }} @ {{ number_format($item->price, 2) }})
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            No quotations registered. Use the form below to add one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="glass-panel" id="create-quotation-form" style="max-width: 800px; margin: 0 auto 3rem auto;">
    <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
        <h2 style="font-size: 1.25rem; font-weight: 700;">Add Quotation / RFQ</h2>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Record a quotation for products (even those not yet in database) from providers.</p>
    </div>
    <form action="{{ route('quotations.store') }}" method="POST" class="panel-body" style="display: flex; flex-direction: column; gap: 1.25rem;">
        @csrf
        
        <div class="grid-3">
            <div class="form-group" style="grid-column: span 1;">
                <label class="form-label" for="provider_name">Provider / Supplier Name *</label>
                <input class="form-input" type="text" id="provider_name" name="provider_name" required placeholder="e.g. Gucci Luxury Suppliers">
            </div>
            <div class="form-group" style="grid-column: span 1;">
                <label class="form-label" for="quote_number">Quote Number</label>
                <input class="form-input" type="text" id="quote_number" name="quote_number" placeholder="e.g. GQT-8910">
            </div>
            <div class="form-group" style="grid-column: span 1;">
                <label class="form-label" for="valid_until">Valid Until *</label>
                <input class="form-input" type="date" id="valid_until" name="valid_until" required min="{{ date('Y-m-d') }}">
            </div>
        </div>

        <div style="border: 1px solid var(--card-border); border-radius: var(--radius-sm); padding: 1rem; background: rgba(0,0,0,0.15);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--card-border); padding-bottom: 0.5rem;">
                <h3 style="font-size: 0.95rem; font-weight: 600; color: #fff;">Quoted Items</h3>
                <button type="button" class="btn-secondary" id="add-quote-row-btn" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; background: var(--primary); color: #fff; border: none;">➕ Add Row</button>
            </div>
            
            <div id="quote-items-container" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div class="quote-item-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 0.75rem; align-items: center;">
                    <div>
                        <input type="text" name="items[0][product_name]" required class="form-input" placeholder="Product name or description" style="padding: 0.7rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <input type="number" name="items[0][quantity]" min="1" required class="form-input" placeholder="Qty" style="padding: 0.7rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <input type="number" step="0.01" name="items[0][price]" min="0" required class="form-input" placeholder="Price" style="padding: 0.7rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <button type="button" class="btn-secondary remove-quote-row-btn" style="color: #ef4444; border-color: transparent; background: none; font-size: 1.2rem; cursor: pointer;">🗑️</button>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-primary" style="margin-top: 0.5rem; align-self: flex-start; width: auto; padding: 0.85rem 2rem;">Save Quotation</button>
    </form>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let rowIdx = 1;
        const container = document.getElementById('quote-items-container');
        const addBtn = document.getElementById('add-quote-row-btn');

        // Add item row
        addBtn.addEventListener('click', function() {
            const template = `
                <div class="quote-item-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 0.75rem; align-items: center;">
                    <div>
                        <input type="text" name="items[${rowIdx}][product_name]" required class="form-input" placeholder="Product name or description" style="padding: 0.7rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <input type="number" name="items[${rowIdx}][quantity]" min="1" required class="form-input" placeholder="Qty" style="padding: 0.7rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <input type="number" step="0.01" name="items[${rowIdx}][price]" min="0" required class="form-input" placeholder="Price" style="padding: 0.7rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <button type="button" class="btn-secondary remove-quote-row-btn" style="color: #ef4444; border-color: transparent; background: none; font-size: 1.2rem; cursor: pointer;">🗑️</button>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', template);
            rowIdx++;
        });

        // Remove item row
        container.addEventListener('click', function(e) {
            if(e.target.classList.contains('remove-quote-row-btn')) {
                const rows = container.querySelectorAll('.quote-item-row');
                if(rows.length > 1) {
                    e.target.closest('.quote-item-row').remove();
                } else {
                    alert('At least one item is required in the quotation.');
                }
            }
        });
    });
</script>
@endsection
