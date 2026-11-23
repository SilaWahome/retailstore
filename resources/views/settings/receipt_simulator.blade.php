@extends('layouts.app')

@section('title', 'Receipt Simulator')

@section('styles')
<style>
/* ===== SIMULATOR LAYOUT ===== */
.sim-layout {
    display: grid;
    grid-template-columns: 420px 1fr;
    gap: 1.5rem;
    align-items: flex-start;
}
@media (max-width: 960px) {
    .sim-layout { grid-template-columns: 1fr; }
}

/* ===== CONTROL PANEL ===== */
.sim-controls {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
.sim-section {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-md);
    overflow: hidden;
}
.sim-section-header {
    padding: 0.85rem 1.1rem;
    border-bottom: 1px solid var(--card-border);
    background: rgba(139, 92, 246, 0.06);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.sim-section-header h3 {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text-main);
    margin: 0;
}
.sim-section-body {
    padding: 1rem 1.1rem;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

/* Item rows */
.item-row {
    display: grid;
    grid-template-columns: 1fr 60px 90px 36px;
    gap: 0.4rem;
    align-items: center;
    background: rgba(255,255,255,0.02);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-sm);
    padding: 0.5rem 0.6rem;
    transition: border-color 0.2s;
}
.item-row:hover { border-color: var(--primary); }
.item-row input {
    background: transparent;
    border: none;
    color: var(--text-main);
    font-size: 0.82rem;
    padding: 0.2rem 0;
    width: 100%;
    outline: none;
}
.item-row input::placeholder { color: var(--text-muted); }
.item-row .remove-item-btn {
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.2);
    color: #ef4444;
    border-radius: 4px;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1rem;
    line-height: 1;
    flex-shrink: 0;
    transition: background 0.15s;
}
.item-row .remove-item-btn:hover { background: rgba(239,68,68,0.25); }

/* Size selector tabs */
.size-tabs {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
}
.size-tab {
    flex: 1;
    min-width: 60px;
    padding: 0.55rem 0.4rem;
    border: 1px solid var(--card-border);
    border-radius: var(--radius-sm);
    background: rgba(255,255,255,0.02);
    color: var(--text-muted);
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    transition: all 0.2s;
}
.size-tab.active, .size-tab:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: rgba(139, 92, 246, 0.1);
}

/* ===== RECEIPT PREVIEW AREA ===== */
.sim-preview-wrap {
    position: sticky;
    top: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.receipt-preview-container {
    background: #e5e7eb;
    border-radius: var(--radius-md);
    padding: 2rem;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 400px;
    border: 1px solid var(--card-border);
    overflow: auto;
}

/* The receipt itself */
#receipt-preview {
    background: #ffffff;
    color: #111111;
    font-family: 'Courier New', Courier, monospace;
    box-shadow: 0 4px 24px rgba(0,0,0,0.25);
    padding: 1.25rem 1rem;
    transition: width 0.3s ease;
    width: 219px;
    font-size: 11px;
    line-height: 1.5;
}
#receipt-preview.size-80mm {
    width: 302px;
    font-size: 12px;
}
#receipt-preview.size-a4 {
    width: 680px;
    font-size: 13px;
    padding: 2.5rem 2rem;
}

