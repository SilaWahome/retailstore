<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaaS Portal & Sign In - Luxeshop Retail POS</title>

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

    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 rx=%2220%22 fill=%22%238b5cf6%22/><text y=%2270%22 x=%2225%22 fill=%22white%22 font-size=%2260%22 font-weight=%22bold%22>L</text></svg>">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">

    <style>
        /* ── SaaS Two-panel login layout ───────────────────────────────────── */
        .login-split-wrapper {
            display: flex;
            min-height: 100vh;
            background-color: var(--bg-dark);
            background-image: var(--bg-image);
            background-attachment: fixed;
        }

        /* Left panel – hero image & status */
        .login-hero-panel {
            flex: 1;
            position: relative;
            overflow: hidden;
            display: none;
        }

        .login-hero-panel img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
            display: block;
        }

        /* Dark gradient overlay so text reads well */
        .login-hero-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                135deg,
                rgba(8, 8, 16, 0.70) 0%,
                rgba(139, 92, 246, 0.40) 60%,
                rgba(16, 185, 129, 0.25) 100%
            );
        }

        /* Overlay text on the hero */
        .hero-caption {
            position: absolute;
            bottom: 2.5rem;
            left: 2.5rem;
            right: 2.5rem;
            z-index: 2;
            color: #fff;
        }

        .hero-caption h2 {
            font-size: 2.1rem;
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: -0.5px;
            margin-bottom: 0.6rem;
            text-shadow: 0 2px 12px rgba(0,0,0,0.5);
        }

        .hero-caption p {
            font-size: 0.95rem;
            color: rgba(255,255,255,0.85);
            line-height: 1.55;
            text-shadow: 0 1px 6px rgba(0,0,0,0.5);
        }

        /* Pill badges on hero */
        .hero-badges-row {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 0.85rem;
            flex-wrap: wrap;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: rgba(139, 92, 246, 0.35);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(139, 92, 246, 0.5);
            border-radius: 999px;
            padding: 0.35rem 0.85rem;
            font-size: 0.78rem;
            font-weight: 600;
            color: #c4b5fd;
            letter-spacing: 0.03em;
        }

        .hero-badge::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #a78bfa;
            box-shadow: 0 0 6px #8b5cf6;
            flex-shrink: 0;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.4);
            border-radius: 999px;
            padding: 0.35rem 0.85rem;
            font-size: 0.78rem;
            font-weight: 600;
            color: #34d399;
        }

        .status-badge::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 8px #10b981;
            flex-shrink: 0;
        }

        /* Stats row */
        .hero-stats {
            display: flex;
            gap: 1.5rem;
            margin-top: 1.2rem;
        }

        .hero-stat {
            display: flex;
            flex-direction: column;
        }

        .hero-stat strong {
            font-size: 1.3rem;
            font-weight: 800;
            color: #fff;
        }

        .hero-stat span {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.70);
            margin-top: 0.1rem;
        }

        /* Right panel – form */
        .login-form-panel {
            width: 100%;
            max-width: 440px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 2.5rem;
            background: var(--card-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-left: 1px solid var(--card-border);
            gap: 1.5rem;
        }

        /* Mode Selector Tabs */
        .portal-tabs {
            display: flex;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 4px;
            gap: 4px;
        }

        .portal-tab {
            flex: 1;
            text-align: center;
            padding: 0.55rem 0.75rem;
            font-size: 0.82rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            background: transparent;
            color: var(--text-muted);
            transition: all 0.2s ease;
        }

        .portal-tab.active {
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4);
        }

        /* Quick Demo Credentials Box */
        .demo-credentials-box {
            background: rgba(139, 92, 246, 0.08);
            border: 1px dashed rgba(139, 92, 246, 0.35);
            border-radius: 12px;
            padding: 1rem;
        }

        .demo-credentials-title {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--primary);
            margin-bottom: 0.65rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .demo-pills {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .demo-btn {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: var(--text-main);
            font-size: 0.76rem;
            font-weight: 600;
            padding: 0.35rem 0.7rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .demo-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-1px);
        }

        /* Show hero on larger screens */
        @media (min-width: 900px) {
            .login-hero-panel {
                display: block;
            }
        }

        /* Tighter on small screens */
        @media (max-width: 520px) {
            .login-form-panel {
                padding: 2rem 1.25rem;
            }
        }

        /* Dynamic focus glow */
        .form-input:focus {
            outline: none;
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px var(--primary-glow);
        }
    </style>
