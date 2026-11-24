@extends('layouts.app')

@section('title', 'POS Checkout - Luxeshop POS')

@section('styles')
<style>
    @keyframes stk-ping {
        0% { transform: scale(0.9); opacity: 1; }
        50% { transform: scale(1.6); opacity: 0; }
        100% { transform: scale(0.9); opacity: 0; }
    }
    @keyframes stk-radar {
        0% { transform: scale(0.6); opacity: 0.8; }
        100% { transform: scale(1.8); opacity: 0; }
    }
    .stk-radar-ring {
        position: absolute;
        inset: 0;
        border-radius: 9999px;
        border: 2px solid rgba(251, 191, 36, 0.5);
        animation: stk-radar 2s infinite cubic-bezier(0.2, 0.8, 0.2, 1);
    }
    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .pm-tab-btn.active {
        border-color: var(--primary) !important;
        background: rgba(37, 99, 235, 0.15) !important;
        color: var(--primary) !important;
    }
</style>
@endsection

@section('content')
@if(isset($editingSale) && $editingSale)
    <div style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(217, 119, 6, 0.25) 100%); border: 1px solid #f59e0b; border-radius: 8px; padding: 0.85rem 1.25rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; color: #f59e0b; font-weight: 700; font-size: 0.95rem;">
            <span>✏️ Editing Sale #{{ $editingSale->id }}</span>
            <span style="font-size: 0.85rem; font-weight: 400; color: var(--text-main);">
                (Customer: {{ $editingSale->customer_name ?: 'Walk-in Customer' }} | Original Total: {{ $baseCurrency->symbol }}{{ number_format($editingSale->total, 2) }})
            </span>
        </div>
        <a href="{{ route('pos') }}" class="btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; text-decoration: none; color: #ef4444; border-color: rgba(239, 68, 68, 0.4);">
            ✕ Cancel Edit Mode
        </a>
    </div>
@endif

