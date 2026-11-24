<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <script>
        const initialTheme = localStorage.getItem('theme') || 'dark';
        if (initialTheme === 'light') {
            document.documentElement.classList.add('light-theme', 'light');
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light-theme', 'light');
        }
    </script>
    
    <!-- Unified Logo & Favicon -->
    @if(Auth::check() && Auth::user()->tenant && Auth::user()->tenant->logo)
        <link rel="icon" type="image/*" href="{{ asset('storage/' . Auth::user()->tenant->logo) }}">
    @else
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 rx=%2220%22 fill=%22%238b5cf6%22/><text y=%2270%22 x=%2225%22 fill=%22white%22 font-size=%2260%22 font-weight=%22bold%22>L</text></svg>">
    @endif

    <title>@yield('title', 'Retail Store POS')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    
    <!-- PWA Settings -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#8b5cf6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="RetailStore">
    <link rel="apple-touch-icon" href="/images/logo.svg">

    @yield('styles')
</head>
<body>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebarNav">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    @if(Auth::check() && Auth::user()->tenant && Auth::user()->tenant->logo)
                        <img class="sidebar-logo-img" src="{{ asset('storage/' . Auth::user()->tenant->logo) }}" alt="Logo">
                    @else
                        L
                    @endif
                </div>
                <div class="sidebar-title">
                    {{ Auth::check() && Auth::user()->tenant ? Auth::user()->tenant->name : 'Luxeshop' }}
                </div>
                <button class="mobile-close-btn" id="sidebarCloseBtn">&times;</button>
            </div>

            <ul class="menu-list">
                @php
                    $authUser = Auth::user();
                    $authTenant = $authUser?->tenant;
                    $isTenantSuspended = $authTenant && !in_array($authTenant->subscription_status, ['active', 'trial']) && $authUser->role !== 'superadmin';
                @endphp

                @if(!$isTenantSuspended)
                    @if($authTenant && $authTenant->isRestaurantOrHotel())
                        {{-- Grouped Hospitality & Restaurant Submenus --}}
                        @if(in_array($authUser->role, ['admin', 'manager', 'receptionist']) && $authTenant->canAccess('room_booking'))
                        <li style="margin-top: 0.35rem;">
                            <div style="font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); padding: 0.35rem 0.75rem;">Hotel & Front Desk</div>
                            <a href="{{ route('hotel.rooms') }}" class="menu-link {{ request()->routeIs('hotel.*') ? 'active' : '' }}">
                                <span class="menu-bullet"></span> Rooms & Guest Folios
                            </a>
                        </li>
                        @endif

                        @if(in_array($authUser->role, ['admin', 'manager', 'cashier', 'receptionist', 'kitchen']))
                        <li style="margin-top: 0.35rem;">
                            <div style="font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); padding: 0.35rem 0.75rem;">Restaurant & KOT</div>
                        </li>
                        @if(in_array($authUser->role, ['admin', 'manager', 'cashier', 'receptionist']) && $authTenant->canAccess('table_management'))
                        <li>
                            <a href="{{ route('restaurant.tables') }}" class="menu-link {{ request()->routeIs('restaurant.tables') ? 'active' : '' }}">
                                <span class="menu-bullet"></span> Floor & Table POS
                            </a>
                        </li>
                        @endif
                        @if(in_array($authUser->role, ['admin', 'manager', 'kitchen']) && $authTenant->canAccess('kds_display'))
                        <li>
                            <a href="{{ route('restaurant.kds') }}" class="menu-link {{ request()->routeIs('restaurant.kds') ? 'active' : '' }}">
                                <span class="menu-bullet"></span> Kitchen Display (KDS)
                            </a>
                        </li>
                        @endif
                        @if(in_array($authUser->role, ['admin', 'manager']))
                        <li>
                            <a href="{{ route('restaurant.menu-setup') }}" class="menu-link {{ request()->routeIs('restaurant.menu-setup') ? 'active' : '' }}">
                                <span class="menu-bullet"></span> Menu, Modifiers & BOM
                            </a>
                        </li>
                        @endif
                        @endif

                        @if(in_array($authUser->role, ['admin', 'manager', 'accountant']))
                        <li style="margin-top: 0.5rem;">
                            <div style="font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); padding: 0.35rem 0.75rem;">Back Office & Reports</div>
                        </li>
                        @endif
                    @endif

                    @if(Auth::check() && in_array(Auth::user()->role, ['admin', 'manager']))
                    <li>
                        <a href="{{ route('sales') }}" class="menu-link {{ request()->routeIs('sales') ? 'active' : '' }}">
                            <span class="menu-bullet"></span> Store Dashboard
                        </a>
                    </li>
                    @endif

                    @if(Auth::check() && Auth::user()->role === 'cashier' && !($authTenant && $authTenant->isRestaurantOrHotel()))
                    <li>
                        <a href="{{ route('cashier.dashboard') }}" class="menu-link {{ request()->routeIs('cashier.dashboard') ? 'active' : '' }}">
                            <span class="menu-bullet"></span> Cashier Dashboard
                        </a>
                    </li>
                    @endif

                    @if(Auth::check() && Auth::user()->role !== 'superadmin' && Auth::user()->role !== 'accountant' && !($authTenant && $authTenant->isRestaurantOrHotel()))
                    <li>
                        <a href="{{ route('pos') }}" class="menu-link {{ request()->routeIs('pos') ? 'active' : '' }}">
                            <span class="menu-bullet"></span> POS Checkout
                        </a>
                    </li>
                    @endif

                    @if(Auth::check() && Auth::user()->role === 'admin')
                    <li>
                        <a href="{{ route('branches.index') }}" class="menu-link {{ request()->routeIs('branches.*') ? 'active' : '' }}">
                            <span class="menu-bullet"></span> Branch Management
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('users.index') }}" class="menu-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <span class="menu-bullet"></span> User & Staff Management
                        </a>
                    </li>
                    @endif
                    
                    @if(Auth::check() && in_array(Auth::user()->role, ['admin', 'manager']))
                    <li>
                        <a href="{{ route('products.index') }}" class="menu-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                            <span class="menu-bullet"></span> Products & Inventory
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('categories.index') }}" class="menu-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                            <span class="menu-bullet"></span> Product Categories
                        </a>
                    </li>
                    @endif

                    @if(Auth::check() && in_array(Auth::user()->role, ['admin', 'manager', 'accountant']))
                    <li>
                        <a href="{{ route('purchase_orders.index') }}" class="menu-link {{ request()->routeIs('purchase_orders.*') ? 'active' : '' }}">
                            <span class="menu-bullet"></span> Purchase Orders (XPOs)
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reports.index') }}" class="menu-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                            <span class="menu-bullet"></span> Reports & Exports
                        </a>
                    </li>
                    @endif

                    @if(Auth::check() && Auth::user()->role !== 'superadmin')
                    <li>
                        <a href="{{ route('quotations.index') }}" class="menu-link {{ request()->routeIs('quotations.*') ? 'active' : '' }}">
                            <span class="menu-bullet"></span> Quotations
                        </a>
                    </li>
                    @endif

                    @if(Auth::check() && in_array(Auth::user()->role, ['admin', 'manager', 'accountant']))
                    <li>
                        <a href="{{ route('expenses.index') }}" class="menu-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                            <span class="menu-bullet"></span> Daily Expenses
                        </a>
                    </li>
                    @endif

                    @if(Auth::check() && Auth::user()->role === 'admin')
                    <li>
                        <a href="{{ route('settings.index') }}" class="menu-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                            <span class="menu-bullet"></span> Settings
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('finances.disbursements') }}" class="menu-link {{ request()->routeIs('finances.*') ? 'active' : '' }}">
                            <span class="menu-bullet"></span> 💸 Mobile Money Payouts
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('receipt.simulator') }}" class="menu-link {{ request()->routeIs('receipt.simulator') ? 'active' : '' }}">
                            <span class="menu-bullet"></span> 🧾 Receipt Simulator
                        </a>
                    </li>
                    @endif
                @endif

                @if(Auth::check() && Auth::user()->role !== 'superadmin')
                <li>
                    <a href="{{ route('support.index') }}" class="menu-link {{ request()->routeIs('support.*') ? 'active' : '' }}">
                        <span class="menu-bullet"></span> Support & Billing Help
                    </a>
                </li>
                @endif
                
                @if(Auth::check() && Auth::user()->role === 'superadmin')
                <li>
                    <a href="{{ route('saas') }}" class="menu-link {{ request()->routeIs('saas') ? 'active' : '' }}">
                        <span class="menu-bullet"></span> SaaS Dashboard
                    </a>
                </li>
                @endif
            </ul>

            <div class="sidebar-footer">
                @php
                    $attendanceToday = null;
                    if (Auth::check() && Auth::user()->role !== 'superadmin') {
                        $attendanceToday = \App\Models\Attendance::where('user_id', Auth::id())
                            ->where('date', \Carbon\Carbon::today()->toDateString())
                            ->first();
                    }
                @endphp

                <div class="user-profile">
                    <div class="user-avatar">
                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="user-info">
                        <span class="user-name">{{ Auth::user()->name ?? 'User' }}</span>
                        <span class="user-role">{{ ucfirst(Auth::user()->role ?? 'cashier') }}</span>
                    </div>
                </div>

                @if(Auth::check() && Auth::user()->role !== 'superadmin')
                    @if(!$attendanceToday)
                        <form action="{{ route('attendance.toggle') }}" method="POST" style="margin-bottom: 0.5rem;">
                            @csrf
                            <button type="submit" class="btn-primary" style="width: 100%; font-size: 0.85rem; padding: 0.5rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                                Check In Today
                            </button>
                        </form>
                    @elseif(!$attendanceToday->check_out)
                        <form action="{{ route('attendance.toggle') }}" method="POST" style="margin-bottom: 0.5rem;">
                            @csrf
                            <button type="submit" class="btn-primary" style="width: 100%; font-size: 0.85rem; padding: 0.5rem; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none;">
                                Check Out Today
                            </button>
                        </form>
                    @else
                        <div style="font-size: 0.75rem; text-align: center; color: var(--accent-emerald); background: rgba(16, 185, 129, 0.05); padding: 0.4rem; border-radius: var(--radius-sm); margin-bottom: 0.5rem; border: 1px solid rgba(16, 185, 129, 0.1); font-weight: 500;">
                            Shift Done ({{ substr($attendanceToday->check_in, 0, 5) }} - {{ substr($attendanceToday->check_out, 0, 5) }})
                        </div>
                    @endif
                @endif

                <button id="theme-toggle-btn" class="btn-secondary" style="width: 100%; font-size: 0.85rem; padding: 0.55rem; background: rgba(255,255,255,0.04); border-color: var(--card-border); color: var(--text-main); margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; border-radius: var(--radius-sm); font-weight: 600;">
                    <span>Toggle Theme</span>
                </button>

                <a href="{{ route('password.change') }}" class="menu-link" style="color: var(--text-main); background: rgba(255,255,255,0.03); margin-top: 0.25rem; justify-content: center; font-size: 0.85rem;">
                    Change Password
                </a>

                <a href="{{ route('logout') }}" class="menu-link" style="color: #ef4444; background: rgba(239, 68, 68, 0.05); margin-top: 0.5rem; justify-content: center;">
                    Log Out
                </a>
                <button id="pwa-install-btn" class="btn-primary" style="display: none; width: 100%; margin-top: 0.5rem; font-size: 0.85rem; padding: 0.5rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    Download App
                </button>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="main-content">
            @php
                $userBranches = [];
                if (Auth::check() && Auth::user()->tenant_id) {
                    $userBranches = \App\Models\Branch::where('tenant_id', Auth::user()->tenant_id)->where('is_active', true)->get();
                }
                $currentBranchId = session('active_branch_id', 'all');
            @endphp

            <!-- App Top Bar / Header -->
            <header class="app-topbar glass-panel">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button id="sidebar-toggle-btn" class="mobile-toggle-btn" aria-label="Toggle Navigation">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                    <div class="topbar-title">
                        <span style="font-weight: 700; font-size: 1.1rem;">{{ Auth::check() && Auth::user()->tenant ? (Auth::user()->tenant->business_name ?? Auth::user()->tenant->name) : 'Retail Store' }}</span>
                    </div>
                </div>

                @if(Auth::check() && in_array(Auth::user()->role, ['admin', 'manager']))
                <div class="branch-selector-container">
                    <form action="{{ route('branches.switch') }}" method="POST" style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        @csrf
                        <label for="header_branch_id" style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; gap: 0.35rem; white-space: nowrap;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            <span class="branch-label-text">Branch Context:</span>
                        </label>
                        <select id="header_branch_id" name="branch_id" onchange="this.form.submit()" class="branch-select" style="background: var(--select-bg); color: var(--text-main); border: 1px solid var(--card-border); padding: 0.45rem 0.75rem; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; cursor: pointer; outline: none;">
                            <option value="all" {{ $currentBranchId === 'all' ? 'selected' : '' }}>🏢 All Branches (Combined)</option>
                            @foreach($userBranches as $b)
                                <option value="{{ $b->id }}" {{ (string)$currentBranchId === (string)$b->id ? 'selected' : '' }}>
                                    📍 {{ $b->name }} ({{ $b->code ?? 'MAIN' }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
                @elseif(Auth::check() && Auth::user()->branch)
                <div class="branch-badge" style="font-size: 0.85rem; font-weight: 600; background: rgba(139, 92, 246, 0.15); color: var(--primary); padding: 0.4rem 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--primary-glow); display: flex; align-items: center; gap: 0.35rem;">
                    📍 {{ Auth::user()->branch->name }}
                </div>
                @endif

                {{-- ── Currency Switcher (all authenticated non-superadmin users) ── --}}
                @if(Auth::check() && Auth::user()->role !== 'superadmin' && Auth::user()->tenant_id)
                @php
                    $topbarCurrencies = \App\Models\Currency::where('tenant_id', Auth::user()->tenant_id)->orderBy('code')->get();
                    $activeCurrencyId = session('active_currency_id', $topbarCurrencies->where('is_base', true)->first()?->id ?? $topbarCurrencies->first()?->id);
                @endphp
                @if($topbarCurrencies->count() > 1)
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <form action="{{ route('currency.switch') }}" method="POST" style="margin: 0; display: flex; align-items: center; gap: 0.4rem;">
                        @csrf
                        <label for="topbar_currency_id" style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); white-space: nowrap; display: flex; align-items: center; gap: 0.3rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                        </label>
                        <select id="topbar_currency_id" name="currency_id" onchange="this.form.submit()"
                            style="background: var(--select-bg); color: var(--text-main); border: 1px solid var(--card-border); padding: 0.4rem 0.65rem; border-radius: var(--radius-sm); font-size: 0.83rem; font-weight: 700; cursor: pointer; outline: none; min-width: 80px;">
                            @foreach($topbarCurrencies as $cur)
                                <option value="{{ $cur->id }}" {{ (string)$activeCurrencyId === (string)$cur->id ? 'selected' : '' }}>
                                    {{ $cur->symbol }} {{ $cur->code }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
                @endif
                @endif
            </header>

            @if(session('impersonating_from_saas'))
                <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.2)); border: 1px solid rgba(139, 92, 246, 0.45); color: var(--text-main); padding: 0.75rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.15rem; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 0.88rem; font-weight: 600;">
                        Impersonating client: <strong>{{ session('impersonated_tenant_name') }}</strong>
                    </span>
                    <a href="{{ route('saas.stop-impersonation') }}" class="btn-primary" style="width: auto; padding: 0.45rem 0.95rem; font-size: 0.82rem; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border: none; color: #fff; font-weight: 700; text-decoration: none;">
                        ← Return to Superadmin
                    </a>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem;">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            @if(isset($authTenant) && $authTenant && in_array($authUser->role ?? '', ['admin', 'manager']) && $authTenant->isBillDueOrPromptable() && !request()->routeIs('settings.*'))
                <div style="background: rgba(245, 158, 11, 0.14); border: 1px solid rgba(245, 158, 11, 0.4); color: #fde68a; padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 0.9rem; font-weight: 600;">
                        Pending Subscription Bill: <strong>UGX {{ number_format($authTenant->pendingBillAmountUgx(), 0) }}</strong>. Please pay via Mobile Money to keep your store active.
                    </span>
                    <a href="{{ route('settings.index') }}" class="btn-primary" style="width: auto; padding: 0.45rem 0.95rem; font-size: 0.82rem; background: #f59e0b; border: none; color: #0f172a; font-weight: 800; text-decoration: none;">
                        Pay Now
                    </a>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @yield('scripts')

    <script>
        // Kill ALL service workers — pages must always load fresh from the server
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(registrations => {
                registrations.forEach(reg => reg.unregister());
            });
            // Also wipe every cache entry the old SW may have stored
            if ('caches' in window) {
                caches.keys().then(keys => keys.forEach(key => caches.delete(key)));
            }
        }

        // PWA Install Prompt Handler
        let deferredPrompt;
        const installBtn = document.getElementById('pwa-install-btn');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            if (installBtn) installBtn.style.display = 'block';
        });

        if (installBtn) {
            installBtn.addEventListener('click', (e) => {
                installBtn.style.display = 'none';
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted PWA installation');
                    }
                    deferredPrompt = null;
                });
            });
        }

        // Mobile Sidebar Drawer Controls
        const sidebarNav = document.getElementById('sidebarNav');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
        const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');

        function openSidebar() {
            if (sidebarNav) sidebarNav.classList.add('mobile-open');
            if (sidebarBackdrop) sidebarBackdrop.classList.add('active');
        }

        function closeSidebar() {
            if (sidebarNav) sidebarNav.classList.remove('mobile-open');
            if (sidebarBackdrop) sidebarBackdrop.classList.remove('active');
        }

        if (sidebarToggleBtn) sidebarToggleBtn.addEventListener('click', openSidebar);
        if (sidebarCloseBtn) sidebarCloseBtn.addEventListener('click', closeSidebar);
        if (sidebarBackdrop) sidebarBackdrop.addEventListener('click', closeSidebar);

        // Global Theme Toggle Event Listener
        function applyTheme(theme) {
            const btn = document.getElementById('theme-toggle-btn');
            if (theme === 'light') {
                document.documentElement.classList.add('light-theme', 'light');
                document.documentElement.classList.remove('dark');
                if (document.body) {
                    document.body.classList.add('light-theme', 'light');
                    document.body.classList.remove('dark');
                }
                if (btn) {
                    btn.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                        <span>Light Mode Active</span>
                    `;
                }
            } else {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light-theme', 'light');
                if (document.body) {
                    document.body.classList.add('dark');
                    document.body.classList.remove('light-theme', 'light');
                }
                if (btn) {
                    btn.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                        <span>Dark Mode Active</span>
                    `;
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            applyTheme(savedTheme);
        });

        document.addEventListener('click', (e) => {
            const toggleBtn = e.target.closest('#theme-toggle-btn');
            if (toggleBtn) {
                e.preventDefault();
                const currentTheme = localStorage.getItem('theme') === 'light' ? 'dark' : 'light';
                localStorage.setItem('theme', currentTheme);
                applyTheme(currentTheme);
            }
        });
    </script>

    @if(Auth::check() && Auth::user()->role === 'admin')
        @include('partials.soft_delete_modal')
    @endif
</body>
</html>

