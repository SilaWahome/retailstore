@extends('layouts.app')

@section('title', 'Settings - Retail POS')

@section('content')
<div class="page-header">
    <h1 class="page-title">Shop Settings</h1>
</div>

<div class="grid-2">
    <!-- Shop configuration panel -->
    <div class="glass-panel">
        <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
            <h2 style="font-size: 1.25rem; font-weight: 700;">Business Details</h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Modify store name, details, contact info, and upload logo (which is also used as site favicon and POS icon).</p>
        </div>
        <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="panel-body" style="display: flex; flex-direction: column; gap: 1.25rem;">
            @csrf
            
            <div class="form-group">
                <label class="form-label" for="name">Shop Name *</label>
                <input class="form-input" type="text" id="name" name="name" required value="{{ old('name', $tenant->name) }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="business_name">Business Legal Name</label>
                <input class="form-input" type="text" id="business_name" name="business_name" value="{{ old('business_name', $tenant->business_name) }}">
            </div>

            <div class="grid-3">
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input class="form-input" type="text" id="phone" name="phone" value="{{ old('phone', $tenant->phone) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="location">Location / Address</label>
                    <input class="form-input" type="text" id="location" name="location" value="{{ old('location', $tenant->location) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="tax_rate">Default Sales Tax Rate (%)</label>
                    <input class="form-input" type="number" step="0.01" min="0" max="100" id="tax_rate" name="tax_rate" value="{{ old('tax_rate', $tenant->tax_rate) }}" placeholder="e.g. 18.00">
                </div>
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 1.5rem; border: 1px solid var(--card-border); padding: 1rem; border-radius: var(--radius-sm); background: rgba(255,255,255,0.01);">
                <div style="width: 72px; height: 72px; border-radius: var(--radius-md); background: rgba(255,255,255,0.02); border: 1px solid var(--card-border); overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    @if($tenant->logo)
                        <img src="{{ asset('storage/' . $tenant->logo) }}" alt="Logo" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <span style="font-size: 0.9rem; color: var(--text-muted); font-weight: bold;">SHOP</span>
                    @endif
                </div>
                <div style="flex-grow: 1;">
                    <label class="form-label" for="logo" style="margin-bottom: 0.25rem; font-weight: 600;">Upload Shop Logo</label>
                    <input class="form-input" type="file" id="logo" name="logo" accept="image/*" style="padding: 0.4rem;">
                    <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem;">This logo updates the app favicon and dashboard sidebar logo. Recommened size: 128x128px PNG.</p>
                </div>
            </div>

            <button type="submit" class="btn-primary" style="align-self: flex-start; width: auto; padding: 0.85rem 2rem;">Save Settings</button>
        </form>

        <!-- Active Display Currency Selector -->
        <div class="glass-panel" style="margin-top: 1.5rem;">
            <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-main);">Store Display Currency</h3>
                        <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem;">Select which currency symbol and code are used on all documents, products, checkout totals, and reporting spreadsheets.</p>
                    </div>
                    @if(isset($baseCurrency) && $baseCurrency)
                        <div style="background: rgba(139, 92, 246, 0.15); border: 1px solid var(--primary-glow); padding: 0.4rem 0.85rem; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 700; color: var(--primary);">
                            Current: {{ $baseCurrency->code }} ({{ $baseCurrency->symbol }})
                        </div>
                    @endif
                </div>
            </div>

            <form action="{{ route('settings.set-base-currency') }}" method="POST" class="panel-body" style="display: flex; flex-direction: column; gap: 1.1rem;">
                @csrf

                <!-- Quick Presets -->
                <div class="form-group">
                    <label class="form-label" for="preset_selector" style="font-weight: 600;">Choose From Presets (or edit code/symbol below)</label>
                    <select id="preset_selector" class="form-input" style="padding: 0.7rem; font-size: 0.9rem;" onchange="applyPreset(this.value)">
                        <option value="">-- Choose a standard currency preset --</option>
                        <option value="KES|KSh">KES - Kenyan Shilling (KSh)</option>
                        <option value="USD|$">USD - US Dollar ($)</option>
                        <option value="EUR|€">EUR - Euro (€)</option>
                        <option value="GBP|£">GBP - British Pound (£)</option>
                        <option value="UGX|USh">UGX - Ugandan Shilling (USh)</option>
                        <option value="TZS|TSh">TZS - Tanzanian Shilling (TSh)</option>
                        <option value="RWF|FRw">RWF - Rwandan Franc (FRw)</option>
                        <option value="ZAR|R">ZAR - South African Rand (R)</option>
                        <option value="NGN|₦">NGN - Nigerian Naira (₦)</option>
                        <option value="GHS|GH₵">GHS - Ghanaian Cedi (GH₵)</option>
                        <option value="AED|AED">AED - UAE Dirham (AED)</option>
                        <option value="SAR|SAR">SAR - Saudi Riyal (SAR)</option>
                        <option value="INR|₹">INR - Indian Rupee (₹)</option>
                        <option value="CAD|CA$">CAD - Canadian Dollar (CA$)</option>
                        <option value="AUD|A$">AUD - Australian Dollar (A$)</option>
                    </select>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="base_currency_code" style="font-weight: 600;">Currency Code *</label>
                        <input class="form-input" type="text" id="base_currency_code" name="code" value="{{ $baseCurrency->code ?? 'KES' }}" required placeholder="e.g. USD, KES, EUR" style="padding: 0.7rem; text-transform: uppercase; font-weight: 700;">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="base_currency_symbol" style="font-weight: 600;">Currency Symbol *</label>
                        <input class="form-input" type="text" id="base_currency_symbol" name="symbol" value="{{ $baseCurrency->symbol ?? 'KSh' }}" required placeholder="e.g. $, KSh, €" style="padding: 0.7rem; font-weight: 700;">
                    </div>
                </div>

                @if($currencies->count() > 1)
                    <div style="padding-top: 0.5rem; border-top: 1px dashed var(--card-border);">
                        <label class="form-label" for="existing_currency_id" style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 0.4rem;">
                            Or switch to an existing registered currency:
                        </label>
                        <select id="existing_currency_id" name="currency_id" class="form-input" style="padding: 0.55rem 0.7rem; font-size: 0.85rem;" onchange="syncExistingCurrency(this)">
                            <option value="">-- Or select from your registered currencies --</option>
                            @foreach($currencies as $c)
                                <option value="{{ $c->id }}" data-code="{{ $c->code }}" data-symbol="{{ $c->symbol }}" {{ $c->is_base ? 'selected' : '' }}>
                                    {{ $c->code }} ({{ $c->symbol }}) {{ $c->is_base ? '— [Current Base]' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <button type="submit" class="btn-primary" style="padding: 0.75rem 2rem; width: auto; align-self: flex-start; background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); border: none; font-weight: 700; cursor: pointer;">
                    Save Display Currency
                </button>
            </form>
        </div>

        <script>
            function applyPreset(val) {
                if (!val) return;
                const parts = val.split('|');
                if (parts.length === 2) {
                    document.getElementById('base_currency_code').value = parts[0];
                    document.getElementById('base_currency_symbol').value = parts[1];
                    const existingSel = document.getElementById('existing_currency_id');
                    if (existingSel) existingSel.value = "";
                }
            }

            function syncExistingCurrency(sel) {
                if (!sel.value) return;
                const opt = sel.options[sel.selectedIndex];
                if (opt && opt.dataset.code) {
                    document.getElementById('base_currency_code').value = opt.dataset.code;
                    document.getElementById('base_currency_symbol').value = opt.dataset.symbol;
                }
            }
        </script>

        <!-- Security & Account Password Card -->
        <div class="glass-panel" style="margin-top: 1.5rem;">
            <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-main);">Account Security & Password</h3>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem;">Update your account password securely.</p>
            </div>
            <form action="{{ route('password.update') }}" method="POST" class="panel-body" style="display: flex; flex-direction: column; gap: 1rem;">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="current_password">Current Password *</label>
                    <input class="form-input" type="password" id="current_password" name="current_password" required placeholder="••••••••" style="padding: 0.7rem;">
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password *</label>
                        <input class="form-input" type="password" id="new_password" name="new_password" required placeholder="Min 6 characters" style="padding: 0.7rem;">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="new_password_confirmation">Confirm Password *</label>
                        <input class="form-input" type="password" id="new_password_confirmation" name="new_password_confirmation" required placeholder="Re-enter password" style="padding: 0.7rem;">
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="padding: 0.75rem 2rem; width: auto; align-self: flex-start; background: var(--primary); border: none;">Update Password</button>
            </form>
        </div>
    </div>

    <!-- Currency and Multicurrency Panel -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Currency List -->
        <div class="glass-panel">
            <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
                <h2 style="font-size: 1.25rem; font-weight: 700;">Currencies & Exchange Rates</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Accept checkout payments using different currencies dynamically scaled from the base exchange rate.</p>
            </div>
            
            <div class="custom-table-container">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Currency</th>
                            <th>Symbol</th>
                            <th>Exchange Rate</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($currencies as $currency)
                            <tr>
                                <td><strong style="color: #fff;">{{ $currency->code }}</strong></td>
                                <td><code>{{ $currency->symbol }}</code></td>
                                <td>
                                    @if($currency->is_base)
                                        1.0000 (Base)
                                    @else
                                        {{ number_format($currency->exchange_rate, 4) }}
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $currency->is_base ? 'badge-received' : 'badge-pending' }}">
                                        {{ $currency->is_base ? 'BASE' : 'ADDITIONAL' }}
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.4rem;">
                                        @if(! $currency->is_base)
                                            <form action="{{ route('settings.set-base-currency') }}" method="POST" style="margin: 0;">
                                                @csrf
                                                <input type="hidden" name="currency_id" value="{{ $currency->id }}">
                                                <button type="submit" class="btn-secondary" style="color: var(--primary); border-color: rgba(139, 92, 246, 0.3); padding: 0.3rem 0.55rem; font-size: 0.78rem; font-weight: 600;">
                                                    Make Base
                                                </button>
                                            </form>
                                            <form action="{{ route('settings.delete-currency', $currency) }}" method="POST" onsubmit="return confirm('Delete this currency?');" style="margin: 0;">
                                                @csrf
                                                <button type="submit" class="btn-secondary" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.2); padding: 0.3rem 0.55rem; font-size: 0.78rem;">Delete</button>
                                            </form>
                                        @else
                                            <span style="font-size: 0.78rem; color: var(--accent-emerald); font-weight: 600;">✓ Primary</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Currency Form -->
        <div class="glass-panel">
            <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
                <h3 style="font-size: 1.05rem; font-weight: 700;">Add Support Currency</h3>
            </div>
            <form action="{{ route('settings.add-currency') }}" method="POST" class="panel-body" style="display: flex; flex-direction: column; gap: 1rem;">
                @csrf
                
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="code">Currency Code *</label>
                        <input class="form-input" type="text" id="code" name="code" required placeholder="e.g. KES" style="padding: 0.7rem;">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="symbol">Symbol *</label>
                        <input class="form-input" type="text" id="symbol" name="symbol" required placeholder="e.g. KSh" style="padding: 0.7rem;">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="exchange_rate">Exchange Rate (Relative to 1.00 Base) *</label>
                    <input class="form-input" type="number" step="0.0001" id="exchange_rate" name="exchange_rate" required placeholder="e.g. 130.0000" style="padding: 0.7rem;">
                    <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem;">How many units of this currency equals 1.00 unit of your base currency.</p>
                </div>

                <button type="submit" class="btn-primary" style="padding: 0.75rem; font-size: 0.9rem;">Register Currency</button>
            </form>
        </div>
    </div>
</div>
@endsection