<div class="pos-container">
    <!-- Left Panel: Product Catalogue -->
    <div class="pos-catalog">
        <div class="glass-panel panel-body" style="display: flex; flex-direction: column; gap: 1rem; padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                    <h2 style="font-size: 1.2rem; font-weight: 700;">{{ $tenant->name ?? 'Store' }} Catalogue</h2>
                    <span id="network-status-badge" class="badge badge-received" style="font-size: 0.75rem;">🟢 Online Mode</span>
                    <span id="offline-queue-badge" onclick="triggerOfflineSync()" class="badge badge-pending" style="display: none; font-size: 0.75rem; cursor: pointer;" title="Click to manually sync queued offline sales">⚡ 0 Offline Sales Queued (Click to Sync)</span>
                </div>
                
                <!-- Camera Scan Toggle -->
                <button type="button" class="btn-secondary" id="camera-scan-toggle" style="background: var(--primary); color: #fff; border: none; font-size: 0.85rem; padding: 0.5rem 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    📷 Scan with Camera (Phone/Webcam)
                </button>
            </div>

            <!-- HTML5 QR Code Scanner Render Target -->
            <div id="qr-reader" style="width: 100%; max-width: 400px; display: none; margin: 0 auto; border: 1px solid var(--card-border); border-radius: var(--radius-sm); padding: 10px; background: rgba(0,0,0,0.3);"></div>

            <div class="pos-search-bar">
                <input type="text" id="search-query" class="form-input" placeholder="🔍 Search product by name..." style="padding: 0.75rem;">
                <input type="text" id="barcode-scan-input" class="form-input" placeholder="⚡ Scan Barcode/QR (Hardware scanner focus)" style="padding: 0.75rem; max-width: 250px; border-color: var(--primary);">
            </div>
        </div>

        <!-- Catalogue Grid -->
        <div class="catalog-grid" id="catalog-products">
            @foreach($products as $product)
                <div class="glass-panel product-card"
                     data-id="{{ $product->id }}"
                     data-name="{{ $product->name }}"
                     data-price="{{ $product->price }}"
                     data-sku="{{ $product->sku }}"
                     data-stock="{{ $product->quantity }}"
                     onclick="posAddToCart({{ $product->id }}, {{ json_encode($product->name) }}, {{ (float)$product->price }}, {{ (int)$product->quantity }})"
                     style="cursor:pointer;">
                    
                    <div class="product-image-container" style="position: relative;">
                        @if($product->image_path)
                            <img class="product-img" src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}">
                        @else
                            <span class="product-placeholder-icon">🛍️</span>
                        @endif

                        <span class="product-in-cart-badge" id="product-in-cart-{{ $product->id }}" style="display: none; position: absolute; top: 6px; right: 6px; background: var(--accent-emerald); color: #ffffff; font-weight: 800; font-size: 0.72rem; padding: 0.2rem 0.5rem; border-radius: 12px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.4); z-index: 10;">
                            0 in cart
                        </span>

                        @if($product->quantity <= $product->low_stock_threshold)
                            <span class="product-badge-low">Low Stock</span>
                        @endif
                    </div>
                    
                    <div class="product-info-wrap">
                        <span class="product-title">{{ $product->name }}</span>
                        <span class="product-sku">{{ $product->sku }}</span>
                    </div>
                    
                    <div class="product-footer">
                        <span class="product-price">{{ $baseCurrency->symbol }}{{ number_format($product->price, 2) }}</span>
                        <span class="product-stock {{ $product->quantity <= $product->low_stock_threshold ? 'low-stock' : '' }}">
                            Stock: {{ $product->quantity }}
                        </span>
                    </div>

                    {{-- Out-of-stock overlay: prevents click visually and via pointer-events --}}
                    @if($product->quantity <= 0)
                        <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.55); border-radius: inherit; display: flex; align-items: center; justify-content: center; z-index: 5; pointer-events: none;">
                            <span style="background: #ef4444; color: #fff; font-size: 0.75rem; font-weight: 800; padding: 0.3rem 0.75rem; border-radius: 20px; letter-spacing: 0.05em;">OUT OF STOCK</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Right Panel: Cart Drawer -->
    <div class="glass-panel pos-cart-panel">
        <div class="cart-header">
            <h2 class="cart-title">🛒 Cart <span class="cart-items-count" id="cart-badge">0</span></h2>
            <button type="button" class="btn-secondary" id="clear-cart-btn" style="padding: 0.35rem 0.60rem; font-size: 0.75rem; color: #ef4444; border-color: rgba(239, 68, 68, 0.2);">Clear</button>
        </div>

        <!-- Cart Items List -->
        <div class="cart-items-list" id="cart-items-container">
            <!-- Dynamic elements will load here -->
            <div style="text-align: center; color: var(--text-muted); margin: auto;" id="empty-cart-msg">
                <span style="font-size: 3rem; display: block; margin-bottom: 0.5rem;">🛍️</span>
                Cart is empty
            </div>
        </div>

        <!-- Cart Summary and Checkout -->
        <form action="{{ route('pos.checkout') }}" method="POST" class="cart-summary">
            @csrf
            
            <!-- Cart Data payload -->
            <input type="hidden" name="cart" id="cart-data-input">
            <input type="hidden" name="currency_code" id="currency-select" value="{{ $baseCurrency->code }}">
            @if(isset($editingSale) && $editingSale)
                <input type="hidden" name="editing_sale_id" value="{{ $editingSale->id }}">
            @endif

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="customer_name" id="customer-name-label">Customer Name</label>
                    <input type="text" name="customer_name" id="customer-name-input" class="form-input" placeholder="Walk-in Customer" style="padding: 0.6rem; font-size: 0.9rem;">
                </div>
                <div class="form-group">
                    <label class="form-label" for="customer_phone" id="customer-phone-label">Customer Phone</label>
                    <input type="text" name="customer_phone" id="customer-phone-input" class="form-input" placeholder="e.g. +256700000000" style="padding: 0.6rem; font-size: 0.9rem;">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="discount">Discount ({{ $baseCurrency->symbol }})</label>
                    <input type="number" step="0.01" name="discount" id="discount-input" class="form-input" value="0.00" style="padding: 0.6rem; font-size: 0.9rem;">
                </div>
                <div class="form-group">
                    <label class="form-label" for="tax_rate">Tax Rate (%)</label>
                    <input type="number" step="0.01" name="tax_rate" id="tax-input" class="form-input" value="0.00" style="padding: 0.6rem; font-size: 0.9rem;">
                </div>
            </div>

            <div class="summary-row">
                <span>Subtotal</span>
                <span id="summary-subtotal">{{ $baseCurrency->symbol }}0.00</span>
            </div>
            
            <div class="summary-row total">
                <span>Total Due</span>
                <span id="summary-total-converted">{{ $baseCurrency->symbol }}0.00</span>
            </div>

            <div class="form-group" style="margin-top: 0.5rem;">
                <label class="form-label" for="payment_method">Payment Method</label>
                
                {{-- Quick Payment Method Selector Tabs --}}
                <div class="pm-tabs-container" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.35rem; margin-bottom: 0.5rem;">
                    <button type="button" class="pm-tab-btn active" id="pm-tab-cash" onclick="posSelectPaymentMethod('cash')" style="padding: 0.45rem 0.2rem; font-size: 0.78rem; font-weight: 700; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-surface); color: var(--text-main); cursor: pointer; transition: all 0.2s;">💵 Cash</button>
                    <button type="button" class="pm-tab-btn" id="pm-tab-mobile" onclick="posSelectPaymentMethod('mobile')" style="padding: 0.45rem 0.2rem; font-size: 0.78rem; font-weight: 700; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-surface); color: var(--text-main); cursor: pointer; transition: all 0.2s;">📱 Mobile</button>
                    <button type="button" class="pm-tab-btn" id="pm-tab-card" onclick="posSelectPaymentMethod('card')" style="padding: 0.45rem 0.2rem; font-size: 0.78rem; font-weight: 700; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-surface); color: var(--text-main); cursor: pointer; transition: all 0.2s;">💳 Card</button>
                    <button type="button" class="pm-tab-btn" id="pm-tab-debt" onclick="posSelectPaymentMethod('debt')" style="padding: 0.45rem 0.2rem; font-size: 0.78rem; font-weight: 700; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-surface); color: var(--text-main); cursor: pointer; transition: all 0.2s;">📝 Debt</button>
                </div>

                <select name="payment_method" id="payment-method-select" class="form-input" style="padding: 0.6rem; font-size: 0.9rem;" onchange="onPaymentMethodChange()">
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="mobile">📱 Mobile Money (MTN / Airtel)</option>
                    <option value="debt">Debt / Customer Credit</option>
                </select>
            </div>

            {{-- Mobile Money Extra Fields --}}
            <div id="mobile-money-fields" style="display:none; background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16,185,129,0.2); border-radius: var(--radius-sm); padding: 0.85rem; margin-bottom: 0.75rem; animation: fadeInDown 0.25s ease;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.6rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1.25rem;">📱</span>
                        <strong style="color: var(--accent-emerald); font-size: 0.88rem;">ioTec Pay Mobile Money</strong>
                    </div>
                    <span style="background: rgba(16,185,129,0.15); color: var(--accent-emerald); border-radius: 4px; padding: 0.15rem 0.5rem; font-size: 0.72rem; font-weight: 800; text-transform: uppercase;">Direct STK Push</span>
                </div>
                <div class="form-group" style="margin-bottom: 0.6rem;">
                    <label class="form-label" style="font-size: 0.8rem;">Select Network Operator</label>
                    <div style="display: flex; gap: 0.6rem; margin-top: 0.25rem;">
                        <label style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.5rem 0.8rem; border: 2px solid rgba(250,204,21,0.3); border-radius: 6px; cursor: pointer; background: rgba(250,204,21,0.05); transition: all 0.2s;" id="mtn-label">
                            <input type="radio" name="mobile_network" value="MTN" checked style="accent-color: #facc15;" onchange="updateMobileNetworkStyle()">
                            <strong style="color: #facc15; font-size: 0.85rem;">MTN MoMo (*165#)</strong>
                        </label>
                        <label style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.5rem 0.8rem; border: 2px solid rgba(239,68,68,0.3); border-radius: 6px; cursor: pointer; background: rgba(239,68,68,0.05); transition: all 0.2s;" id="airtel-label">
                            <input type="radio" name="mobile_network" value="Airtel" style="accent-color: #ef4444;" onchange="updateMobileNetworkStyle()">
                            <strong style="color: #f87171; font-size: 0.85rem;">Airtel Money (*185#)</strong>
                        </label>
                    </div>
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 0.6rem;">
                    💡 Prompt will be sent directly to <strong>Customer Phone</strong> entered above. Format: 07XXXXXXXX or 256XXXXXXXXX.
                </div>
                <div style="background: rgba(245,158,11,0.1); border: 1px dashed rgba(245,158,11,0.3); border-radius: 5px; padding: 0.5rem 0.75rem; font-size: 0.8rem; color: var(--accent-amber);">
                    <strong>⚡ Extra UGX 500</strong> mobile money processing surcharge is included. Client receives prompt on phone.
                </div>
            </div>

            <div class="form-row" id="amount-paid-row">
                <div class="form-group">
                    <label class="form-label" for="amount_paid">Amount Paid *</label>
                    <input type="number" step="any" name="amount_paid" id="amount-paid-input" required
                           class="form-input" placeholder="0.00"
                           oninput="this._userTyped=true; posCalculateChange()"
                           style="padding: 0.6rem; font-size: 0.9rem; border-color: var(--primary);">
                    <div style="display: flex; gap: 0.25rem; margin-top: 0.35rem; flex-wrap: wrap;" id="quick-cash-buttons">
                        <button type="button" class="btn-secondary" onclick="setExactAmount()" style="padding: 0.2rem 0.45rem; font-size: 0.72rem; font-weight: 700; color: var(--primary); border-color: var(--primary-glow);">Exact Cash</button>
                        <button type="button" class="btn-secondary" onclick="addAmount(1000)" style="padding: 0.2rem 0.45rem; font-size: 0.72rem;">+1k</button>
                        <button type="button" class="btn-secondary" onclick="addAmount(5000)" style="padding: 0.2rem 0.45rem; font-size: 0.72rem;">+5k</button>
                        <button type="button" class="btn-secondary" onclick="addAmount(10000)" style="padding: 0.2rem 0.45rem; font-size: 0.72rem;">+10k</button>
                        <button type="button" class="btn-secondary" onclick="addAmount(50000)" style="padding: 0.2rem 0.45rem; font-size: 0.72rem;">+50k</button>
                    </div>
                </div>
                <div class="form-group" id="change-display-group">
                    <label class="form-label">Change</label>
                    <div id="change-display" style="padding: 0.6rem; font-size: 1.1rem; font-weight: 700; color: var(--accent-emerald);">0.00</div>
                </div>
            </div>

            {{-- 📱 PROMINENT DEDICATED MOBILE MONEY PAY BUTTON ON CART --}}
            <button type="button" id="cart-pay-mobile-direct-btn" onclick="payUsingMobileMoney()" style="width: 100%; margin-top: 0.65rem; padding: 0.88rem 1rem; border-radius: var(--radius-sm); background: linear-gradient(135deg, #059669 0%, #10b981 50%, #047857 100%); color: #ffffff; font-weight: 800; font-size: 0.98rem; border: 1px solid rgba(16,185,129,0.5); box-shadow: 0 4px 16px rgba(16,185,129,0.35); display: flex; align-items: center; justify-content: center; gap: 0.55rem; cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);">
                <span style="font-size: 1.25rem;">📱</span>
                <span id="cart-pay-mobile-text">Pay using Mobile Money (STK Push)</span>
            </button>

            <button type="submit" id="process-payment-btn" class="btn-primary" style="margin-top: 0.45rem; font-weight: 700; padding: 0.85rem; font-size: 1rem; background: {{ isset($editingSale) && $editingSale ? 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)' : 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)' }};">
                {{ isset($editingSale) && $editingSale ? '🔄 Update & Redo Sale #' . $editingSale->id : '💳 Process Payment' }}
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')


<script>
    // ── Global cart state (window scope so inline onclick can reach it) ──────
    window.posCart = [];
    window.posBaseSymbol = "{{ $baseCurrency->symbol }}";

    // ── Base URL for STK push AJAX calls (generated by Laravel to handle subdirectory hosting) ──
    window._stkStatusUrlBase = "{{ url('/pos/stk-status') }}/";
    window._stkCancelUrlBase = "{{ url('/pos/stk-cancel') }}/";
    window._stkSimulateUrlBase = "{{ url('/pos/stk-simulate') }}/";

    // Called directly from onclick on every product card
    window.posAddToCart = function(id, name, price, stock) {
        if (stock <= 0) {
            alert(name + ' is out of stock.');
            return;
        }
        const existing = posCart.find(i => i.product_id === id);
        if (existing) {
            if (existing.qty >= stock) {
                alert('Cannot add more — only ' + stock + ' in stock.');
                return;
            }
            existing.qty++;
        } else {
            posCart.push({ product_id: id, name: name, price: price, qty: 1, stock: stock });
        }
        posRenderCart();
        posShowToast('Added: ' + name);
    };

    window.posRenderCart = function() {
        const container = document.getElementById('cart-items-container');
        const badge     = document.getElementById('cart-badge');
        const cartInput = document.getElementById('cart-data-input');
        if (!container) return;

        if (posCart.length === 0) {
            container.innerHTML = '<div style="text-align:center;color:var(--text-muted);margin:auto;"><span style="font-size:3rem;display:block;">🛍️</span>Cart is empty</div>';
            if (badge) badge.textContent = '0';
            if (cartInput) cartInput.value = '';
            updateCatalogBadges();
            posCalculateTotals();
            return;
        }

        let totalItems = 0;
        container.innerHTML = '';
        posCart.forEach((item, idx) => {
            totalItems += item.qty;
            container.insertAdjacentHTML('beforeend', `
                <div class="cart-item">
                    <div class="cart-item-details">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-price">${posBaseSymbol}${(item.price * item.qty).toFixed(2)}</div>
                    </div>
                    <div class="cart-item-qty-control" style="display:flex;align-items:center;">
                        <button type="button" class="qty-btn" onclick="posAdjustQty(${idx},-1)">-</button>
                        <input type="number" class="form-input qty-input-field" value="${item.qty}" min="1" max="${item.stock}"
                               onchange="posSetQty(${idx},this.value)" style="width:58px;text-align:center;padding:0.3rem 0.2rem;font-weight:700;margin:0 0.2rem;border-color:var(--primary);">
                        <button type="button" class="qty-btn" onclick="posAdjustQty(${idx},1)">+</button>
                    </div>
                    <button type="button" class="cart-item-remove" onclick="posRemoveItem(${idx})">🗑️</button>
                </div>
            `);
        });
        if (badge) badge.textContent = totalItems;
        if (cartInput) cartInput.value = JSON.stringify(posCart.map(i => ({ product_id: i.product_id, qty: i.qty })));
        updateCatalogBadges();
        posCalculateTotals();
    };

    function updateCatalogBadges() {
        document.querySelectorAll('.product-in-cart-badge').forEach(badge => {
            badge.style.display = 'none';
            badge.textContent = '0';
        });

        posCart.forEach(item => {
            const badge = document.getElementById(`product-in-cart-${item.product_id}`);
            if (badge && item.qty > 0) {
                badge.textContent = `🛒 ${item.qty} in cart`;
                badge.style.display = 'inline-block';
            }
        });
    }

    window.posAdjustQty = function(idx, change) {
        const item = posCart[idx];
        if (!item) return;
        const newQty = item.qty + change;
        if (newQty <= 0) { posCart.splice(idx, 1); }
        else if (newQty > item.stock) { alert('Only ' + item.stock + ' in stock.'); return; }
        else { item.qty = newQty; }
        posRenderCart();
    };

    window.posSetQty = function(idx, val) {
        const item = posCart[idx];
        const v = parseInt(val) || 1;
        if (v > item.stock) { alert('Only ' + item.stock + ' in stock.'); return; }
        item.qty = Math.max(1, v);
        posRenderCart();
    };

    window.posRemoveItem = function(idx) {
        posCart.splice(idx, 1);
        posRenderCart();
    };

    window.posCalculateTotals = function() {
        const disc = parseFloat((document.getElementById('discount-input') || {}).value) || 0;
        const tax  = parseFloat((document.getElementById('tax-input')      || {}).value) || 0;
        let sub = 0;
        posCart.forEach(i => sub += i.price * i.qty);
        let total = Math.max(0, sub - disc + (sub - disc) * (tax / 100));
        // Add UGX 500 mobile money surcharge
        const pmSel = document.getElementById('payment-method-select');
        const isMobile = pmSel && pmSel.value === 'mobile';
        if (isMobile) total += 500;
        const sub_el   = document.getElementById('summary-subtotal');
        const total_el = document.getElementById('summary-total-converted');
        const paid_el  = document.getElementById('amount-paid-input');
        if (sub_el)   sub_el.textContent   = posBaseSymbol + sub.toFixed(2);
        if (total_el) total_el.textContent = posBaseSymbol + total.toFixed(2);
        if (paid_el && !paid_el._userTyped) paid_el.value = total > 0 ? total.toFixed(2) : '';
        const cartInput = document.getElementById('cart-data-input');
        if (cartInput) cartInput.value = JSON.stringify(posCart.map(i => ({ product_id: i.product_id, qty: i.qty })));
        posCalculateChange();
    };

    window.posCalculateChange = function() {
        const total_el = document.getElementById('summary-total-converted');
        const paid_el  = document.getElementById('amount-paid-input');
        const chg_el   = document.getElementById('change-display');
        if (!total_el || !paid_el || !chg_el) return;
        // Strip ALL non-numeric chars except dot to handle any currency symbol
        const total = parseFloat(total_el.textContent.replace(/[^0-9.]/g, '')) || 0;
        const paid  = parseFloat(paid_el.value) || 0;
        const chg   = paid - total;
        if (paid === 0 && total === 0) {
            chg_el.textContent = posBaseSymbol + '0.00';
            chg_el.style.color = 'var(--accent-emerald)';
            return;
        }
        chg_el.textContent = (chg < 0 ? '-' : '') + posBaseSymbol + Math.abs(chg).toFixed(2);
        chg_el.style.color = chg >= 0 ? 'var(--accent-emerald)' : '#ef4444';
        // Also keep the change display large and visible
        chg_el.style.fontSize = '1.3rem';
        chg_el.style.fontWeight = '800';
    };

    window.posSelectPaymentMethod = function(method) {
        const sel = document.getElementById('payment-method-select');
        if (sel) {
            sel.value = method;
            onPaymentMethodChange();
        }
    };

    window.onPaymentMethodChange = function() {
        const sel = document.getElementById('payment-method-select');
        const mobileFields = document.getElementById('mobile-money-fields');
        const procBtn = document.getElementById('process-payment-btn');
        const payDirectMobileBtn = document.getElementById('cart-pay-mobile-direct-btn');
        const amountRow = document.getElementById('amount-paid-row');
        const isMobile = sel && sel.value === 'mobile';

        if (sel && mobileFields) {
            mobileFields.style.display = isMobile ? 'block' : 'none';
        }

        // Sync tab buttons active state
        if (sel) {
            document.querySelectorAll('.pm-tab-btn').forEach(btn => btn.classList.remove('active'));
            const activeBtn = document.getElementById('pm-tab-' + sel.value);
            if (activeBtn) activeBtn.classList.add('active');
        }

        // Adjust buttons based on method
        if (procBtn) {
            if (isMobile) {
                procBtn.innerHTML = '📲 Send STK Push to Client';
                procBtn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
            } else {
                procBtn.innerHTML = '💳 Process Payment';
                procBtn.style.background = 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)';
            }
        }

        // Always keep direct mobile pay button available on cart
        if (payDirectMobileBtn) {
            payDirectMobileBtn.style.display = 'flex';
        }

        posCalculateTotals();
    };

    window.payUsingMobileMoney = function() {
        if (posCart.length === 0) {
            alert('Your cart is empty. Please add products to cart first.');
            return;
        }

        posSelectPaymentMethod('mobile');
        posCalculateTotals();

        const phoneVal = (document.getElementById('customer-phone-input')?.value || '').trim();
        if (!phoneVal) {
            openMobileMoneyPhoneModal();
        } else {
            // Ensure amount_paid is set to the total (including mobile fee)
            const totalEl = document.getElementById('summary-total-converted');
            const paidEl = document.getElementById('amount-paid-input');
            if (totalEl && paidEl) {
                const totalVal = parseFloat(totalEl.textContent.replace(/[^0-9.]/g, '')) || 0;
                if (!parseFloat(paidEl.value) || parseFloat(paidEl.value) < totalVal) {
                    paidEl.value = totalVal.toFixed(2);
                }
            }

            if (typeof window.posPerformCheckout === 'function') {
                window.posPerformCheckout();
            } else {
                const posForm = document.querySelector('form.cart-summary');
                if (posForm) posForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            }
        }
    };

    window.openMobileMoneyPhoneModal = function() {
        let existing = document.getElementById('mobile-phone-input-modal');
        if (existing) existing.remove();

        const totalEl = document.getElementById('summary-total-converted');
        const totalText = totalEl ? totalEl.textContent : 'UGX 0.00';

        const modalHtml = `
        <div id="mobile-phone-input-modal" style="position: fixed; inset: 0; background: rgba(5,8,18,0.85); backdrop-filter: blur(8px); z-index: 9998; display: flex; align-items: center; justify-content: center; padding: 1rem; animation: fadeInDown 0.2s ease;">
            <div style="max-width: 440px; width: 100%; background: #111827; border: 1px solid rgba(255,255,255,0.15); border-radius: 20px; padding: 1.5rem; box-shadow: 0 25px 60px rgba(0,0,0,0.7); color: #f3f4f6;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <span style="font-size: 1.4rem;">📱</span>
                        <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #ffffff;">Client Mobile Money STK</h3>
                    </div>
                    <button type="button" onclick="document.getElementById('mobile-phone-input-modal').remove()" style="background: transparent; border: none; color: #9ca3af; font-size: 1.5rem; cursor: pointer; line-height: 1;">&times;</button>
                </div>

                <p style="font-size: 0.85rem; color: #9ca3af; margin-bottom: 1rem; line-height: 1.45;">
                    Enter the client's phone number to prompt their handset for PIN approval:
                </p>

                <div style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); border-radius: 12px; padding: 0.8rem 1rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.82rem; color: #6ee7b7;">Payable via STK Push:</span>
                    <strong style="font-size: 1.15rem; color: #34d399; font-family: monospace;">${totalText}</strong>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.35rem; color: #ffffff;">Telecom Operator</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <label id="modal-mtn-label" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.5rem; border: 2px solid #facc15; border-radius: 8px; cursor: pointer; background: rgba(250,204,21,0.12); transition: all 0.2s;">
                            <input type="radio" name="modal_network" value="MTN" checked onchange="onModalNetworkChange()" style="accent-color: #facc15;">
                            <strong style="color: #facc15; font-size: 0.85rem;">MTN MoMo (*165#)</strong>
                        </label>
                        <label id="modal-airtel-label" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.5rem; border: 2px solid rgba(239,68,68,0.3); border-radius: 8px; cursor: pointer; background: rgba(239,68,68,0.05); transition: all 0.2s;">
                            <input type="radio" name="modal_network" value="Airtel" onchange="onModalNetworkChange()" style="accent-color: #ef4444;">
                            <strong style="color: #f87171; font-size: 0.85rem;">Airtel Money (*185#)</strong>
                        </label>
                    </div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.35rem; color: #ffffff;">Customer Phone Number *</label>
                    <input type="tel" id="modal-phone-input" placeholder="07XXXXXXXX or 256XXXXXXXXX" style="width: 100%; padding: 0.75rem 0.9rem; border-radius: 8px; border: 2px solid #10b981; background: rgba(0,0,0,0.3); color: #ffffff; font-size: 1.05rem; font-family: monospace; font-weight: 700;" oninput="onModalPhoneInput(this.value)" onkeydown="if(event.key==='Enter'){event.preventDefault();submitModalPhoneAndPush();}" autofocus>
                    <div style="font-size: 0.72rem; color: #9ca3af; margin-top: 0.35rem;">Operator auto-detects based on prefix (077/078 MTN, 070/075 Airtel). Press Enter to send.</div>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" onclick="document.getElementById('mobile-phone-input-modal').remove()" style="flex: 1; padding: 0.75rem; border-radius: 8px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: #ffffff; font-weight: 700; cursor: pointer;">Cancel</button>
                    <button type="button" onclick="submitModalPhoneAndPush()" style="flex: 2; padding: 0.75rem; border-radius: 8px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; color: #ffffff; font-weight: 800; font-size: 0.95rem; cursor: pointer; box-shadow: 0 4px 12px rgba(16,185,129,0.3);">📲 Send STK Prompt</button>
                </div>
            </div>
        </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        setTimeout(() => {
            const inp = document.getElementById('modal-phone-input');
            if (inp) inp.focus();
        }, 100);
    };

    window.onModalPhoneInput = function(val) {
        const clean = val.replace(/[^0-9]/g, '');
        let prefix = '';
        if (clean.startsWith('256') && clean.length >= 5) {
            prefix = clean.substring(3, 5);
        } else if (clean.startsWith('0') && clean.length >= 3) {
            prefix = clean.substring(1, 3);
        } else if (clean.length >= 2) {
            prefix = clean.substring(0, 2);
        }

        const mtnRadio = document.querySelector('input[name="modal_network"][value="MTN"]');
        const airtelRadio = document.querySelector('input[name="modal_network"][value="Airtel"]');

        if (['77', '78', '76', '39', '31'].includes(prefix) && mtnRadio) {
            mtnRadio.checked = true;
            onModalNetworkChange();
        } else if (['70', '75', '74'].includes(prefix) && airtelRadio) {
            airtelRadio.checked = true;
            onModalNetworkChange();
        }
    };

    window.onModalNetworkChange = function() {
        const mtnLabel = document.getElementById('modal-mtn-label');
        const airtelLabel = document.getElementById('modal-airtel-label');
        const mtnRadio = document.querySelector('input[name="modal_network"][value="MTN"]');
        if (!mtnLabel || !airtelLabel || !mtnRadio) return;

        if (mtnRadio.checked) {
            mtnLabel.style.borderColor = '#facc15';
            mtnLabel.style.background = 'rgba(250,204,21,0.12)';
            airtelLabel.style.borderColor = 'rgba(239,68,68,0.3)';
            airtelLabel.style.background = 'rgba(239,68,68,0.05)';
        } else {
            airtelLabel.style.borderColor = '#ef4444';
            airtelLabel.style.background = 'rgba(239,68,68,0.12)';
            mtnLabel.style.borderColor = 'rgba(250,204,21,0.3)';
            mtnLabel.style.background = 'rgba(250,204,21,0.05)';
        }
    };

    window.submitModalPhoneAndPush = function() {
        const val = (document.getElementById('modal-phone-input')?.value || '').trim();
        if (!val || val.length < 9) {
            alert('Please enter a valid mobile number (e.g. 0771234567).');
            return;
        }

        const mtnRadio = document.querySelector('input[name="modal_network"][value="MTN"]');
        const network = mtnRadio && mtnRadio.checked ? 'MTN' : 'Airtel';

        const mainPhone = document.getElementById('customer-phone-input');
        if (mainPhone) mainPhone.value = val;

        const mainNetworkRadio = document.querySelector(`input[name="mobile_network"][value="${network}"]`);
        if (mainNetworkRadio) {
            mainNetworkRadio.checked = true;
            updateMobileNetworkStyle();
        }

        const modal = document.getElementById('mobile-phone-input-modal');
        if (modal) modal.remove();

        posSelectPaymentMethod('mobile');
        posCalculateTotals();
        const totalEl = document.getElementById('summary-total-converted');
        const paidEl = document.getElementById('amount-paid-input');
        if (totalEl && paidEl) {
            const totalVal = parseFloat(totalEl.textContent.replace(/[^0-9.]/g, '')) || 0;
            if (!parseFloat(paidEl.value) || parseFloat(paidEl.value) < totalVal) {
                paidEl.value = totalVal.toFixed(2);
            }
        }

        if (typeof window.posPerformCheckout === 'function') {
            window.posPerformCheckout();
        } else {
            const posForm = document.querySelector('form.cart-summary');
            if (posForm) {
                const submitEvent = new Event('submit', { cancelable: true, bubbles: true });
                posForm.dispatchEvent(submitEvent);
            }
        }
    };

    window.updateMobileNetworkStyle = function() {
        const mtnLabel    = document.getElementById('mtn-label');
        const airtelLabel = document.getElementById('airtel-label');
        const mtnRadio    = document.querySelector('input[name="mobile_network"][value="MTN"]');
        if (!mtnLabel || !airtelLabel || !mtnRadio) return;
        if (mtnRadio.checked) {
            mtnLabel.style.borderColor    = '#facc15';
            mtnLabel.style.background     = 'rgba(250,204,21,0.12)';
            airtelLabel.style.borderColor = 'rgba(239,68,68,0.3)';
            airtelLabel.style.background  = 'rgba(239,68,68,0.05)';
        } else {
            airtelLabel.style.borderColor = '#ef4444';
            airtelLabel.style.background  = 'rgba(239,68,68,0.12)';
            mtnLabel.style.borderColor    = 'rgba(250,204,21,0.3)';
            mtnLabel.style.background     = 'rgba(250,204,21,0.05)';
        }
    };

    window.posShowToast = function(msg) {
        let t = document.getElementById('pos-add-toast');
        if (!t) {
            t = document.createElement('div');
            t.id = 'pos-add-toast';
            t.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;background:#10b981;color:#fff;padding:0.75rem 1.25rem;border-radius:8px;font-weight:700;font-size:0.9rem;box-shadow:0 8px 24px rgba(16,185,129,.4);transition:opacity .3s,transform .3s;opacity:0;transform:translateY(-10px);';
            document.body.appendChild(t);
        }
        t.textContent = msg;
        t.style.opacity = '1'; t.style.transform = 'translateY(0)';
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.style.opacity='0'; t.style.transform='translateY(-10px)'; }, 2000);
    };

    window.setExactAmount = function() {
        const total_el = document.getElementById('summary-total-converted');
        const paid_el  = document.getElementById('amount-paid-input');
        if (total_el && paid_el) {
            paid_el.value = parseFloat(total_el.textContent.replace(/[^\d.]/g,'') || 0).toFixed(2);
            paid_el._userTyped = false;
            posCalculateChange();
        }
    };
    window.addAmount = function(amt) {
        const paid_el = document.getElementById('amount-paid-input');
        if (paid_el) { paid_el.value = ((parseFloat(paid_el.value)||0)+amt).toFixed(2); paid_el._userTyped = true; posCalculateChange(); }
    };

    window.returnSaleToCart = function(sale) {
        if (!sale) return;

        // Clear existing cart items
        window.posCart.length = 0;

        // Extract items
        const items = sale.cartItems || sale.items || [];
        items.forEach(item => {
            const prodId = item.product_id;
            const name = item.product ? item.product.name : (item.name || 'Item');
            const price = parseFloat(item.price || 0);
            const qty = parseInt(item.quantity || item.qty || 1);

            // Get stock from DOM catalog if present
            let stock = 999;
            const card = document.querySelector(`.product-card[data-id="${prodId}"]`);
            if (card) {
                stock = parseInt(card.getAttribute('data-stock')) || 999;
            } else if (item.stock) {
                stock = parseInt(item.stock);
            }

            window.posCart.push({
                product_id: prodId,
                name: name,
                price: price,
                qty: qty,
                stock: stock
            });
        });

        // Populate checkout inputs
        const nameInput = document.getElementById('customer-name-input');
        const phoneInput = document.getElementById('customer-phone-input');
        const discountInput = document.getElementById('discount-input');
        const taxInput = document.getElementById('tax-input');
        const payMethodSelect = document.getElementById('payment-method-select');
        const paidInput = document.getElementById('amount-paid-input');

        if (nameInput) nameInput.value = sale.customer_name || '';
        if (phoneInput) phoneInput.value = sale.customer_phone || '';
        if (discountInput) discountInput.value = (sale.discount && parseFloat(sale.discount) > 0) ? parseFloat(sale.discount) : '';
        
        if (taxInput) {
            if (sale.tax_rate !== undefined && parseFloat(sale.tax_rate) > 0) {
                taxInput.value = parseFloat(sale.tax_rate);
            } else if (sale.tax !== undefined && parseFloat(sale.tax) > 0 && parseFloat(sale.subtotal) > 0) {
                const disc = parseFloat(sale.discount) || 0;
                const taxable = parseFloat(sale.subtotal) - disc;
                if (taxable > 0) {
                    taxInput.value = ((parseFloat(sale.tax) / taxable) * 100).toFixed(2);
                }
            } else {
                taxInput.value = '';
            }
        }

        if (payMethodSelect && sale.payment_method) {
            payMethodSelect.value = sale.payment_method;
            payMethodSelect.dispatchEvent(new Event('change'));
        }

        if (paidInput) {
            if (sale.amount_paid !== undefined && parseFloat(sale.amount_paid) > 0) {
                paidInput.value = parseFloat(sale.amount_paid).toFixed(2);
                paidInput._userTyped = true;
            } else {
                paidInput.value = '';
                paidInput._userTyped = false;
            }
        }

        // Remove offline sale from local queue if returning an offline sale
        if (sale.offline_id) {
            try {
                let queue = JSON.parse(localStorage.getItem('luxeshop_offline_sales') || '[]');
                queue = queue.filter(s => s.offline_id !== sale.offline_id);
                localStorage.setItem('luxeshop_offline_sales', JSON.stringify(queue));
                if (typeof updateOfflineQueueBadge === 'function') updateOfflineQueueBadge();
            } catch(e) {
                console.error('Offline queue update failed', e);
            }
        }

        // Re-render cart UI and recalculate totals
        window.posRenderCart();
        window.posCalculateTotals();

        // Close any open receipt modals
        const onlineModal = document.getElementById('receipt-modal');
        if (onlineModal) onlineModal.remove();
        const offlineModal = document.getElementById('offline-receipt-modal');
        if (offlineModal) offlineModal.remove();

        window.posShowToast('🛒 Order returned to cart for modification!');
    };
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Wire amount-paid input so manual typing sets the _userTyped flag
        const amountPaidInput = document.getElementById('amount-paid-input');
        if (amountPaidInput) {
            amountPaidInput.addEventListener('input', function() {
                this._userTyped = true;
                posCalculateChange();
            });
        }
        // Wire discount/tax inputs to unified posCalculateTotals
        const discountInput = document.getElementById('discount-input');
        const taxInput      = document.getElementById('tax-input');
        if (discountInput) discountInput.addEventListener('input', posCalculateTotals);
        if (taxInput)      taxInput.addEventListener('input',      posCalculateTotals);

        const catalogProducts = document.querySelectorAll('.product-card');
        const searchInput = document.getElementById('search-query');
        const barcodeInput = document.getElementById('barcode-scan-input');
        const clearCartBtn = document.getElementById('clear-cart-btn');
        const cameraToggle = document.getElementById('camera-scan-toggle');
        let html5QrcodeScanner = null;

        const paymentMethodSelect = document.getElementById('payment-method-select');
        const customerNameInput = document.getElementById('customer-name-input');
        const customerPhoneInput = document.getElementById('customer-phone-input');
        const customerNameLabel = document.getElementById('customer-name-label');
        const customerPhoneLabel = document.getElementById('customer-phone-label');

        if (paymentMethodSelect) {
            paymentMethodSelect.addEventListener('change', function() {
                if (this.value === 'debt') {
                    customerNameInput.required = true;
                    customerPhoneInput.required = true;
                    customerNameLabel.textContent = 'Customer Name *';
                    customerPhoneLabel.textContent = 'Customer Phone *';
                    customerNameInput.style.borderColor = 'var(--accent-amber)';
                    customerPhoneInput.style.borderColor = 'var(--accent-amber)';
                } else {
                    customerNameInput.required = false;
                    customerPhoneInput.required = false;
                    customerNameLabel.textContent = 'Customer Name';
                    customerPhoneLabel.textContent = 'Customer Phone';
                    customerNameInput.style.borderColor = 'var(--input-border)';
                    customerPhoneInput.style.borderColor = 'var(--input-border)';
                }
            });
        }

        if (clearCartBtn) {
            clearCartBtn.addEventListener('click', function() {
                posCart.length = 0;
                const paid_el = document.getElementById('amount-paid-input');
                if (paid_el) paid_el._userTyped = false;
                posRenderCart();
            });
        }

        posCalculateTotals();

        @if(isset($editingSaleData) && $editingSaleData)
            const editingSaleData = @json($editingSaleData);
            returnSaleToCart(editingSaleData);
        @endif

        // 1. Live Catalog Search
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = searchInput.value.toLowerCase();
                catalogProducts.forEach(card => {
                    const name = card.getAttribute('data-name').toLowerCase();
                    const sku = card.getAttribute('data-sku').toLowerCase();
                    if (name.includes(query) || sku.includes(query)) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }

        // 2. Hardware Barcode Listener / Input
        if (barcodeInput) {
            barcodeInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const barcode = barcodeInput.value.trim();
                    if (barcode) {
                        lookupProductBySku(barcode);
                        barcodeInput.value = '';
                    }
                }
            });
        }

        // Simulating physical keyboard scans (rapid keyboard inputs without element focus)
        let scannerBuffer = '';
        let lastKeyTime = Date.now();
        document.addEventListener('keydown', function(e) {
            if (['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
                return;
            }

            const currentTime = Date.now();
            if (currentTime - lastKeyTime > 50) {
                scannerBuffer = '';
            }

            if (e.key !== 'Enter' && e.key.length === 1) {
                scannerBuffer += e.key;
                lastKeyTime = currentTime;
            } else if (e.key === 'Enter') {
                if (scannerBuffer.length > 2) {
                    lookupProductBySku(scannerBuffer);
                }
                scannerBuffer = '';
            }
        });

        function lookupProductBySku(sku) {
            const matchedCard = Array.from(catalogProducts).find(card => card.getAttribute('data-sku') === sku);
            if (matchedCard) {
                const id = parseInt(matchedCard.getAttribute('data-id'));
                const name = matchedCard.getAttribute('data-name');
                const price = parseFloat(matchedCard.getAttribute('data-price'));
                const stock = parseInt(matchedCard.getAttribute('data-stock'));
                posAddToCart(id, name, price, stock);
                flashHighlight(matchedCard);
            } else {
                const scanUrl = '{{ route("pos.scan") }}';
                fetch(scanUrl + '?sku=' + encodeURIComponent(sku))
                    .then(res => {
                        if (!res.ok) throw new Error('Product not found');
                        return res.json();
                    })
                    .then(p => {
                        posAddToCart(p.id, p.name, parseFloat(p.price), parseInt(p.quantity ?? p.stock ?? 999));
                    })
                    .catch(() => alert(`Barcode "${sku}" not found in inventory.`));
            }
        }

        function flashHighlight(element) {
            element.style.borderColor = 'var(--primary)';
            element.style.boxShadow = '0 0 20px var(--primary-glow)';
            setTimeout(() => {
                element.style.borderColor = '';
                element.style.boxShadow = '';
            }, 500);
        }

        // 5. Webcam / Phone Camera Scanner using html5-qrcode
        cameraToggle.addEventListener('click', function() {
            const qrReaderDiv = document.getElementById('qr-reader');
            if (qrReaderDiv.style.display === 'none') {
                qrReaderDiv.style.display = 'block';
                cameraToggle.textContent = '🛑 Stop Camera Scanner';
                startCameraScanner();
            } else {
                qrReaderDiv.style.display = 'none';
                cameraToggle.textContent = '📷 Scan with Camera (Phone/Webcam)';
                stopCameraScanner();
            }
        });

        function startCameraScanner() {
            html5QrcodeScanner = new Html5Qrcode("qr-reader");
            const config = { fps: 10, qrbox: { width: 250, height: 150 } };
            
            html5QrcodeScanner.start(
                { facingMode: "environment" }, // Prioritize back phone camera
                config,
                (decodedText) => {
                    // Success callback
                    lookupProductBySku(decodedText.trim());
                    
                    // Add vibration feedback if supported
                    if (navigator.vibrate) {
                        navigator.vibrate(100);
                    }
                    
                    // Briefly flash scanner box
                    const readerBox = document.getElementById('qr-reader');
                    readerBox.style.borderColor = 'var(--accent-emerald)';
                    setTimeout(() => { readerBox.style.borderColor = 'var(--card-border)'; }, 300);
                },
                (errorMessage) => {
                    // Verbose debug, we can ignore common scan check failures
                }
            ).catch(err => {
                console.error("Camera startup error", err);
                alert("Failed to access camera: " + err);
                stopCameraScanner();
                document.getElementById('qr-reader').style.display = 'none';
                cameraToggle.textContent = '📷 Scan with Camera (Phone/Webcam)';
            });
        }

        function stopCameraScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    html5QrcodeScanner = null;
                }).catch(err => console.error("Error stopping scanner", err));
            }
        }

        // 6. Network Status & Offline Queue Auto-Sync System
        const posForm = document.querySelector('form.cart-summary');
        const netBadge = document.getElementById('network-status-badge');
        const queueBadge = document.getElementById('offline-queue-badge');

        function updateNetworkStatus() {
            if (navigator.onLine) {
                netBadge.textContent = '🟢 Online Mode';
                netBadge.className = 'badge badge-received';
                syncOfflineSales();
            } else {
                netBadge.textContent = '🔴 Offline Mode';
                netBadge.className = 'badge badge-pending';
            }
            updateOfflineQueueBadge();
        }

        window.addEventListener('online', updateNetworkStatus);
        window.addEventListener('offline', updateNetworkStatus);
        updateNetworkStatus();

        function getOfflineQueue() {
            try {
                return JSON.parse(localStorage.getItem('luxeshop_offline_sales') || '[]');
            } catch(e) {
                return [];
            }
        }

        function saveOfflineQueue(queue) {
            localStorage.setItem('luxeshop_offline_sales', JSON.stringify(queue));
            updateOfflineQueueBadge();
        }

        function updateOfflineQueueBadge() {
            const queue = getOfflineQueue();
            if (queue.length > 0) {
                queueBadge.style.display = 'inline-block';
                queueBadge.textContent = `${queue.length} Offline Sale(s) Pending Sync`;
            } else {
                queueBadge.style.display = 'none';
            }
        }

        window.posPerformCheckout = async function() {
            if (!posForm) return;

            if (posCart.length === 0) {
                alert('Your cart is empty. Please add items before checking out.');
                return;
            }

            // Ensure cart hidden input is up to date before submit
            const cartInput = document.getElementById('cart-data-input');
            if (cartInput) cartInput.value = JSON.stringify(posCart.map(i => ({ product_id: i.product_id, qty: i.qty })));

            // If offline, process locally
            if (!navigator.onLine) {
                processOfflineCheckout();
                return;
            }

            const amountPaidInput = document.getElementById('amount-paid-input');
            const totalConvertedDisplay = document.getElementById('summary-total-converted');
            const totalVal = parseFloat((totalConvertedDisplay?.textContent || '').replace(/[^0-9.]/g, '')) || 0;
            let amountPaid = parseFloat(amountPaidInput?.value) || 0;
            const paymentMethod = document.getElementById('payment-method-select')?.value || 'cash';

            // Auto-fill exact amount for non-debt if empty
            if (paymentMethod !== 'debt' && amountPaid === 0 && totalVal > 0) {
                amountPaid = totalVal;
                if (amountPaidInput) amountPaidInput.value = totalVal.toFixed(2);
                posCalculateChange();
            }

            // 📱 MOBILE MONEY validation
            if (paymentMethod === 'mobile') {
                const phoneVal = (document.getElementById('customer-phone-input')?.value || '').trim();
                if (!phoneVal) {
                    openMobileMoneyPhoneModal();
                    return;
                }
                posShowToast('📲 Initiating STK Push to ' + phoneVal + '...');
            }

            const submitBtn = posForm.querySelector('button[type="submit"]');
            const payDirectMobileBtn = document.getElementById('cart-pay-mobile-direct-btn');
            const origSubmitText = submitBtn ? submitBtn.innerHTML : '';
            const origDirectText = payDirectMobileBtn ? payDirectMobileBtn.innerHTML : '';

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = paymentMethod === 'mobile' ? '📲 Sending STK Push...' : '⏳ Processing...';
            }
            if (payDirectMobileBtn) {
                payDirectMobileBtn.disabled = true;
                payDirectMobileBtn.innerHTML = '<span style="font-size: 1.25rem;">📲</span><span>Sending STK Prompt...</span>';
            }

            try {
                const formData = new FormData(posForm);
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

                const response = await fetch(posForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    if (data.is_mobile && data.iotec_transaction) {
                        // Show interactive STK Push Waiting Screen (Gita reference)
                        openStkWaitingModal(data.sale, data.iotec_transaction, data.is_demo);
                    } else {
                        renderSaleReceiptModal(data.sale, data.iotec_transaction);
                        posCart.length = 0;
                        posRenderCart();
                        if (amountPaidInput) amountPaidInput.value = '';
                        posCalculateChange();
                    }
                } else {
                    let errorMsg = data.error;
                    if (!errorMsg && data.message) errorMsg = data.message;
                    if (!errorMsg && data.errors) {
                        errorMsg = Object.values(data.errors).flat().join('\n');
                    }
                    alert(errorMsg || 'Checkout failed. Please verify details.');
                }
            } catch (err) {
                console.error('AJAX checkout error:', err);
                alert('Checkout failed: ' + (err.message || 'Server connection error'));
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origSubmitText;
                }
                if (payDirectMobileBtn) {
                    payDirectMobileBtn.disabled = false;
                    payDirectMobileBtn.innerHTML = origDirectText;
                }
            }
        };

        if (posForm) {
            posForm.addEventListener('submit', function(e) {
                e.preventDefault();
                window.posPerformCheckout();
            });
        }

        window.openStkWaitingModal = function(sale, iotecTx, isDemo = false) {
            let existing = document.getElementById('stk-waiting-modal');
            if (existing) existing.remove();

            window._activeStkTx = iotecTx;
            window._activeStkSale = sale;
            window._stkPollCount = 0;
            if (window._stkPollInterval) clearInterval(window._stkPollInterval);

            const totalUgx = parseFloat(sale.total || iotecTx.net_amount || (parseFloat(iotecTx.amount) + 500)).toLocaleString();
            const phone = iotecTx.phone_number || sale.customer_phone || 'Customer Phone';
            const network = iotecTx.network || 'MTN';
            const networkBadge = network === 'MTN'
                ? '<span style="background:rgba(250,204,21,0.18); color:#facc15; border:1px solid rgba(250,204,21,0.4); border-radius:6px; padding:2px 8px; font-weight:800; font-size:11px;">MTN MoMo (*165#)</span>'
                : '<span style="background:rgba(239,68,68,0.18); color:#f87171; border:1px solid rgba(239,68,68,0.4); border-radius:6px; padding:2px 8px; font-weight:800; font-size:11px;">Airtel Money (*185#)</span>';

            const modalHtml = `
            <div id="stk-waiting-modal" style="position: fixed; inset: 0; background: rgba(5,8,18,0.88); backdrop-filter: blur(10px); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 1rem; animation: fadeInDown 0.25s ease;">
                <div style="max-width: 480px; width: 100%; background: #111827; border: 1px solid rgba(255,255,255,0.12); border-radius: 24px; box-shadow: 0 25px 60px rgba(0,0,0,0.8); overflow: hidden; position: relative; color: #f3f4f6;">
                    
                    <!-- Top Rainbow Gradient Line -->
                    <div style="height: 4px; width: 100%; background: linear-gradient(90deg, #f59e0b 0%, #1e5ae3 50%, #10b981 100%);"></div>

                    <!-- Header -->
                    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.08); background: rgba(20,29,48,0.6); display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #1e5ae3, #0d3899); display: flex; align-items: center; justify-content: center; color: #ffffff; box-shadow: 0 4px 12px rgba(30,90,227,0.3);">
                                <svg style="width: 22px; height: 22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #ffffff;">Mobile Money STK Push</h3>
                                <p style="margin: 0; font-size: 0.75rem; color: #9ca3af;">ioTec Pay · Uganda</p>
                            </div>
                        </div>
                        <div>
                            <span id="stk-status-pill" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; background: rgba(245,158,11,0.15); color: #fcd34d; border: 1px solid rgba(245,158,11,0.3);">
                                <span id="stk-ping-dot" style="width: 8px; height: 8px; border-radius: 9999px; background: #fbbf24; animation: stk-ping 1.5s cubic-bezier(0,0,0.2,1) infinite;"></span>
                                <span id="stk-status-label">Awaiting PIN</span>
                            </span>
                        </div>
                    </div>

                    <!-- Body -->
                    <div style="padding: 1.5rem; text-align: center;">
                        
                        <!-- Animated Radar Graphic -->
                        <div style="position: relative; width: 84px; height: 84px; margin: 0 auto 1.2rem; display: flex; align-items: center; justify-content: center;">
                            <div class="stk-radar-ring"></div>
                            <div id="stk-center-icon-wrap" style="width: 60px; height: 60px; border-radius: 9999px; background: linear-gradient(135deg, rgba(245,158,11,0.25), rgba(245,158,11,0.05)); border: 1px solid rgba(245,158,11,0.4); display: flex; align-items: center; justify-content: center; color: #fbbf24; transition: all 0.3s ease;">
                                <svg id="stk-center-icon" style="width: 28px; height: 28px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                        </div>

                        <h4 style="margin: 0 0 0.4rem; font-size: 1.2rem; font-weight: 800; color: #ffffff;" id="stk-headline">Please check client's phone</h4>
                        <p style="margin: 0 0 1.25rem; font-size: 0.85rem; color: #d1d5db; line-height: 1.45;" id="stk-description">
                            A secure STK prompt was sent to <strong id="stk-phone-target" style="color: #fde047; font-family: monospace; font-size: 0.95rem;">${phone}</strong>.<br>
                            Please ask the client to enter their secret PIN on their phone to approve.
                        </p>

                        <!-- Transaction Summary Card -->
                        <div style="background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 0.9rem 1.1rem; text-align: left; margin-bottom: 1rem; font-size: 0.82rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                <span style="color: #9ca3af;">Total Amount:</span>
                                <span style="font-weight: 900; font-size: 1.15rem; color: #34d399; font-family: monospace;">UGX ${totalUgx}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                <span style="color: #9ca3af;">Network:</span>
                                <span>${networkBadge}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                <span style="color: #9ca3af;">Customer:</span>
                                <span style="font-weight: 700; color: #ffffff;">${sale.customer_name || 'Walk-in Customer'}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #9ca3af;">Reference:</span>
                                <span style="font-family: monospace; color: #9ca3af; font-size: 0.75rem;">${iotecTx.external_id || ('POS-COL-' + sale.id)}</span>
                            </div>
                        </div>

                        <!-- Helpful Notice -->
                        <div style="background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.25); border-radius: 10px; padding: 0.6rem 0.85rem; font-size: 0.76rem; color: #93c5fd; text-align: left; margin-bottom: 1.2rem; display: flex; align-items: flex-start; gap: 0.5rem;">
                            <span style="font-size: 1rem; line-height: 1;">💡</span>
                            <span><strong>Prompt taking a few seconds?</strong> Telecom STK push prompts usually appear within 5–25 seconds. Keep phone unlocked. This screen will auto-refresh once approved.</span>
                        </div>

                        <!-- Action Buttons -->
                        <div style="display: flex; flex-direction: column; gap: 0.55rem;">
                            <button type="button" onclick="checkStkPushManual()" id="stk-refresh-btn" style="width: 100%; padding: 0.75rem; border-radius: 10px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: #ffffff; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: all 0.2s;">
                                <span id="stk-refresh-spinner" style="display: inline-block;">🔄</span>
                                <span>Client has entered PIN / Refresh Status</span>
                            </button>

                            <button type="button" onclick="cancelStkPushPrompt()" style="background: transparent; border: none; color: #9ca3af; font-size: 0.78rem; cursor: pointer; padding: 0.4rem; text-decoration: underline;">
                                Cancel STK Push and return to cart
                            </button>
                        </div>

                    </div>

                    <!-- Footer -->
                    <div style="background: #0b0f19; padding: 0.75rem; text-align: center; font-size: 0.72rem; color: #6b7280; border-top: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                        <span style="color: #10b981;">🛡️</span>
                        <span>Secured by ioTec Pay OAuth 2.0 & Mobile Money Integration</span>
                    </div>

                </div>
            </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Auto poll every 2.5 seconds
            window._stkPollInterval = setInterval(pollStkPushStatus, 2500);
        };

        window.pollStkPushStatus = async function() {
            if (!window._activeStkTx) return;
            window._stkPollCount++;

            try {
                const res = await fetch(window._stkStatusUrlBase + window._activeStkTx.id, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (data.status === 'complete') {
                    clearInterval(window._stkPollInterval);
                    onStkPushApproved(data.sale || window._activeStkSale, data.transaction || window._activeStkTx);
                    return;
                }

                if (data.status === 'failed' || data.status === 'cancelled') {
                    clearInterval(window._stkPollInterval);
                    onStkPushFailed(data.message || 'Payment was declined or cancelled on client handset.');
                    return;
                }
            } catch (e) {
                console.warn('STK Polling notice:', e);
            }

            if (window._stkPollCount > 70) {
                clearInterval(window._stkPollInterval);
            }
        };

        window.checkStkPushManual = async function() {
            const spinner = document.getElementById('stk-refresh-spinner');
            if (spinner) spinner.style.transform = 'rotate(360deg)';
            await pollStkPushStatus();
            setTimeout(() => { if (spinner) spinner.style.transform = ''; }, 400);
        };

        window.simulateStkPush = async function(action) {
            if (!window._activeStkTx) return;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
                const res = await fetch(window._stkSimulateUrlBase + window._activeStkTx.id, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ action: action })
                });
                const data = await res.json();
                if (data.status === 'complete') {
                    clearInterval(window._stkPollInterval);
                    onStkPushApproved(data.sale || window._activeStkSale, data.transaction || window._activeStkTx);
                } else {
                    clearInterval(window._stkPollInterval);
                    onStkPushFailed('Simulated: Client cancelled prompt or entered incorrect PIN.');
                }
            } catch (e) {
                console.error('Simulate STK error:', e);
            }
        };

        window.cancelStkPushPrompt = async function() {
            if (!confirm('Are you sure you want to cancel this STK payment and return to the cart?')) {
                return;
            }
            clearInterval(window._stkPollInterval);
            if (window._activeStkTx) {
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
                    await fetch(window._stkCancelUrlBase + window._activeStkTx.id, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });
                } catch (e) {
                    console.error('Cancel STK error:', e);
                }
            }
            const modal = document.getElementById('stk-waiting-modal');
            if (modal) modal.remove();
            posShowToast('STK push cancelled. Cart items preserved.');
        };

        window.onStkPushApproved = function(sale, tx) {
            const pill = document.getElementById('stk-status-pill');
            const label = document.getElementById('stk-status-label');
            const dot = document.getElementById('stk-ping-dot');
            const iconWrap = document.getElementById('stk-center-icon-wrap');
            const headline = document.getElementById('stk-headline');
            const desc = document.getElementById('stk-description');

            if (pill) {
                pill.style.background = 'rgba(16,185,129,0.2)';
                pill.style.borderColor = '#10b981';
                pill.style.color = '#34d399';
            }
            if (label) label.textContent = 'Payment Confirmed! ✓';
            if (dot) dot.style.display = 'none';
            if (iconWrap) {
                iconWrap.style.background = 'rgba(16,185,129,0.25)';
                iconWrap.style.borderColor = '#10b981';
                iconWrap.style.color = '#10b981';
                iconWrap.innerHTML = '<span style="font-size:32px; font-weight:800;">✓</span>';
            }
            if (headline) headline.textContent = 'Payment Confirmed!';
            if (desc) desc.textContent = 'Mobile Money authorization confirmed successfully.';

            posShowToast('✅ STK Payment Confirmed!');

            // Clear cart
            posCart.length = 0;
            posRenderCart();
            const paidInput = document.getElementById('amount-paid-input');
            if (paidInput) paidInput.value = '';
            posCalculateChange();

            setTimeout(() => {
                const modal = document.getElementById('stk-waiting-modal');
                if (modal) modal.remove();
                renderSaleReceiptModal(sale, tx);
            }, 850);
        };

        window.onStkPushFailed = function(msg) {
            const pill = document.getElementById('stk-status-pill');
            const label = document.getElementById('stk-status-label');
            const dot = document.getElementById('stk-ping-dot');
            const iconWrap = document.getElementById('stk-center-icon-wrap');
            const headline = document.getElementById('stk-headline');
            const desc = document.getElementById('stk-description');

            if (pill) {
                pill.style.background = 'rgba(239,68,68,0.2)';
                pill.style.borderColor = '#ef4444';
                pill.style.color = '#f87171';
            }
            if (label) label.textContent = 'Payment Declined';
            if (dot) dot.style.display = 'none';
            if (iconWrap) {
                iconWrap.style.background = 'rgba(239,68,68,0.2)';
                iconWrap.style.borderColor = '#ef4444';
                iconWrap.style.color = '#ef4444';
                iconWrap.innerHTML = '<span style="font-size:30px;">✕</span>';
            }
            if (headline) headline.textContent = 'Payment Failed';
            if (desc) desc.textContent = msg || 'Mobile Money payment was declined or cancelled on customer handset.';

            alert(msg || 'Mobile Money payment was declined or cancelled.');
            setTimeout(() => {
                const modal = document.getElementById('stk-waiting-modal');
                if (modal) modal.remove();
            }, 1200);
        };

        window.checkStkPushStatus = async function(txId, saleId) {
            try {
                const res = await fetch(window._stkStatusUrlBase + txId, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await res.json();
                const badge = document.getElementById('stk-status-badge-' + saleId);
                if (badge && data.status) {
                    badge.textContent = data.status.toUpperCase();
                    badge.style.background = data.status === 'complete' ? '#10b981' : (data.status === 'failed' ? '#ef4444' : '#f59e0b');
                    posShowToast('STK Status: ' + data.status.toUpperCase());
                }
            } catch(e) {
                console.error('Failed to check STK status', e);
            }
        };

        function renderSaleReceiptModal(sale, iotecTx = null) {
            window._currentReceiptSale = sale;
            let existing = document.getElementById('receipt-modal');
            if (existing) existing.remove();

            const itemsRows = (sale.items || []).map(item => `
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 0.45rem 0;"><strong>${item.product ? item.product.name : 'Item'}</strong></td>
                    <td style="text-align: center; padding: 0.45rem 0;">${item.quantity}</td>
                    <td style="text-align: right; padding: 0.45rem 0;">${posBaseSymbol}${parseFloat(item.price).toFixed(2)}</td>
                    <td style="text-align: right; padding: 0.45rem 0;">${posBaseSymbol}${parseFloat(item.total).toFixed(2)}</td>
                </tr>
            `).join('');

            const dateStr = sale.created_at ? new Date(sale.created_at).toLocaleString() : new Date().toLocaleString();
            const cashierName = sale.user ? sale.user.name : '{{ Auth::user()->name }}';
            const customerName = sale.customer_name || 'Walk-in Customer';

            const isMobile = sale.payment_method === 'mobile' || iotecTx;
            const totalVal = parseFloat(sale.total) || 0;
            const paidVal = parseFloat(sale.amount_paid) || 0;
            const isMobilePending = isMobile && (!iotecTx || iotecTx.status !== 'complete');
            const effectivePaid = isMobilePending ? 0 : paidVal;
            const balanceDue = Math.max(0, totalVal - effectivePaid);
            const isUnpaid = sale.payment_status === 'unpaid' || sale.payment_method === 'debt' || balanceDue > 0.009 || isMobilePending;
            const receiptStatusBanner = isUnpaid
                ? `<div style="text-align: center; border: 2px solid #dc2626; color: #dc2626; background: #fef2f2; padding: 0.45rem 0.75rem; border-radius: 6px; font-weight: 900; font-size: 0.85rem; letter-spacing: 0.04em; margin-bottom: 0.85rem;">
                    ${isMobilePending ? 'PAYMENT PENDING — NOT PAID' : (effectivePaid > 0 ? 'PARTIALLY PAID — BALANCE DUE' : 'UNPAID CREDIT / DEBT BILL')}
                   </div>`
                : `<div style="text-align: center; border: 1.5px solid #059669; color: #059669; background: #ecfdf5; padding: 0.35rem 0.75rem; border-radius: 6px; font-weight: 800; font-size: 0.8rem; letter-spacing: 0.04em; margin-bottom: 0.85rem;">
                    PAID IN FULL
                   </div>`;
            const mobileStatusHtml = isMobile ? `
                <div class="no-print" style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.3); border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1rem; text-align: center;">
                    <div style="font-weight: 800; font-size: 0.88rem; color: #065f46; display: flex; align-items: center; justify-content: center; gap: 0.4rem; flex-wrap: wrap;">
                        <span>📱 Mobile Money STK Push</span>
                        <span id="stk-status-badge-${sale.id}" style="background: ${iotecTx && iotecTx.status==='complete'?'#10b981':(iotecTx && iotecTx.status==='failed'?'#ef4444':'#f59e0b')}; color: white; border-radius: 12px; padding: 0.15rem 0.6rem; font-size: 0.72rem; text-transform: uppercase; font-weight: 800;">
                            ${iotecTx ? iotecTx.status.toUpperCase() : 'PENDING'}
                        </span>
                    </div>
                    <div style="font-size: 0.78rem; color: #374151; margin-top: 0.3rem;">
                        STK prompt sent to: <strong>${iotecTx ? iotecTx.phone_number : (sale.customer_phone || 'Customer Phone')}</strong>
                    </div>
                    ${iotecTx && iotecTx.id ? `
                        <button type="button" onclick="checkStkPushStatus(${iotecTx.id}, ${sale.id})" style="margin-top: 0.45rem; padding: 0.35rem 0.85rem; font-size: 0.75rem; background: #059669; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-weight: 700;">
                            🔄 Refresh STK Payment Status
                        </button>
                    ` : ''}
                </div>
            ` : '';

            const modalHtml = `
                <div id="receipt-modal" style="position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.75); display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                    <div class="glass-panel" style="width: 100%; max-width: 430px; background: #ffffff; color: #000000; padding: 2rem; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); position: relative;" id="printable-receipt-area">
                        
                        ${mobileStatusHtml}
                        ${receiptStatusBanner}

                        <!-- Receipt Header -->
                        <div style="text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 1rem; margin-bottom: 1rem;">
                            <div class="no-print" style="display: inline-block; background: #10b981; color: white; border-radius: 50%; width: 34px; height: 34px; line-height: 34px; font-size: 1.1rem; margin-bottom: 0.5rem; text-align: center;">✓</div>
                            <h2 style="font-size: 1.35rem; font-weight: 800; text-transform: uppercase; margin: 0; color: #000000;">{{ $tenant->name ?? 'Luxeshop' }}</h2>
                            @if(isset($tenant) && $tenant->business_name)
                                <p style="font-size: 0.8rem; color: #444; margin: 0.2rem 0;">{{ $tenant->business_name }}</p>
                            @endif
                            <p style="font-size: 0.75rem; color: #555; margin: 0.2rem 0;">{{ $tenant->location ?? 'Main Store Location' }}</p>
                            <p style="font-size: 0.75rem; color: #555; margin: 0.2rem 0;">Tel: {{ $tenant->phone ?? '+256 700 000 000' }}</p>
                        </div>

                        <!-- Receipt Metadata -->
                        <div style="font-size: 0.8rem; color: #333; margin-bottom: 1rem; display: flex; flex-direction: column; gap: 0.25rem;">
                            <div style="display: flex; justify-content: space-between;"><span>Receipt No:</span><strong>#${sale.id}</strong></div>
                            <div style="display: flex; justify-content: space-between;"><span>Date & Time:</span><span>${dateStr}</span></div>
                            <div style="display: flex; justify-content: space-between;"><span>Server:</span><span>${cashierName}</span></div>
                            <div style="display: flex; justify-content: space-between;"><span>Customer:</span><span>${customerName}</span></div>
                            ${sale.customer_phone ? `<div style="display: flex; justify-content: space-between;"><span>Phone:</span><span>${sale.customer_phone}</span></div>` : ''}
                        </div>

                        <!-- Items Table -->
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem; margin-bottom: 1rem; color: #000000;">
                            <thead>
                                <tr style="border-bottom: 1px solid #000; border-top: 1px solid #000;">
                                    <th style="text-align: left; padding: 0.4rem 0;">Item</th>
                                    <th style="text-align: center; padding: 0.4rem 0;">Qty</th>
                                    <th style="text-align: right; padding: 0.4rem 0;">Price</th>
                                    <th style="text-align: right; padding: 0.4rem 0;">Total</th>
                                </tr>
                            </thead>
                            <tbody>${itemsRows}</tbody>
                        </table>

                        <!-- Totals Summary -->
                        <div style="border-top: 2px dashed #ccc; padding-top: 0.75rem; font-size: 0.85rem; display: flex; flex-direction: column; gap: 0.35rem; color: #000000;">
                            <div style="display: flex; justify-content: space-between;"><span>Subtotal:</span><span>${posBaseSymbol}${parseFloat(sale.subtotal).toFixed(2)}</span></div>
                            ${parseFloat(sale.discount) > 0 ? `<div style="display: flex; justify-content: space-between;"><span>Discount:</span><span>-${posBaseSymbol}${parseFloat(sale.discount).toFixed(2)}</span></div>` : ''}
                            ${parseFloat(sale.tax) > 0 ? `<div style="display: flex; justify-content: space-between;"><span>Tax:</span><span>+${posBaseSymbol}${parseFloat(sale.tax).toFixed(2)}</span></div>` : ''}
                            <div style="display: flex; justify-content: space-between; font-size: 1.15rem; font-weight: 800; border-top: 1px solid #000; padding-top: 0.4rem; margin-top: 0.25rem;">
                                <span>TOTAL DUE:</span><span>${posBaseSymbol}${parseFloat(sale.total).toFixed(2)}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-weight: 600; padding-top: 0.25rem;">
                                <span>${isUnpaid ? 'Amount Paid' : 'Paid'} (${(sale.payment_method || 'CASH').toUpperCase()}):</span><span>${posBaseSymbol}${effectivePaid.toFixed(2)}</span>
                            </div>
                            ${isUnpaid
                                ? `<div style="display: flex; justify-content: space-between; font-weight: 800; color: #dc2626;">
                                    <span>BALANCE DUE:</span><span>${posBaseSymbol}${balanceDue.toFixed(2)}</span>
                                   </div>`
                                : `<div style="display: flex; justify-content: space-between; font-weight: 700; color: #059669;">
                                    <span>Change:</span><span>${posBaseSymbol}${parseFloat(sale.change_amount).toFixed(2)}</span>
                                   </div>`
                            }
                        </div>

                        <!-- Receipt Footer Message -->
                        <div style="text-align: center; margin-top: 1.25rem; border-top: 1px solid #ddd; padding-top: 0.75rem; font-size: 0.75rem; color: #666666;">
                            <p style="margin: 0; font-weight: 600;">Thank you for your business!</p>
                            <p style="margin: 0.2rem 0 0 0;">Please keep this receipt for records.</p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="no-print" style="margin-top: 1.25rem; display: flex; gap: 0.5rem; justify-content: space-between; flex-wrap: wrap;">
                            <button type="button" onclick="returnSaleToCart(window._currentReceiptSale)" class="btn-secondary" style="flex: 1; padding: 0.75rem; font-size: 0.85rem; border: 1px solid #2563eb; background: #eff6ff; color: #1d4ed8; font-weight: 700; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                🛒 Return to Cart
                            </button>
                            <button type="button" onclick="window.print()" class="btn-primary" style="flex: 1; padding: 0.75rem; font-size: 0.85rem; background: #000000; color: #ffffff; border: none; font-weight: 700; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                🖨️ Print Receipt
                            </button>
                            <button type="button" onclick="document.getElementById('receipt-modal').remove()" class="btn-secondary" style="padding: 0.75rem 1rem; font-size: 0.85rem; border: 1px solid #ccc; background: #f3f4f6; color: #333333; font-weight: 600; border-radius: 6px; cursor: pointer;">
                                ✕ Close
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        function processOfflineCheckout() {
            const customerName = document.getElementById('customer-name-input')?.value || 'Walk-in Customer';
            const customerPhone = document.getElementById('customer-phone-input')?.value || '';
            const discount = parseFloat(document.getElementById('discount-input')?.value) || 0;
            const taxRate = parseFloat(document.getElementById('tax-input')?.value) || 0;
            const paymentMethod = document.getElementById('payment-method-select')?.value || 'cash';
            const amountPaid = parseFloat(document.getElementById('amount-paid-input')?.value) || 0;

            let subtotal = 0;
            posCart.forEach(i => subtotal += i.price * i.qty);
            const total = subtotal - discount + ((subtotal - discount) * (taxRate / 100));
            const change = Math.max(0, amountPaid - total);

            const offlineSale = {
                offline_id: 'OFFLINE-' + Math.floor(100000 + Math.random() * 900000),
                cart: JSON.stringify(posCart.map(i => ({ product_id: i.product_id, qty: i.qty }))),
                cartItems: [...posCart],
                customer_name: customerName,
                customer_phone: customerPhone,
                discount: discount,
                tax_rate: taxRate,
                payment_method: paymentMethod,
                amount_paid: amountPaid,
                subtotal: subtotal,
                total: total,
                change: change,
                currency_code: '{{ $baseCurrency->code }}',
                created_at: new Date().toLocaleString()
            };

            const queue = getOfflineQueue();
            queue.push(offlineSale);
            saveOfflineQueue(queue);

            // Render offline receipt modal
            renderOfflineReceiptModal(offlineSale);

            // Clear cart
            posCart.length = 0;
            const paid_el = document.getElementById('amount-paid-input');
            if (paid_el) paid_el._userTyped = false;
            posRenderCart();
        }

        function renderOfflineReceiptModal(sale) {
            window._currentOfflineReceiptSale = sale;
            let modal = document.getElementById('offline-receipt-modal');
            if (modal) modal.remove();

            const itemsRows = sale.cartItems.map(item => `
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 0.4rem 0;"><strong>${item.name}</strong></td>
                    <td style="text-align: center; padding: 0.4rem 0;">${item.qty}</td>
                    <td style="text-align: right; padding: 0.4rem 0;">${posBaseSymbol}${item.price.toFixed(2)}</td>
                    <td style="text-align: right; padding: 0.4rem 0;">${posBaseSymbol}${(item.price * item.qty).toFixed(2)}</td>
                </tr>
            `).join('');

            const modalHtml = `
                <div id="offline-receipt-modal" style="position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.75); display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                    <div class="glass-panel" style="width: 100%; max-width: 420px; background: #ffffff; color: #000000; padding: 2rem; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); position: relative;" id="printable-offline-receipt-area">
                        <div style="text-align: center; background: #fef3c7; color: #92400e; padding: 0.4rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem; margin-bottom: 0.75rem; border: 1px solid #fcd34d;">
                            📡 OFFLINE RECEIPT (Queued for Database Sync)
                        </div>
                        ${(sale.payment_method === 'debt' || sale.amount_paid < sale.total)
                            ? `<div style="text-align: center; border: 2px solid #dc2626; color: #dc2626; background: #fef2f2; padding: 0.4rem; border-radius: 6px; font-weight: 900; font-size: 0.82rem; margin-bottom: 0.75rem;">
                                ${sale.amount_paid > 0 ? 'PARTIALLY PAID — BALANCE DUE' : 'UNPAID CREDIT / DEBT BILL'}
                               </div>`
                            : `<div style="text-align: center; border: 1.5px solid #059669; color: #059669; background: #ecfdf5; padding: 0.35rem; border-radius: 6px; font-weight: 800; font-size: 0.78rem; margin-bottom: 0.75rem;">
                                PAID IN FULL
                               </div>`
                        }
                        <div style="text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 1rem; margin-bottom: 1rem;">
                            <h2 style="font-size: 1.4rem; font-weight: 800; text-transform: uppercase; margin: 0; color: #000000;">{{ $tenant->name ?? 'Luxeshop Boutique' }}</h2>
                            <p style="font-size: 0.75rem; color: #555; margin: 0.2rem 0;">{{ $tenant->location ?? 'Main Store Location' }}</p>
                        </div>
                        <div style="font-size: 0.8rem; color: #333; margin-bottom: 1rem; display: flex; flex-direction: column; gap: 0.25rem;">
                            <div style="display: flex; justify-content: space-between;"><span>Receipt No:</span><strong>#${sale.offline_id}</strong></div>
                            <div style="display: flex; justify-content: space-between;"><span>Date & Time:</span><span>${sale.created_at}</span></div>
                            <div style="display: flex; justify-content: space-between;"><span>Server:</span><span>{{ Auth::user()->name }}</span></div>
                            <div style="display: flex; justify-content: space-between;"><span>Customer:</span><span>${sale.customer_name}</span></div>
                        </div>
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem; margin-bottom: 1rem; color: #000000;">
                            <thead>
                                <tr style="border-bottom: 1px solid #000; border-top: 1px solid #000;">
                                    <th style="text-align: left; padding: 0.4rem 0;">Item</th>
                                    <th style="text-align: center; padding: 0.4rem 0;">Qty</th>
                                    <th style="text-align: right; padding: 0.4rem 0;">Price</th>
                                    <th style="text-align: right; padding: 0.4rem 0;">Total</th>
                                </tr>
                            </thead>
                            <tbody>${itemsRows}</tbody>
                        </table>
                        <div style="border-top: 2px dashed #ccc; padding-top: 0.75rem; font-size: 0.85rem; display: flex; flex-direction: column; gap: 0.35rem; color: #000000;">
                            <div style="display: flex; justify-content: space-between;"><span>Subtotal:</span><span>${posBaseSymbol}${sale.subtotal.toFixed(2)}</span></div>
                            <div style="display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: 800; border-top: 1px solid #000; padding-top: 0.4rem; margin-top: 0.25rem;">
                                <span>TOTAL DUE:</span><span>${posBaseSymbol}${sale.total.toFixed(2)}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-weight: 600;"><span>${(sale.payment_method === 'debt' || sale.amount_paid < sale.total) ? 'Amount Paid' : 'Paid'} (${sale.payment_method.toUpperCase()}):</span><span>${posBaseSymbol}${sale.amount_paid.toFixed(2)}</span></div>
                            ${(sale.payment_method === 'debt' || sale.amount_paid < sale.total)
                                ? `<div style="display: flex; justify-content: space-between; font-weight: 800; color: #dc2626;"><span>BALANCE DUE:</span><span>${posBaseSymbol}${Math.max(0, sale.total - sale.amount_paid).toFixed(2)}</span></div>`
                                : `<div style="display: flex; justify-content: space-between; font-weight: 600; color: #059669;"><span>Change:</span><span>${posBaseSymbol}${sale.change.toFixed(2)}</span></div>`
                            }
                        </div>
                        <div class="no-print" style="margin-top: 1.5rem; display: flex; gap: 0.5rem; justify-content: space-between; flex-wrap: wrap;">
                            <button type="button" onclick="returnSaleToCart(window._currentOfflineReceiptSale)" class="btn-secondary" style="flex: 1; padding: 0.75rem; font-size: 0.85rem; border: 1px solid #2563eb; background: #eff6ff; color: #1d4ed8; font-weight: 700; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                🛒 Return to Cart
                            </button>
                            <button type="button" onclick="window.print()" class="btn-primary" style="flex: 1; padding: 0.75rem; font-size: 0.85rem; background: #000000; color: #ffffff; border: none; font-weight: 700; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                🖨️ Print Receipt
                            </button>
                            <button type="button" onclick="document.getElementById('offline-receipt-modal').remove()" class="btn-secondary" style="padding: 0.75rem 1rem; font-size: 0.85rem; border: 1px solid #ccc; background: #f3f4f6; color: #333333; font-weight: 600; border-radius: 6px; cursor: pointer;">
                                ✕ Close
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        window.triggerOfflineSync = function() {
            if (!navigator.onLine) {
                alert('Cannot sync right now. Internet connection is offline.');
                return;
            }
            syncOfflineSales();
        };

        async function syncOfflineSales() {
            const queue = getOfflineQueue();
            if (queue.length === 0) return;

            let syncedCount = 0;
            const remainingQueue = [];
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            for (const sale of queue) {
                const formData = new FormData();
                formData.append('_token', csrfToken);
                formData.append('cart', sale.cart);
                formData.append('customer_name', sale.customer_name);
                formData.append('customer_phone', sale.customer_phone);
                formData.append('discount', sale.discount);
                formData.append('tax_rate', sale.tax_rate);
                formData.append('payment_method', sale.payment_method);
                formData.append('amount_paid', sale.amount_paid);
                formData.append('currency_code', sale.currency_code);

                try {
                    const response = await fetch('{{ route("pos.checkout") }}', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    if (response.ok) {
                        syncedCount++;
                    } else {
                        remainingQueue.push(sale);
                    }
                } catch (err) {
                    remainingQueue.push(sale);
                }
            }

            saveOfflineQueue(remainingQueue);

            if (syncedCount > 0) {
                let toast = document.createElement('div');
                toast.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 3000; background: var(--accent-emerald); color: #fff; padding: 1rem 1.5rem; border-radius: 8px; font-weight: 700; box-shadow: 0 10px 30px rgba(0,0,0,0.5);';
                toast.innerHTML = `${syncedCount} offline transaction(s) synced to the server.`;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 4000);
            }
        }
    });
