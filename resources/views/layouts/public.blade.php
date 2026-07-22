<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Chainbook Intelligence') }} — @yield('title', 'AI-Powered Accounting')</title>

    <link rel="icon" type="image/png" href="{{ asset('storage/images/chainbook-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('storage/images/chainbook-icon.png') }}">

    <!-- ── SEO Essentials ───────────────────────────────────────── -->
    <meta name="description" content="@yield('meta-description', 'Chainbook Intelligence — AI-powered financial management and accounting solutions built for modern businesses.')">
    <meta name="robots" content="@yield('meta-robots', 'index, follow')">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- ── Open Graph ───────────────────────────────────────────── -->
    <meta property="og:site_name" content="{{ config('app.name', 'Chainbook Intelligence') }}">
    <meta property="og:type" content="@yield('og-type', 'website')">
    <meta property="og:title" content="{{ config('app.name', 'Chainbook Intelligence') }} — @yield('title', 'AI-Powered Accounting')">
    <meta property="og:description" content="@yield('meta-description', 'Chainbook Intelligence — AI-powered financial management and accounting solutions built for modern businesses.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="en_ZA">

    <!-- ── Twitter / X Card ─────────────────────────────────────── -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ config('app.name', 'Chainbook Intelligence') }} — @yield('title', 'AI-Powered Accounting')">
    <meta name="twitter:description" content="@yield('meta-description', 'Chainbook Intelligence — AI-powered financial management and accounting solutions built for modern businesses.')">

    <!-- ── Schema.org JSON-LD ───────────────────────────────────── -->
    @stack('schema')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap"
        rel="stylesheet">

    <!-- GSAP + ScrollTrigger -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    @stack('styles')

    <style>
        html, body {
            background-color: #fff;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
        }

        /* ── Navbar ─────────────────────────────────────────────── */
        #main-nav {
            transition: background 0.35s, box-shadow 0.35s, backdrop-filter 0.35s;
        }

        #main-nav.nav-solid {
            background: #fff !important;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #e5e7eb !important;
        }

        /* ── Nav links ──────────────────────────────────────────── */
        .nav-link {
            position: relative;
            color: #1f2937;
            padding: 0.375rem 1rem;
            border-radius: 0;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s, background 0.2s;
            overflow: hidden;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%) scaleX(0);
            width: 60%;
            height: 2px;
            background: #5e17eb;
            border-radius: 0;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            transform: translateX(-50%) scaleX(1);
        }

        .nav-link.active {
            background: #f3eefe;
            color: #5e17eb;
        }

        .nav-link:hover:not(.active) {
            background: #faf7ff;
            color: #5e17eb;
        }

        .nav-link .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(94, 23, 235, 0.12);
            transform: scale(0);
            animation: nav-ripple 0.5s linear;
            pointer-events: none;
        }

        @keyframes nav-ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        /* ── Mobile hamburger ───────────────────────────────────── */
        #mobile-nav-btn {
            display: none;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            background: transparent;
            border: none;
            cursor: pointer;
            color: #1f2937;
            padding: 0;
            flex-shrink: 0;
        }

        #mobile-menu {
            display: none;
            flex-direction: column;
            gap: 0.25rem;
            position: fixed;
            top: 4rem;
            left: 0;
            right: 0;
            background: #fff;
            z-index: 98;
            padding: 1rem 1.5rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        #mobile-menu.open {
            display: flex;
        }

        #mobile-menu .nav-link {
            font-size: 0.95rem;
            padding: 0.75rem 1rem;
        }

        @media (max-width: 768px) {
            #desktop-nav-links {
                display: none !important;
            }

            #mobile-nav-btn {
                display: flex !important;
            }

            .aa-footer-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 2rem !important;
            }
        }

        @media (max-width: 480px) {
            .aa-footer-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>

<body
    style="min-height: 100vh; display: flex; flex-direction: column; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;"
    data-page="@yield('page-id')">

    @unless (request()->routeIs('companies.*'))
    <nav id="main-nav"
        style="position: fixed; top: 0; left: 0; width: 100%; z-index: 100; background: #fff; border-bottom: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
        <div
            style="max-width: 72rem; margin: 0 auto; padding: 0 1.5rem; height: 4rem; display: flex; align-items: center; justify-content: space-between;">

            <!-- Logo / Brand -->
            <a href="{{ url('/') }}"
                style="text-decoration: none; display: flex; align-items: center;">
                <img src="{{ asset('storage/images/chainbook-intelligence-logo.png') }}"
                    alt="{{ config('app.name', 'Chainbook Intelligence') }}"
                    style="height: 48px; width: auto; display: block;">
            </a>

            <!-- Desktop Nav Links -->
            <div id="desktop-nav-links" style="display: flex; align-items: center; gap: 0.25rem;">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}">Log
                        in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                            class="nav-link {{ request()->routeIs('register') ? 'active' : '' }}">Register</a>
                    @endif
                @endauth
            </div>

            <!-- Hamburger button (mobile only) -->
            <button id="mobile-nav-btn" aria-label="Open navigation" aria-expanded="false" aria-controls="mobile-menu">
                <svg id="hamburger-icon" width="22" height="22" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24">
                    <line x1="3" y1="6" x2="21" y2="6" />
                    <line x1="3" y1="12" x2="21" y2="12" />
                    <line x1="3" y1="18" x2="21" y2="18" />
                </svg>
                <svg id="close-icon" width="22" height="22" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24" style="display:none;">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>

        </div>
    </nav>

    <!-- Mobile menu drawer -->
    <div id="mobile-menu" role="navigation" aria-label="Mobile navigation">
        @auth
            <a href="{{ route('dashboard') }}"
                class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
        @else
            <a href="{{ route('login') }}" class="nav-link">Log in</a>
            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="nav-link">Register</a>
            @endif
        @endauth
    </div>
    @endunless

    <!-- Page Content -->
    <main style="flex: 1; width: 100%; display: block;">
        @yield('content')
    </main>

    <!-- Footer -->
    @unless (request()->routeIs('dashboard') || request()->routeIs('companies.*'))
    <footer style="background: #fff; color: #1a1a1a; font-family: inherit; border-top: 1px solid #e5e7eb;">

        <!-- Top footer: columns -->
        <div class="aa-footer-grid"
            style="max-width: 1200px; margin: 0 auto; padding: 4rem 2rem 3rem; display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 3rem;">

            <!-- Brand column -->
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <img src="{{ asset('storage/images/chainbook-intelligence-logo.png') }}"
                    alt="{{ config('app.name', 'Chainbook Intelligence') }}"
                    style="height: auto; width: 180px; max-width: 100%; display: block;">
                <p style="font-size: 0.825rem; line-height: 1.7; color: #444; max-width: 320px;">
                    AI-driven double-entry accounting for South African businesses — IFRS-for-SMEs
                    reporting, payroll, VAT, asset register and semantic search, all in one ledger.
                </p>
            </div>

            <!-- Product column -->
            <div>
                <p
                    style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; color: #1a1a1a; margin-bottom: 1.25rem;">
                    Product</p>
                <ul
                    style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                    @auth
                        <li><a href="{{ route('dashboard') }}"
                                style="font-size: 0.825rem; color: #444; text-decoration: none;"
                                onmouseover="this.style.color='#5e17eb'"
                                onmouseout="this.style.color='#444'">Dashboard</a></li>
                    @else
                        <li><a href="{{ route('login') }}"
                                style="font-size: 0.825rem; color: #444; text-decoration: none;"
                                onmouseover="this.style.color='#5e17eb'" onmouseout="this.style.color='#444'">Log In</a>
                        </li>
                        <li><a href="{{ route('register') }}"
                                style="font-size: 0.825rem; color: #444; text-decoration: none;"
                                onmouseover="this.style.color='#5e17eb'" onmouseout="this.style.color='#444'">Get
                                Started</a></li>
                    @endauth
                </ul>
            </div>

            <!-- Company column -->
            <div>
                <p
                    style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; color: #1a1a1a; margin-bottom: 1.25rem;">
                    Company</p>
                <ul
                    style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li><a href="https://chainbook.co.za" target="_blank" rel="noopener noreferrer"
                            style="font-size: 0.825rem; color: #444; text-decoration: none;"
                            onmouseover="this.style.color='#5e17eb'"
                            onmouseout="this.style.color='#444'">Chainbook</a></li>
                </ul>
            </div>

        </div>

        <!-- Divider -->
        <div style="border-top: 1px solid rgba(0,0,0,0.08); max-width: 1200px; margin: 0 auto;"></div>

        <!-- Bottom bar -->
        <div
            style="max-width: 1200px; margin: 0 auto; padding: 1.5rem 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <p style="font-size: 0.75rem; color: #555; margin: 0;">
                &copy; {{ date('Y') }} AgentAccounting &mdash; A Chainbook product. All rights reserved.
            </p>
            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                <a href="#" style="font-size: 0.75rem; color: #555; text-decoration: none;"
                    onmouseover="this.style.color='#5e17eb'" onmouseout="this.style.color='#555'">Privacy Policy</a>
                <a href="#" style="font-size: 0.75rem; color: #555; text-decoration: none;"
                    onmouseover="this.style.color='#5e17eb'" onmouseout="this.style.color='#555'">Terms of Use</a>
            </div>
        </div>

    </footer>
    @endunless

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ── Mobile nav toggle ───────────────────────────────────
            var btn = document.getElementById('mobile-nav-btn');
            var menu = document.getElementById('mobile-menu');
            var ham = document.getElementById('hamburger-icon');
            var cls = document.getElementById('close-icon');

            if (btn && menu) {
                btn.addEventListener('click', function() {
                    var open = menu.classList.toggle('open');
                    btn.setAttribute('aria-expanded', String(open));
                    ham.style.display = open ? 'none' : '';
                    cls.style.display = open ? '' : 'none';
                });
                menu.querySelectorAll('a').forEach(function(a) {
                    a.addEventListener('click', function() {
                        menu.classList.remove('open');
                        btn.setAttribute('aria-expanded', 'false');
                        ham.style.display = '';
                        cls.style.display = 'none';
                    });
                });
            }

            // ── Nav scroll solid effect ─────────────────────────────
            var nav = document.getElementById('main-nav');
            if (nav) {
                window.addEventListener('scroll', function() {
                    if (window.scrollY > 40) {
                        nav.classList.add('nav-solid');
                    } else {
                        nav.classList.remove('nav-solid');
                    }
                }, {
                    passive: true
                });
            }

            // ── Nav link ripple effect ──────────────────────────────
            document.querySelectorAll('.nav-link').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    var ripple = document.createElement('span');
                    ripple.className = 'ripple';
                    var rect = link.getBoundingClientRect();
                    var size = Math.max(rect.width, rect.height);
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
                    ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
                    link.appendChild(ripple);
                    ripple.addEventListener('animationend', function() {
                        ripple.remove();
                    });
                });
            });
        });
    </script>

    @stack('scripts')

    {{-- ── Global confirm modal (replaces browser confirm()) ── --}}
    <div id="confirm-modal"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:99999;align-items:center;justify-content:center;">
        <div style="background:#fff;width:100%;max-width:420px;margin:1rem;position:relative;border:1px solid #000;">
            <div style="padding:1.5rem 1.75rem 0;">
                <p id="confirm-modal-label"
                    style="font-size:0.58rem;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#7c3aed;margin:0 0 0.3rem;"></p>
                <h3 id="confirm-modal-title"
                    style="font-size:0.95rem;font-weight:800;color:#1b1b18;margin:0 0 0.6rem;"></h3>
                <p id="confirm-modal-body"
                    style="font-size:0.78rem;color:#374151;margin:0 0 1.25rem;line-height:1.5;"></p>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:0.6rem;padding:1rem 1.75rem;border-top:1px solid #e5e7eb;">
                <button id="confirm-modal-cancel"
                    style="background:#fff;border:1px solid #000;color:#000;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:0.45rem 1rem;cursor:pointer;font-family:inherit;">
                    Cancel
                </button>
                <button id="confirm-modal-ok"
                    style="background:#000;border:1px solid #000;color:#fff;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:0.45rem 1rem;cursor:pointer;font-family:inherit;">
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <script>
    // Global delegated submit handler — forms with data-confirm-title (and no data-ajax-confirm) get the modal
    document.addEventListener('submit', function (e) {
        const form = e.target.closest('form[data-confirm-title]');
        if (!form || form.dataset.ajaxConfirm !== undefined) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        window.showConfirmModal({
            label:       form.dataset.confirmLabel ?? '',
            title:       form.dataset.confirmTitle,
            body:        form.dataset.confirmBody ?? '',
            confirmText: form.dataset.confirmText ?? 'Confirm',
            danger:      !!form.dataset.confirmDanger,
            onConfirm:   () => { form.removeAttribute('data-confirm-title'); form.submit(); },
        });
    }, true);

    window.showConfirmModal = function ({ label, title, body, confirmText, danger, onConfirm }) {
        const modal  = document.getElementById('confirm-modal');
        const okBtn  = document.getElementById('confirm-modal-ok');

        document.getElementById('confirm-modal-label').textContent = label ?? '';
        document.getElementById('confirm-modal-title').textContent = title ?? 'Are you sure?';
        document.getElementById('confirm-modal-body').textContent  = body  ?? '';
        document.getElementById('confirm-modal-cancel').textContent = 'Cancel';
        okBtn.textContent = confirmText ?? 'Confirm';
        okBtn.style.background = danger ? '#dc2626' : '#000';
        okBtn.style.borderColor = danger ? '#dc2626' : '#000';

        modal.style.display = 'flex';

        function cleanup() {
            modal.style.display = 'none';
            okBtn.removeEventListener('click', onOk);
            document.getElementById('confirm-modal-cancel').removeEventListener('click', onCancel);
            modal.removeEventListener('click', onBackdrop);
        }
        function onOk()      { cleanup(); onConfirm?.(); }
        function onCancel()  { cleanup(); }
        function onBackdrop(e) { if (e.target === modal) { cleanup(); } }

        okBtn.addEventListener('click', onOk);
        document.getElementById('confirm-modal-cancel').addEventListener('click', onCancel);
        modal.addEventListener('click', onBackdrop);
    };
    </script>
</body>

</html>
