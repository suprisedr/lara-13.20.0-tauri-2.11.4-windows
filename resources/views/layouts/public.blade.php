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

        /* Desktop app — no web chrome needed */
    </style>
</head>

<body
    style="min-height: 100vh; display: flex; flex-direction: column; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;"
    data-page="@yield('page-id')">

    <!-- Page Content -->
    <main style="flex: 1; width: 100%; display: block;">
        @yield('content')
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {});
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