</head>
<body style="margin:0; padding:0;">

    <div class="login-split-wrapper">

        {{-- ── LEFT: SaaS Hero Panel / Product Slider ── --}}
        <div class="login-hero-panel" id="heroSliderPanel">
            @if(isset($sliderProducts) && $sliderProducts->count() > 0)
                @foreach($sliderProducts as $idx => $sp)
                    @php
                        $spSym = $sp->tenant?->currencies?->first()?->symbol ?? '';
                    @endphp
                    <div class="hero-slide" data-slide="{{ $idx }}" style="position:absolute;inset:0;transition:opacity 0.6s ease;opacity:{{ $idx === 0 ? '1' : '0' }};">
                        <img src="{{ asset('storage/' . $sp->image_path) }}" alt="{{ $sp->name }}" style="width:100%;height:100%;object-fit:cover;">
                        <div style="position:absolute;top:1.75rem;left:2rem;z-index:3;background:rgba(15,23,42,0.75);backdrop-filter:blur(8px);padding:0.65rem 1.1rem;border-radius:12px;border:1px solid rgba(255,255,255,0.18);color:#fff;">
                            <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.06em;color:#c4b5fd;font-weight:700;">Featured Product</div>
                            <div style="font-size:1.15rem;font-weight:800;">{{ $sp->name }}</div>
                            <div style="font-size:0.95rem;font-weight:700;color:#34d399;">{{ $spSym }} {{ number_format($sp->price, 0) }}</div>
                        </div>
                    </div>
                @endforeach
                <div style="position:absolute;top:1.75rem;right:2rem;z-index:4;display:flex;gap:0.5rem;">
                    <button type="button" onclick="moveHeroSlide(-1)" style="width:36px;height:36px;border-radius:50%;background:rgba(15,23,42,0.75);color:#fff;border:1px solid rgba(255,255,255,0.2);cursor:pointer;font-weight:800;">‹</button>
                    <button type="button" onclick="moveHeroSlide(1)" style="width:36px;height:36px;border-radius:50%;background:rgba(15,23,42,0.75);color:#fff;border:1px solid rgba(255,255,255,0.2);cursor:pointer;font-weight:800;">›</button>
                </div>
                <script>
                    (function() {
                        let currentSlide = 0;
                        const slides = document.querySelectorAll('.hero-slide');
                        window.moveHeroSlide = function(dir) {
                            if (!slides.length) return;
                            slides[currentSlide].style.opacity = '0';
                            currentSlide = (currentSlide + dir + slides.length) % slides.length;
                            slides[currentSlide].style.opacity = '1';
                        };
                        if (slides.length > 1) {
                            setInterval(() => window.moveHeroSlide(1), 4500);
                        }
                    })();
                </script>
            @else
                <img
                    src="{{ asset('images/login-hero.png') }}"
                    alt="Luxeshop SaaS Point of Sale System"
                    onerror="this.style.display='none';"
                >
            @endif
            <div class="hero-caption">
                <div class="hero-badges-row">
                    <div class="hero-badge">SaaS Cloud Platform</div>
                    <div class="status-badge">99.99% Systems Operational</div>
                </div>
                <h2>Multi-Tenant Retail POS<br>& Enterprise Engine</h2>
                <p>Manage multi-branch inventory, real-time sales checkout, accounting, and tenant subscriptions — all from a single unified cloud portal.</p>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <strong>POS</strong>
                        <span>Fast Checkout</span>
                    </div>
                    <div class="hero-stat">
                        <strong>SaaS</strong>
                        <span>Multi-Tenant</span>
                    </div>
                    <div class="hero-stat">
                        <strong>256-bit</strong>
                        <span>SSL Encrypted</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── RIGHT: SaaS Auth Form Panel ── --}}
        <div class="login-form-panel">

            {{-- Header --}}
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #8b5cf6, #6366f1); color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.25rem; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);">
                        L
                    </div>
                    <div>
                        <h1 style="font-size: 1.3rem; font-weight: 800; color: var(--text-main); margin: 0; line-height: 1.2; letter-spacing: -0.3px;">Luxeshop SaaS</h1>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">Retail Cloud Portal</span>
                    </div>
                </div>

                <button type="button" id="theme-toggle-btn" class="btn-secondary" style="font-size: 0.75rem; padding: 0.4rem 0.75rem; border-radius: 8px; border-color: var(--card-border);">
                    Toggle Theme
                </button>
            </div>

            {{-- Mode Selector Tabs --}}
            <div class="portal-tabs">
                <button type="button" class="portal-tab active" id="tab-store" onclick="switchMode('store')">
                    🏪 Tenant Store Login
                </button>
                <button type="button" class="portal-tab" id="tab-saas" onclick="switchMode('saas')">
                    ⚡ Superadmin Portal
                </button>
            </div>

            <div id="form-heading">
                <h2 id="login-title" style="font-size: 1.45rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.35rem;">Sign in to your account</h2>
                <p id="login-subtitle" style="color: var(--text-muted); font-size: 0.88rem; margin: 0;">Enter your tenant store credentials below to continue.</p>
            </div>

            {{-- Quick Demo Preset Login Box --}}
            <div class="demo-credentials-box">
                <div class="demo-credentials-title">
                    <span>⚡ Instant Demo Presets (Click to Fill)</span>
                    <span style="font-weight: 400; font-size: 0.7rem; opacity: 0.8;">Pass: password</span>
                </div>
                <div class="demo-pills">
                    <button type="button" class="demo-btn" onclick="fillDemo('superadmin@luxeshop.com', 'password', 'saas')">
                        👑 Superadmin
                    </button>
                    <button type="button" class="demo-btn" onclick="fillDemo('admin@luxeshop.com', 'password', 'store')">
                        💼 Store Admin
                    </button>
                    <button type="button" class="demo-btn" onclick="fillDemo('cashier@luxeshop.com', 'password', 'store')">
                        🛒 Cashier
                    </button>
                </div>
            </div>

            @if($errors->any())
                <div class="alert alert-danger" style="border-radius: var(--radius-sm); font-size: 0.85rem; padding: 0.75rem 1rem; margin: 0;">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Form --}}
            <form action="{{ route('login') }}" method="POST" style="display: flex; flex-direction: column; gap: 1.15rem;">
                @csrf

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="email" style="font-size: 0.85rem; font-weight: 600;" id="email-label">Email Address</label>
                    <input class="form-input" type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="you@store.com" autofocus style="padding: 0.75rem; font-size: 0.9rem;">
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="password" style="font-size: 0.85rem; font-weight: 600;">Password</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input class="form-input" type="password" id="password" name="password" required placeholder="••••••••" style="padding: 0.75rem; font-size: 0.9rem; width: 100%; padding-right: 4rem;">
                        <button type="button" id="toggle-password" style="position: absolute; right: 0.75rem; background: transparent; border: none; color: var(--text-muted); font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                            Show
                        </button>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted); cursor: pointer; user-select: none;">
                        <input type="checkbox" id="remember" name="remember" style="accent-color: var(--primary); width: 15px; height: 15px;">
                        Remember this session
                    </label>
                </div>

                <button type="submit" id="submit-btn" class="btn-primary" style="padding: 0.85rem; font-size: 0.95rem; font-weight: 700; background: var(--primary); border: none; border-radius: var(--radius-sm); cursor: pointer; margin-top: 0.25rem; transition: background 0.2s ease;">
                    Sign In to Store
                </button>
            </form>

            {{-- Footer --}}
            <div style="text-align: center; border-top: 1px solid var(--card-border); padding-top: 1.15rem; font-size: 0.85rem; color: var(--text-muted);">
                Need a new SaaS store account?
                <a href="{{ route('register') }}" style="color: var(--primary-hover); text-decoration: none; font-weight: 600; margin-left: 0.25rem;">Register New Shop</a>
            </div>
        </div>

    </div>

    <script>
        // Password Visibility Toggle
        const toggleBtn = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');

        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', () => {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                toggleBtn.textContent = isPassword ? 'Hide' : 'Show';
            });
        }

        // Theme Toggle Handler
        const themeBtn = document.getElementById('theme-toggle-btn');
        if (themeBtn) {
            themeBtn.addEventListener('click', () => {
                const isLight = document.documentElement.classList.contains('light');
                if (isLight) {
                    document.documentElement.classList.add('dark');
                    document.documentElement.classList.remove('light-theme', 'light');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.documentElement.classList.add('light-theme', 'light');
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                }
            });
        }

        // Mode Switching (Store vs SaaS Superadmin)
        function switchMode(mode) {
            const tabStore = document.getElementById('tab-store');
            const tabSaas = document.getElementById('tab-saas');
            const title = document.getElementById('login-title');
            const subtitle = document.getElementById('login-subtitle');
            const submitBtn = document.getElementById('submit-btn');

            if (mode === 'saas') {
                tabSaas.classList.add('active');
                tabStore.classList.remove('active');
                title.textContent = 'Superadmin Portal Sign In';
                subtitle.textContent = 'Enter global SaaS platform administrative credentials.';
                submitBtn.textContent = 'Sign In to SaaS Platform';
            } else {
                tabStore.classList.add('active');
                tabSaas.classList.remove('active');
                title.textContent = 'Sign in to your account';
                subtitle.textContent = 'Enter your tenant store credentials below to continue.';
                submitBtn.textContent = 'Sign In to Store';
            }
        }

        // Instant Fill Demo Helper
        function fillDemo(email, password, mode) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
            switchMode(mode);
        }
    </script>
</body>
</html>