/* Receipt elements */
.rcp-header { text-align: center; border-bottom: 1.5px dashed #999; padding-bottom: 0.6rem; margin-bottom: 0.6rem; }
.rcp-shop-name { font-size: 1.1em; font-weight: 900; text-transform: uppercase; }
.rcp-shop-sub { font-size: 0.78em; color: #555; }
.rcp-meta { font-size: 0.78em; color: #333; margin-bottom: 0.6rem; display: flex; flex-direction: column; gap: 0.15rem; }
.rcp-meta-row { display: flex; justify-content: space-between; }
.rcp-table { width: 100%; border-collapse: collapse; font-size: 0.82em; margin-bottom: 0.6rem; }
.rcp-table thead tr { border-bottom: 1px solid #111; border-top: 1px solid #111; }
.rcp-table th { padding: 0.3rem 0.1rem; }
.rcp-table td { padding: 0.3rem 0.1rem; border-bottom: 1px dashed #ddd; }
.rcp-totals { border-top: 1.5px dashed #999; padding-top: 0.5rem; font-size: 0.85em; display: flex; flex-direction: column; gap: 0.2rem; }
.rcp-total-row { display: flex; justify-content: space-between; }
.rcp-grand-total { font-size: 1.05em; font-weight: 900; border-top: 1px solid #111; padding-top: 0.3rem; margin-top: 0.15rem; }
.rcp-footer { text-align: center; border-top: 1px dashed #ccc; padding-top: 0.6rem; margin-top: 0.8rem; font-size: 0.75em; color: #666; }
.rcp-logo { max-width: 60px; max-height: 50px; object-fit: contain; margin-bottom: 0.3rem; }

/* Action bar */
.sim-action-bar { display: flex; gap: 0.6rem; flex-wrap: wrap; }
.sim-action-bar button {
    flex: 1;
    padding: 0.75rem;
    font-weight: 700;
    font-size: 0.85rem;
    border-radius: var(--radius-sm);
    cursor: pointer;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    transition: opacity 0.2s;
}
.sim-action-bar button:hover { opacity: 0.88; }

/* ===== PRINT STYLES ===== */
@media print {
    body * { visibility: hidden !important; }
    #receipt-preview, #receipt-preview * { visibility: visible !important; }
    #receipt-preview {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 8px !important;
        border: none !important;
        background: #fff !important;
    }
    #receipt-preview.size-58mm { width: 58mm !important; font-size: 8pt !important; }
    #receipt-preview.size-80mm { width: 80mm !important; font-size: 9pt !important; }
    #receipt-preview.size-a4 { width: 210mm !important; font-size: 11pt !important; }
}
</style>
@endsection

@section('content')
<div class="page-header" style="margin-bottom: 1.25rem;">
    <div>
        <h1 class="page-title">🧾 Receipt Simulator</h1>
        <p style="color:var(--text-muted);font-size:0.85rem;margin-top:0.2rem;">
            Preview and print test receipts for your store. Customize items, paper size, and content — then print to verify your thermal printer layout.
        </p>
    </div>
</div>

<div class="sim-layout">

    <!-- ===== LEFT: CONTROL PANEL ===== -->
    <div class="sim-controls">

        <!-- Paper Size -->
        <div class="sim-section">
            <div class="sim-section-header">
                <span>📐</span>
                <h3>Paper / Receipt Size</h3>
            </div>
            <div class="sim-section-body">
                <div class="size-tabs">
                    <button type="button" class="size-tab active" id="tab-58mm" onclick="setSize('58mm')">
                        58mm<br><span style="font-weight:400;font-size:0.7rem;color:var(--text-muted);">Narrow thermal</span>
                    </button>
                    <button type="button" class="size-tab" id="tab-80mm" onclick="setSize('80mm')">
                        80mm<br><span style="font-weight:400;font-size:0.7rem;color:var(--text-muted);">Standard thermal</span>
                    </button>
                    <button type="button" class="size-tab" id="tab-a4" onclick="setSize('a4')">
                        A4<br><span style="font-weight:400;font-size:0.7rem;color:var(--text-muted);">Full page</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Store Info -->
        <div class="sim-section">
            <div class="sim-section-header">
                <span>🏪</span>
                <h3>Store Header Info</h3>
            </div>
            <div class="sim-section-body">
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.8rem;">Shop Name</label>
                    <input class="form-input" id="sim-shop-name" type="text" value="{{ $tenant->name ?? 'My Store' }}" oninput="renderReceipt()" style="padding:0.5rem 0.75rem;font-size:0.85rem;">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.8rem;">Business / Legal Name</label>
                    <input class="form-input" id="sim-biz-name" type="text" value="{{ $tenant->business_name ?? '' }}" oninput="renderReceipt()" placeholder="(optional)" style="padding:0.5rem 0.75rem;font-size:0.85rem;">
                </div>
                <div class="grid-2" style="gap:0.5rem;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.8rem;">Phone</label>
                        <input class="form-input" id="sim-phone" type="text" value="{{ $tenant->phone ?? '' }}" oninput="renderReceipt()" placeholder="+254 700 000 000" style="padding:0.5rem 0.75rem;font-size:0.85rem;">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.8rem;">Location</label>
                        <input class="form-input" id="sim-location" type="text" value="{{ $tenant->location ?? '' }}" oninput="renderReceipt()" placeholder="e.g. Main Street" style="padding:0.5rem 0.75rem;font-size:0.85rem;">
                    </div>
                </div>
                @if($tenant->logo)
                <div class="form-group" style="margin:0;">
                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                        <input type="checkbox" id="sim-show-logo" checked onchange="renderReceipt()" style="width:16px;height:16px;">
                        <span style="font-size:0.82rem;color:var(--text-muted);">Include shop logo at top of receipt</span>
                    </label>
                </div>
                @else
                <div style="font-size:0.78rem;color:var(--text-muted);padding:0.4rem;background:rgba(255,255,255,0.03);border-radius:var(--radius-sm);border:1px dashed var(--card-border);">
                    💡 Upload a logo in <a href="{{ route('settings.index') }}" style="color:var(--primary);">Settings</a> to show it on receipts.
                </div>
                @endif
            </div>
        </div>

        <!-- Transaction Info -->
        <div class="sim-section">
            <div class="sim-section-header">
                <span>📋</span>
                <h3>Transaction Details</h3>
            </div>
            <div class="sim-section-body">
                <div class="grid-2" style="gap:0.5rem;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.8rem;">Customer Name</label>
                        <input class="form-input" id="sim-customer" type="text" value="Walk-in Customer" oninput="renderReceipt()" style="padding:0.5rem 0.75rem;font-size:0.85rem;">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.8rem;">Server Name</label>
                        <input class="form-input" id="sim-cashier" type="text" value="{{ Auth::user()->name }}" oninput="renderReceipt()" style="padding:0.5rem 0.75rem;font-size:0.85rem;">
                    </div>
                </div>
                <div class="grid-2" style="gap:0.5rem;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.8rem;">Payment Method</label>
                        <select class="form-input" id="sim-payment" onchange="renderReceipt()" style="padding:0.5rem 0.75rem;font-size:0.85rem;">
                            <option>Cash</option>
                            <option>M-Pesa</option>
                            <option>Card</option>
                            <option>Bank Transfer</option>
                            <option>Credit</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.8rem;">Currency Symbol</label>
                        <input class="form-input" id="sim-currency" type="text" value="{{ $baseCurrency->symbol ?? 'KSh' }}" oninput="renderReceipt()" style="padding:0.5rem 0.75rem;font-size:0.85rem;">
                    </div>
                </div>
                <div class="grid-2" style="gap:0.5rem;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.8rem;">Discount Amount</label>
                        <input class="form-input" id="sim-discount" type="number" value="0" min="0" oninput="renderReceipt()" style="padding:0.5rem 0.75rem;font-size:0.85rem;">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.8rem;">Tax Rate (%)</label>
                        <input class="form-input" id="sim-tax" type="number" value="{{ $tenant->tax_rate ?? 0 }}" min="0" max="100" step="0.01" oninput="renderReceipt()" style="padding:0.5rem 0.75rem;font-size:0.85rem;">
                    </div>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.8rem;">Amount Paid</label>
                    <input class="form-input" id="sim-paid" type="number" value="700" min="0" step="0.01" oninput="renderReceipt()" style="padding:0.5rem 0.75rem;font-size:0.85rem;">
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="sim-section">
            <div class="sim-section-header" style="justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <span>🛒</span>
                    <h3>Receipt Items</h3>
                </div>
                <button type="button" onclick="addItem()" class="btn-primary" style="padding:0.3rem 0.65rem;font-size:0.78rem;background:var(--primary);border:none;border-radius:4px;cursor:pointer;">
                    + Add Item
                </button>
            </div>
            <div class="sim-section-body" style="gap:0.4rem;">
                <div style="display:grid;grid-template-columns:1fr 60px 90px 36px;gap:0.4rem;padding:0 0.1rem;">
                    <span style="font-size:0.7rem;color:var(--text-muted);font-weight:600;">ITEM NAME</span>
                    <span style="font-size:0.7rem;color:var(--text-muted);font-weight:600;">QTY</span>
                    <span style="font-size:0.7rem;color:var(--text-muted);font-weight:600;">UNIT PRICE</span>
                    <span></span>
                </div>
                <div id="sim-items-list" style="display:flex;flex-direction:column;gap:0.4rem;"></div>
                <button type="button" onclick="addItem()" style="margin-top:0.25rem;width:100%;padding:0.45rem;border:1px dashed var(--card-border);border-radius:var(--radius-sm);background:transparent;color:var(--text-muted);font-size:0.8rem;cursor:pointer;transition:border-color 0.2s;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--card-border)'">
                    + Add another item
                </button>
            </div>
        </div>

        <!-- Footer Message -->
        <div class="sim-section">
            <div class="sim-section-header">
                <span>💬</span>
                <h3>Footer / Thank-You Message</h3>
            </div>
            <div class="sim-section-body">
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.8rem;">Line 1</label>
                    <input class="form-input" id="sim-footer1" type="text" value="Thank you for your business!" oninput="renderReceipt()" style="padding:0.5rem 0.75rem;font-size:0.85rem;">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.8rem;">Line 2</label>
                    <input class="form-input" id="sim-footer2" type="text" value="Please keep this receipt for records." oninput="renderReceipt()" style="padding:0.5rem 0.75rem;font-size:0.85rem;">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.8rem;">Line 3 (optional)</label>
                    <input class="form-input" id="sim-footer3" type="text" value="" oninput="renderReceipt()" placeholder="e.g. Visit us again soon!" style="padding:0.5rem 0.75rem;font-size:0.85rem;">
                </div>
            </div>
        </div>

        <!-- Display Options -->
        <div class="sim-section">
            <div class="sim-section-header">
                <span>⚙️</span>
                <h3>Display Options</h3>
            </div>
            <div class="sim-section-body" style="gap:0.55rem;">
                @foreach([
                    ['opt-show-receipt-no', 'Show Receipt Number', true],
                    ['opt-show-cashier',    'Show Server Name',    true],
                    ['opt-show-customer',   'Show Customer Name',  true],
                    ['opt-show-discount',   'Show Discount Line',  true],
                    ['opt-show-tax',        'Show Tax Line',       true],
                    ['opt-show-change',     'Show Change Amount',  true],
                    ['opt-show-sku',        'Show Item SKU / Code',false],
                ] as [$id, $label, $checked])
                <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                    <input type="checkbox" id="{{ $id }}" {{ $checked ? 'checked' : '' }} onchange="renderReceipt()" style="width:16px;height:16px;">
                    <span style="font-size:0.82rem;color:var(--text-muted);">{{ $label }}</span>
                </label>
                @endforeach
            </div>
        </div>

    </div>

    <!-- ===== RIGHT: PREVIEW PANE ===== -->
    <div class="sim-preview-wrap">

        <!-- Action Bar -->
        <div class="glass-panel" style="padding:1rem 1.25rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;">
                <div>
                    <p style="font-weight:700;font-size:0.9rem;color:var(--text-main);margin:0;">Live Receipt Preview</p>
                    <p style="font-size:0.75rem;color:var(--text-muted);margin:0.15rem 0 0 0;" id="sim-size-label">58mm thermal receipt (narrow)</p>
                </div>
                <div class="sim-action-bar">
                    <button type="button" onclick="resetAll()" style="background:rgba(255,255,255,0.06);color:var(--text-muted);border:1px solid var(--card-border);">
                        🔄 Reset
                    </button>
                    <button type="button" onclick="window.print()" style="background:linear-gradient(135deg,#111 0%,#333 100%);color:#fff;">
                        🖨️ Print Receipt
                    </button>
                </div>
            </div>
        </div>

        <!-- Receipt Preview -->
        <div class="receipt-preview-container">
            <div id="receipt-preview" class="size-58mm">
                <!-- Rendered by JS -->
            </div>
        </div>

        <!-- Helper note -->
        <div style="background:rgba(139,92,246,0.08);border:1px solid rgba(139,92,246,0.2);border-radius:var(--radius-sm);padding:0.75rem 1rem;font-size:0.78rem;color:var(--text-muted);">
            💡 <strong style="color:var(--text-main);">Tip:</strong> This simulator previews exactly how receipts will look when printed from the POS. Adjust the paper size to match your thermal printer (58mm or 80mm is most common), then click <strong style="color:var(--text-main);">Print Receipt</strong> to verify the layout on your printer.
        </div>

    </div>
</div>

<script>
let simSize = '58mm';
let simItems = [];
let simItemCounter = 0;

const sizeLabels = {
    '58mm': '58mm thermal receipt (narrow)',
    '80mm': '80mm thermal receipt (standard)',
    'a4':   'A4 full-page document'
};

function setSize(size) {
    simSize = size;
    document.querySelectorAll('.size-tab').forEach(t => t.classList.remove('active'));
    const tab = document.getElementById('tab-' + size);
    if (tab) tab.classList.add('active');
    const lbl = document.getElementById('sim-size-label');
    if (lbl) lbl.textContent = sizeLabels[size] || size;
    const el = document.getElementById('receipt-preview');
    el.className = 'size-' + size;
    renderReceipt();
}

function addItem(name, qty, price) {
    name  = name  !== undefined ? name  : '';
    qty   = qty   !== undefined ? qty   : 1;
    price = price !== undefined ? price : 0;
    const id = ++simItemCounter;
    simItems.push({ id, name, qty, price });
    const row = document.createElement('div');
    row.className = 'item-row';
    row.id = 'item-row-' + id;
    row.innerHTML = [
        '<input type="text" placeholder="Product name" value="' + name + '" oninput="updateItem(' + id + ',\'name\',this.value)">',
        '<input type="number" min="1" value="' + qty + '" style="text-align:center;" oninput="updateItem(' + id + ',\'qty\',this.value)">',
        '<input type="number" min="0" step="0.01" value="' + price + '" style="text-align:right;" oninput="updateItem(' + id + ',\'price\',this.value)">',
        '<button class="remove-item-btn" onclick="removeItem(' + id + ')" title="Remove">&#215;</button>'
    ].join('');
    document.getElementById('sim-items-list').appendChild(row);
    renderReceipt();
}

function updateItem(id, field, value) {
    const item = simItems.find(function(i) { return i.id === id; });
    if (!item) return;
    if (field === 'qty') item.qty = parseInt(value) || 1;
    else if (field === 'price') item.price = parseFloat(value) || 0;
    else item[field] = value;
    renderReceipt();
}

function removeItem(id) {
    simItems = simItems.filter(function(i) { return i.id !== id; });
    var row = document.getElementById('item-row-' + id);
    if (row) row.remove();
    renderReceipt();
}

function gv(id) {
    var el = document.getElementById(id);
    return el ? el.value.trim() : '';
}
function gc(id) {
    var el = document.getElementById(id);
    return el ? el.checked : false;
}

function renderReceipt() {
    var shopName  = gv('sim-shop-name')  || 'My Store';
    var bizName   = gv('sim-biz-name');
    var phone     = gv('sim-phone');
    var location  = gv('sim-location');
    var customer  = gv('sim-customer')   || 'Walk-in Customer';
    var cashier   = gv('sim-cashier')    || 'Staff';
    var payment   = gv('sim-payment')    || 'Cash';
    var sym       = gv('sim-currency')   || 'KSh';
    var discount  = parseFloat(document.getElementById('sim-discount') ? document.getElementById('sim-discount').value : 0) || 0;
    var taxRate   = parseFloat(document.getElementById('sim-tax')      ? document.getElementById('sim-tax').value      : 0) || 0;
    var amtPaid   = parseFloat(document.getElementById('sim-paid')     ? document.getElementById('sim-paid').value     : 0) || 0;
    var footer1   = gv('sim-footer1');
    var footer2   = gv('sim-footer2');
    var footer3   = gv('sim-footer3');
    var showLogo  = gc('sim-show-logo');
    var showNo    = gc('opt-show-receipt-no');
    var showCashier = gc('opt-show-cashier');
    var showCust  = gc('opt-show-customer');
    var showDisc  = gc('opt-show-discount');
    var showTax   = gc('opt-show-tax');
    var showChg   = gc('opt-show-change');
    var showSKU   = gc('opt-show-sku');

    var subtotal = simItems.reduce(function(s, i) { return s + i.price * i.qty; }, 0);
    var discountAmt = Math.min(discount, subtotal);
    var taxable = subtotal - discountAmt;
    var taxAmt  = taxable * (taxRate / 100);
    var total   = taxable + taxAmt;
    var change  = Math.max(0, amtPaid - total);

    var now = new Date();
    var dateStr = now.toLocaleDateString() + ' ' + now.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    var receiptNo = Math.floor(10000 + Math.random() * 90000);

    var logoSrc = '{{ $tenant->logo ? asset("storage/" . $tenant->logo) : "" }}';
    var logoHtml = (showLogo && logoSrc) ? '<div><img class="rcp-logo" src="' + logoSrc + '" alt="Logo"></div>' : '';

    var itemsHtml;
    if (simItems.length === 0) {
        itemsHtml = '<tr><td colspan="4" style="text-align:center;color:#aaa;padding:0.5rem 0;">No items added yet</td></tr>';
    } else {
        itemsHtml = simItems.map(function(i) {
            var skuHtml = showSKU ? '<br><span style="font-size:0.7em;color:#888;">SKU-' + String(i.id).padStart(4,'0') + '</span>' : '';
            return '<tr><td style="text-align:left;padding:0.3rem 0.1rem;">' + (i.name || '—') + skuHtml + '</td>' +
                   '<td style="text-align:center;padding:0.3rem 0.1rem;">' + i.qty + '</td>' +
                   '<td style="text-align:right;padding:0.3rem 0.1rem;">' + sym + i.price.toFixed(2) + '</td>' +
                   '<td style="text-align:right;padding:0.3rem 0.1rem;">' + sym + (i.price * i.qty).toFixed(2) + '</td></tr>';
        }).join('');
    }

    var totalsHtml = '<div class="rcp-total-row"><span>Subtotal:</span><span>' + sym + subtotal.toFixed(2) + '</span></div>';
    if (showDisc && discountAmt > 0) {
        totalsHtml += '<div class="rcp-total-row"><span>Discount:</span><span>-' + sym + discountAmt.toFixed(2) + '</span></div>';
    }
    if (showTax && taxAmt > 0) {
        totalsHtml += '<div class="rcp-total-row"><span>Tax (' + taxRate + '%):</span><span>+' + sym + taxAmt.toFixed(2) + '</span></div>';
    }
    totalsHtml += '<div class="rcp-total-row rcp-grand-total"><span>TOTAL DUE:</span><span>' + sym + total.toFixed(2) + '</span></div>';
    totalsHtml += '<div class="rcp-total-row" style="padding-top:0.2rem;"><span>Paid (' + payment.toUpperCase() + '):</span><span>' + sym + amtPaid.toFixed(2) + '</span></div>';
    if (showChg) {
        totalsHtml += '<div class="rcp-total-row" style="font-weight:700;color:#059669;"><span>Change:</span><span>' + sym + change.toFixed(2) + '</span></div>';
    }

    var footerHtml = '';
    if (footer1 || footer2 || footer3) {
        footerHtml = '<div class="rcp-footer">';
        if (footer1) footerHtml += '<p style="margin:0;font-weight:700;">' + footer1 + '</p>';
        if (footer2) footerHtml += '<p style="margin:0.15rem 0 0;">' + footer2 + '</p>';
        if (footer3) footerHtml += '<p style="margin:0.15rem 0 0;">' + footer3 + '</p>';
        footerHtml += '</div>';
    }

    var metaHtml = '<div class="rcp-meta">';
    if (showNo)      metaHtml += '<div class="rcp-meta-row"><span>Receipt No:</span><strong>#' + receiptNo + '</strong></div>';
    metaHtml += '<div class="rcp-meta-row"><span>Date:</span><span>' + dateStr + '</span></div>';
    if (showCashier) metaHtml += '<div class="rcp-meta-row"><span>Server:</span><span>' + cashier + '</span></div>';
    if (showCust)    metaHtml += '<div class="rcp-meta-row"><span>Customer:</span><span>' + customer + '</span></div>';
    metaHtml += '</div>';

    var html = '<div class="rcp-header">' + logoHtml +
        '<div class="rcp-shop-name">' + shopName + '</div>' +
        (bizName   ? '<div class="rcp-shop-sub">' + bizName  + '</div>' : '') +
        (location  ? '<div class="rcp-shop-sub">' + location + '</div>' : '') +
        (phone     ? '<div class="rcp-shop-sub">Tel: ' + phone + '</div>' : '') +
        '</div>' + metaHtml +
        '<table class="rcp-table"><thead><tr>' +
        '<th style="text-align:left;">Item</th>' +
        '<th style="text-align:center;">Qty</th>' +
        '<th style="text-align:right;">Price</th>' +
        '<th style="text-align:right;">Total</th>' +
        '</tr></thead><tbody>' + itemsHtml + '</tbody></table>' +
        '<div class="rcp-totals">' + totalsHtml + '</div>' +
        footerHtml;

    document.getElementById('receipt-preview').innerHTML = html;
}

function resetAll() {
    if (!confirm('Reset all items and fields to defaults?')) return;
    simItems = [];
    simItemCounter = 0;
    document.getElementById('sim-items-list').innerHTML = '';
    var fields = {
        'sim-customer': 'Walk-in Customer',
        'sim-discount': '0',
        'sim-paid':     '700',
        'sim-footer1':  'Thank you for your business!',
        'sim-footer2':  'Please keep this receipt for records.',
        'sim-footer3':  ''
    };
    Object.keys(fields).forEach(function(k) {
        var el = document.getElementById(k);
        if (el) el.value = fields[k];
    });
    addItem('Sample Item 1', 2, 150.00);
    addItem('Sample Item 2', 1, 350.00);
    renderReceipt();
}

document.addEventListener('DOMContentLoaded', function() {
    setSize('58mm');
    addItem('Sample Item 1', 2, 150.00);
    addItem('Sample Item 2', 1, 350.00);
    renderReceipt();
});
</script>
@endsection
