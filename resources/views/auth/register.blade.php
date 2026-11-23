<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Store - Retail POS</title>

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
        /* ── Two-panel registration layout ─────────────────────────────── */
        .register-split-wrapper {
            display: flex;
            min-height: 100vh;
            background-color: var(--bg-dark);
            background-image: var(--bg-image);
            background-attachment: fixed;
        }

        /* Left panel – hero image */
        .register-hero-panel {
            flex: 1;
            position: relative;
            overflow: hidden;
            display: none; /* hidden on mobile */
        }

        .register-hero-panel img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
            display: block;
        }

        /* Dark gradient overlay */
        .register-hero-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                135deg,
                rgba(8, 8, 16, 0.60) 0%,
                rgba(139, 92, 246, 0.35) 60%,
                rgba(16, 185, 129, 0.25) 100%
            );
        }

        /* Overlay text on hero */
        .hero-caption {
            position: absolute;
            bottom: 2.5rem;
            left: 2.5rem;
            right: 2.5rem;
            z-index: 2;
            color: #fff;
        }

        .hero-caption h2 {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: -0.5px;
            margin-bottom: 0.6rem;
            text-shadow: 0 2px 12px rgba(0,0,0,0.5);
        }

        .hero-caption p {
            font-size: 0.95rem;
            color: rgba(255,255,255,0.78);
            line-height: 1.55;
            text-shadow: 0 1px 6px rgba(0,0,0,0.5);
        }

        /* Pill badge */
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
            margin-bottom: 0.85rem;
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
            color: rgba(255,255,255,0.60);
            margin-top: 0.1rem;
        }

        /* Right panel – form */
        .register-form-panel {
            width: 100%;
            max-width: 580px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2.5rem;
            background: var(--card-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-left: 1px solid var(--card-border);
            gap: 1.25rem;
            overflow-y: auto;
            max-height: 100vh;
        }

        /* Show hero on larger screens */
        @media (min-width: 960px) {
            .register-hero-panel {
                display: block;
            }
        }

        @media (max-width: 520px) {
            .register-form-panel {
                padding: 1.75rem 1.25rem;
            }
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px var(--primary-glow);
        }
    </style>
</head>
<body style="margin:0; padding:0;">

    <div class="register-split-wrapper">

        {{-- ── LEFT: Hero Image Panel ── --}}
        <div class="register-hero-panel">
            <img
                src="{{ asset('images/login-hero.png') }}"
                alt="POS Cashier Centre"
            >
            <div class="hero-caption">
                <div class="hero-badge">New Store Registration</div>
                <h2>Create Your Store Account</h2>
                <p>Set up your shop profile, add your products and staff, and start processing sales from day one.</p>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <strong>Multi-Branch</strong>
                        <span>Support</span>
                    </div>
                    <div class="hero-stat">
                        <strong>Offline</strong>
                        <span>Capable</span>
                    </div>
                    <div class="hero-stat">
                        <strong>Staff</strong>
                        <span>Management</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── RIGHT: Form Panel ── --}}
        <div class="register-form-panel">

            {{-- Header --}}
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: var(--primary); color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem;">
                        L
                    </div>
                    <div>
                        <h1 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin: 0; line-height: 1.2;">Retail POS</h1>
                    </div>
                </div>

                <button type="button" id="theme-toggle-btn" class="btn-secondary" style="font-size: 0.75rem; padding: 0.35rem 0.65rem; border-radius: 6px; border-color: var(--card-border);">
                    Toggle Theme
                </button>
            </div>

            <div>
                <h2 style="font-size: 1.4rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">Create store account</h2>
                <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0;">Fill in your retail store and admin credentials below.</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger" style="border-radius: var(--radius-sm); font-size: 0.85rem; padding: 0.75rem 1rem; margin: 0;">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Form --}}
            <form action="{{ route('register') }}" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                @csrf

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Business Category *</label>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-top: 0.35rem;">
                        <label style="cursor: pointer; border: 1px solid var(--card-border); border-radius: 8px; padding: 0.6rem; text-align: center; font-size: 0.8rem; font-weight: 600;" class="biz-type-card">
                            <input type="radio" name="business_type" value="retail" {{ old('business_type', 'retail') === 'retail' ? 'checked' : '' }} style="margin-right: 4px;">
                            Retail / Shop
                        </label>
                        <label style="cursor: pointer; border: 1px solid var(--card-border); border-radius: 8px; padding: 0.6rem; text-align: center; font-size: 0.8rem; font-weight: 600;" class="biz-type-card">
                            <input type="radio" name="business_type" value="restaurant" {{ old('business_type') === 'restaurant' ? 'checked' : '' }} style="margin-right: 4px;">
                            Restaurant / Bar
                        </label>
                        <label style="cursor: pointer; border: 1px solid var(--card-border); border-radius: 8px; padding: 0.6rem; text-align: center; font-size: 0.8rem; font-weight: 600;" class="biz-type-card">
                            <input type="radio" name="business_type" value="hotel" {{ old('business_type') === 'hotel' ? 'checked' : '' }} style="margin-right: 4px;">
                            Hotel / Resort
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600;">Select Plan (14-Day Free Trial) *</label>
                    <div id="plan-cards-container" style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.35rem;">
                        @foreach($plans as $plan)
                            <label class="plan-option-card" data-business-type="{{ $plan->business_type }}" style="cursor: pointer; border: 1px solid var(--card-border); border-radius: 8px; padding: 0.65rem 0.8rem; display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem;">
                                <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                                    <input type="radio" name="plan_id" value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'checked' : '' }} style="margin-top: 3px;">
                                    <div>
                                        <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-main);">{{ $plan->name }}</div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">{{ $plan->description }}</div>
                                    </div>
                                </div>
                                <div style="font-weight: 800; font-size: 0.85rem; color: var(--primary-hover); white-space: nowrap;">
                                    UGX {{ number_format($plan->price_ugx, 0) }}/mo
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="shop_name" style="font-size: 0.85rem; font-weight: 600;">Shop / Brand Name *</label>
                    <input class="form-input" type="text" id="shop_name" name="shop_name" value="{{ old('shop_name') }}" required placeholder="e.g. Suubi Ventures" style="padding: 0.65rem; font-size: 0.9rem;">
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="business_name" style="font-size: 0.85rem; font-weight: 600;">Business Legal Name</label>
                    <input class="form-input" type="text" id="business_name" name="business_name" value="{{ old('business_name') }}" placeholder="e.g. Acme Traders" style="padding: 0.65rem; font-size: 0.9rem;">
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="currency_code" style="font-size: 0.85rem; font-weight: 600;">Currency Code *</label>
                        <input class="form-input" type="text" id="currency_code" name="currency_code" value="{{ old('currency_code', 'USD') }}" required placeholder="e.g. USD" style="padding: 0.65rem; font-size: 0.9rem;">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="currency_symbol" style="font-size: 0.85rem; font-weight: 600;">Currency Symbol *</label>
                        <input class="form-input" type="text" id="currency_symbol" name="currency_symbol" value="{{ old('currency_symbol', '$') }}" required placeholder="e.g. $" style="padding: 0.65rem; font-size: 0.9rem;">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="phone" style="font-size: 0.85rem; font-weight: 600;">Phone Number</label>
                        <input class="form-input" type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="+1 (555) 000-0000" style="padding: 0.65rem; font-size: 0.9rem;">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="location" style="font-size: 0.85rem; font-weight: 600;">Location / Address</label>
                        <input class="form-input" type="text" id="location" name="location" value="{{ old('location') }}" placeholder="New York, USA" style="padding: 0.65rem; font-size: 0.9rem;">
                    </div>
                </div>

                <div style="border-top: 1px dashed var(--card-border); margin: 0.25rem 0;"></div>

                <div>
                    <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">Owner Credentials</h3>
                    <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0;">This account will serve as the Store Administrator.</p>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="name" style="font-size: 0.85rem; font-weight: 600;">Admin Full Name *</label>
                    <input class="form-input" type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="John Doe" style="padding: 0.65rem; font-size: 0.9rem;">
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="email" style="font-size: 0.85rem; font-weight: 600;">Admin Email Address *</label>
                    <input class="form-input" type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="admin@mystore.com" style="padding: 0.65rem; font-size: 0.9rem;">
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="password" style="font-size: 0.85rem; font-weight: 600;">Password *</label>
                        <input class="form-input" type="password" id="password" name="password" required placeholder="••••••••" style="padding: 0.65rem; font-size: 0.9rem;">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="password_confirmation" style="font-size: 0.85rem; font-weight: 600;">Confirm Password *</label>
                        <input class="form-input" type="password" id="password_confirmation" name="password_confirmation" required placeholder="••••••••" style="padding: 0.65rem; font-size: 0.9rem;">
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="padding: 0.8rem; font-size: 0.95rem; font-weight: 600; background: var(--primary); border: none; border-radius: var(--radius-sm); cursor: pointer; margin-top: 0.25rem;">
                    Register Shop
                </button>
            </form>

            {{-- Footer --}}
            <div style="text-align: center; border-top: 1px solid var(--card-border); padding-top: 1rem; font-size: 0.85rem; color: var(--text-muted);">
                Already have a shop registered?
                <a href="{{ route('login') }}" style="color: var(--primary-hover); text-decoration: none; font-weight: 600; margin-left: 0.25rem;">Log In</a>
            </div>
        </div>

    </div>

    <script>
        // Business Type -> Filter Plans
        function filterPlansByBusinessType() {
            const selectedType = document.querySelector('input[name="business_type"]:checked')?.value || 'retail';
            const planCards = document.querySelectorAll('.plan-option-card');
            let firstVisibleRadio = null;
            let hasCheckedVisible = false;

            planCards.forEach(card => {
                const radio = card.querySelector('input[type="radio"]');
                const matches = card.dataset.businessType === selectedType;
                card.style.display = matches ? 'flex' : 'none';
                if (matches) {
                    if (!firstVisibleRadio) firstVisibleRadio = radio;
                    if (radio.checked) hasCheckedVisible = true;
                } else {
                    radio.checked = false;
                }
            });

            if (!hasCheckedVisible && firstVisibleRadio) {
                firstVisibleRadio.checked = true;
            }
        }

        document.querySelectorAll('input[name="business_type"]').forEach(radio => {
            radio.addEventListener('change', filterPlansByBusinessType);
        });
        filterPlansByBusinessType();

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
    </script>
</body>
</html>