</script>

@if(session('receiptSale'))
@php $rec = session('receiptSale'); @endphp
<script>
    window._currentSessionReceiptSale = @json($rec->load('items.product'));
</script>
<div id="receipt-modal" style="position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.75); display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="glass-panel" style="width: 100%; max-width: 420px; background: #ffffff; color: #000000; padding: 2rem; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); position: relative;" id="printable-receipt-area">
        
        <!-- Receipt Header -->
        <div style="text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 1rem; margin-bottom: 1rem;">
            <h2 style="font-size: 1.4rem; font-weight: 800; text-transform: uppercase; margin: 0; color: #000000;">{{ $tenant->name ?? 'Luxeshop Boutique' }}</h2>
            @if(isset($tenant) && $tenant->business_name)
                <p style="font-size: 0.8rem; color: #444; margin: 0.2rem 0;">{{ $tenant->business_name }}</p>
            @endif
            <p style="font-size: 0.75rem; color: #555; margin: 0.2rem 0;">{{ $tenant->location ?? 'Main Store Location' }}</p>
            <p style="font-size: 0.75rem; color: #555; margin: 0.2rem 0;">Tel: {{ $tenant->phone ?? '+1 555-589-3746' }}</p>
        </div>

        <!-- Receipt Metadata -->
        <div style="font-size: 0.8rem; color: #333; margin-bottom: 1rem; display: flex; flex-direction: column; gap: 0.25rem;">
            <div style="display: flex; justify-content: space-between;">
                <span>Receipt No:</span>
                <strong>#{{ $rec->id }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Date & Time:</span>
                <span>{{ is_string($rec->created_at) ? \Carbon\Carbon::parse($rec->created_at)->format('Y-m-d H:i') : ($rec->created_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i')) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Server:</span>
                <span>{{ $rec->user->name ?? 'System' }}</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Customer:</span>
                <span>{{ $rec->customer_name ?: 'Walk-in Customer' }}</span>
            </div>
        </div>

        <!-- Items Table -->
        <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem; margin-bottom: 1rem; color: #000000;">
            <thead>
                <tr style="border-bottom: 1px solid #000; border-top: 1px solid #000;">
                    <th style="text-align: left; padding: 0.4rem 0;">Item</th>
                    <th style="text-align: center; padding: 0.4rem 0;">Qty</th>
                    <th style="text-align: right; padding: 0.4rem 0;">Price</th>
                    <th style="text-align: right; padding: 0.4rem 0;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rec->items as $item)
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 0.4rem 0;"><strong>{{ $item->product->name ?? 'Item' }}</strong></td>
                        <td style="text-align: center; padding: 0.4rem 0;">{{ $item->quantity }}</td>
                        <td style="text-align: right; padding: 0.4rem 0;">{{ $baseCurrency->symbol }}{{ number_format($item->price, 2) }}</td>
                        <td style="text-align: right; padding: 0.4rem 0;">{{ $baseCurrency->symbol }}{{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Summary -->
        <div style="border-top: 2px dashed #ccc; padding-top: 0.75rem; font-size: 0.85rem; display: flex; flex-direction: column; gap: 0.35rem; color: #000000;">
            <div style="display: flex; justify-content: space-between;">
                <span>Subtotal:</span>
                <span>{{ $baseCurrency->symbol }}{{ number_format($rec->subtotal, 2) }}</span>
            </div>
            @if($rec->discount > 0)
            <div style="display: flex; justify-content: space-between;">
                <span>Discount:</span>
                <span>-{{ $baseCurrency->symbol }}{{ number_format($rec->discount, 2) }}</span>
            </div>
            @endif
            @if($rec->tax > 0)
            <div style="display: flex; justify-content: space-between;">
                <span>Tax:</span>
                <span>+{{ $baseCurrency->symbol }}{{ number_format($rec->tax, 2) }}</span>
            </div>
            @endif
            <div style="display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: 800; border-top: 1px solid #000; padding-top: 0.4rem; margin-top: 0.25rem;">
                <span>TOTAL DUE:</span>
                <span>{{ $baseCurrency->symbol }}{{ number_format($rec->total, 2) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-weight: 600; padding-top: 0.25rem;">
                <span>Paid ({{ strtoupper($rec->payment_method) }}):</span>
                <span>{{ $baseCurrency->symbol }}{{ number_format($rec->amount_paid, 2) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-weight: 600; color: #059669;">
                <span>Change:</span>
                <span>{{ $baseCurrency->symbol }}{{ number_format($rec->change_amount, 2) }}</span>
            </div>
        </div>

        <!-- Receipt Footer Message -->
        <div style="text-align: center; margin-top: 1.5rem; border-top: 1px solid #ddd; padding-top: 0.75rem; font-size: 0.75rem; color: #666666;">
            <p style="margin: 0; font-weight: 600;">Thank you for shopping with us!</p>
            <p style="margin: 0.2rem 0 0 0;">Please keep this receipt for returns/exchanges.</p>
        </div>

        <!-- Action Buttons (Hidden when printing) -->
        <div class="no-print" style="margin-top: 1.5rem; display: flex; gap: 0.5rem; justify-content: space-between; flex-wrap: wrap;">
            <button type="button" onclick="returnSaleToCart(window._currentSessionReceiptSale)" class="btn-secondary" style="flex: 1; padding: 0.75rem; font-size: 0.85rem; border: 1px solid #2563eb; background: #eff6ff; color: #1d4ed8; font-weight: 700; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                🛒 Return to Cart
            </button>
            <button type="button" onclick="window.print()" class="btn-primary" style="flex: 1; padding: 0.75rem; font-size: 0.85rem; background: #000000; color: #ffffff; border: none; font-weight: 700; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                🖨️ Print Receipt
            </button>
            <button type="button" onclick="document.getElementById('receipt-modal').remove()" class="btn-secondary" style="padding: 0.75rem 1rem; font-size: 0.85rem; border: 1px solid #ccc; background: #f3f4f6; color: #333333; font-weight: 600; border-radius: 6px; cursor: pointer;">
                ✕ Close
            </button>
        </div>
    </div>
</div>
@endif

<style>
    @media print {
        @page {
            size: 58mm auto;
            margin: 0;
        }
        html, body {
            width: 58mm !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
            color: #000000 !important;
            font-family: 'Courier New', Courier, monospace, sans-serif !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body * {
            visibility: hidden !important;
        }
        #printable-receipt-area, #printable-receipt-area *,
        #printable-offline-receipt-area, #printable-offline-receipt-area * {
            visibility: visible !important;
        }
        #printable-receipt-area,
        #printable-offline-receipt-area {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 58mm !important;
            max-width: 58mm !important;
            margin: 0 !important;
            padding: 2mm 2mm 5mm 2mm !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            border: none !important;
            font-size: 8pt !important;
            line-height: 1.2 !important;
            background: #ffffff !important;
            color: #000000 !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
        }
        #printable-receipt-area h2, #printable-offline-receipt-area h2 {
            font-size: 10.5pt !important;
            font-weight: 800 !important;
            margin: 0 0 2px 0 !important;
        }
        #printable-receipt-area p, #printable-offline-receipt-area p {
            font-size: 7.5pt !important;
            margin: 1px 0 !important;
        }
        #printable-receipt-area table, #printable-offline-receipt-area table {
            width: 100% !important;
            font-size: 7.5pt !important;
            margin: 4px 0 !important;
            border-collapse: collapse !important;
        }
        #printable-receipt-area th, #printable-receipt-area td,
        #printable-offline-receipt-area th, #printable-offline-receipt-area td {
            padding: 2px 0 !important;
            word-break: break-word !important;
        }
        .no-print {
            display: none !important;
        }
    }

    /* STK Radar & Mobile Money Animations */
    @keyframes stk-radar-pulse {
        0% { transform: scale(0.85); opacity: 0.85; }
        50% { transform: scale(1.25); opacity: 0.2; }
        100% { transform: scale(0.85); opacity: 0.85; }
    }
    @keyframes stk-ping {
        0% { transform: scale(1); opacity: 1; }
        75%, 100% { transform: scale(2.2); opacity: 0; }
    }
    .stk-radar-ring {
        position: absolute;
        inset: 0;
        border-radius: 9999px;
        background: rgba(245, 158, 11, 0.2);
        animation: stk-radar-pulse 2s infinite ease-in-out;
    }
    .pm-tab-btn {
        transition: all 0.2s ease;
    }
    .pm-tab-btn:hover {
        background: rgba(37, 99, 235, 0.15) !important;
        border-color: #3b82f6 !important;
    }
    .pm-tab-btn.active {
        background: #2563eb !important;
        color: #ffffff !important;
        border-color: #2563eb !important;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.4);
    }
    .pm-tab-btn#pm-tab-mobile.active {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important;
        border-color: #059669 !important;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
    }
    #cart-pay-mobile-direct-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 22px rgba(16, 185, 129, 0.5) !important;
        filter: brightness(1.05);
    }
    #cart-pay-mobile-direct-btn:active {
        transform: translateY(0);
    }
</style>
@endsection
