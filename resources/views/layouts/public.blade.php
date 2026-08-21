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

    <!-- System font stack (no external font loading needed) -->

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
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
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

    {{-- ── PDF download handler (Tauri desktop) ── --}}
    <div id="pdf-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:99998;align-items:center;justify-content:center;">
        <div style="background:#fff;padding:2rem 2.5rem;text-align:center;border:1px solid #000;max-width:360px;width:90%;">
            <div id="pdf-spinner" style="display:inline-block;width:28px;height:28px;border:3px solid #d3e2f5;border-top-color:#005bf0;border-radius:50%;animation:pdfspin .7s linear infinite;margin-bottom:0.8rem;"></div>
            <p id="pdf-status" style="font-size:0.82rem;font-weight:600;color:#191919;margin:0;"></p>
        </div>
    </div>
    <style>@keyframes pdfspin{to{transform:rotate(360deg)}}</style>

    <script>
    (function() {
        var isTauri = window.__TAURI_INTERNALS__ || window.__TAURI__;
        if (!isTauri) return;

        var overlay  = document.getElementById('pdf-overlay');
        var status   = document.getElementById('pdf-status');
        var spinner  = document.getElementById('pdf-spinner');
        var busy     = false;

        function showOverlay(msg, isError) {
            status.textContent = msg;
            spinner.style.display = isError ? 'none' : 'inline-block';
            if (isError) status.style.color = '#dc2626';
            else status.style.color = '#191919';
            overlay.style.display = 'flex';
        }

        function hideOverlay() {
            overlay.style.display = 'none';
            busy = false;
        }

        function extractFilenameFromUrl(href) {
            var params = new URLSearchParams(href.split('?')[1] || '');
            var fmt = params.get('format') || 'pdf';
            var ext = { xlsx: 'xlsx', csv: 'csv', ods: 'ods', pdf: 'pdf' }[fmt] || fmt;
            var segments = href.split('?')[0].split('/');
            for (var i = segments.length - 2; i >= 0; i--) {
                if (segments[i] && segments[i] !== 'pdf' && segments[i] !== 'export' && segments[i] !== 'download') {
                    return segments[i].replace(/\s+/g, '-') + '.' + ext;
                }
            }
            return 'document.' + ext;
        }

        function extractFilenameFromResponse(resp, fallback) {
            var cd = resp.headers.get('content-disposition') || '';
            var match = cd.match(/filename[*]?=(?:UTF-8''|"?)([^";]+)/i);
            if (match) return decodeURIComponent(match[1].replace(/["]/g, ''));
            return fallback;
        }

        // Matches endpoints ending in pdf/export/download, whether the word is
        // its own path segment (/invoices/12/pdf) or suffixed onto one
        // (/payroll/irp5-bulk-pdf).
        function isDownloadLink(href) {
            return !!href && /[\/-](pdf|export|download)(\?|$|#)/.test(href);
        }

        async function fetchWithRetry(url) {
            var maxAttempts = 4;
            for (var attempt = 0; attempt < maxAttempts; attempt++) {
                showOverlay(attempt > 0 ? 'PDF is being generated, please wait…' : 'Preparing PDF…');

                var resp = await fetch(url, { credentials: 'same-origin' });

                if (resp.status === 202 || resp.status === 204) {
                    if (attempt < maxAttempts - 1) {
                        await new Promise(function(r) { setTimeout(r, 2000 * (attempt + 1)); });
                        continue;
                    }
                    throw new Error('PDF is still being generated. Please try again in a moment.');
                }

                if (!resp.ok) {
                    throw new Error('Server error: ' + resp.status);
                }

                var ct = resp.headers.get('content-type') || '';
                if (ct.indexOf('text/html') !== -1) {
                    if (attempt < maxAttempts - 1) {
                        await new Promise(function(r) { setTimeout(r, 2000 * (attempt + 1)); });
                        continue;
                    }
                    throw new Error('PDF is not ready yet. Please try again.');
                }

                return { response: resp, buffer: await resp.arrayBuffer() };
            }
        }

        async function downloadPdf(href) {
            if (busy) return;
            busy = true;

            var url = href.startsWith('http') ? href : window.location.origin + href;
            var fallbackName = extractFilenameFromUrl(href);

            try {
                var result = await fetchWithRetry(url);
                var filename = extractFilenameFromResponse(result.response, fallbackName);
                showOverlay('Saving to Downloads…');

                var bytes = Array.from(new Uint8Array(result.buffer));
                var savedPath = await window.__TAURI__.core.invoke('save_pdf', {
                    bytes: bytes,
                    filename: filename
                });

                status.style.color = '#16a34a';
                spinner.style.display = 'none';
                status.textContent = 'Saved & opened: ' + savedPath.split('/').pop();
                setTimeout(hideOverlay, 2000);
            } catch (err) {
                var msg = (err && err.message) ? err.message : String(err);
                showOverlay('Download failed: ' + msg, true);
                setTimeout(hideOverlay, 3500);
            }
        }

        document.addEventListener('click', function(e) {
            var link = e.target.closest('a[href]');
            if (!link) return;

            var href = link.getAttribute('href');
            if (!isDownloadLink(href)) return;
            if (link.hasAttribute('data-pdf-preview')) return;

            e.preventDefault();
            e.stopPropagation();
            downloadPdf(href);
        }, true);

        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (form.method && form.method.toLowerCase() !== 'get') return;

            var action = form.getAttribute('action') || '';
            if (!isDownloadLink(action)) return;
            if (form.hasAttribute('data-pdf-preview')) return;

            e.preventDefault();
            e.stopPropagation();

            var params = new URLSearchParams(new FormData(form)).toString();
            var base = action.startsWith('http') ? action : window.location.origin + action;
            var href = params ? base + '?' + params : base;
            downloadPdf(href);
        }, true);
    })();
    </script>

    @stack('scripts')

    {{-- ── Global confirm modal (replaces browser confirm()) ── --}}
    <div id="confirm-modal"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:99999;align-items:center;justify-content:center;">
        <div style="background:#fff;width:100%;max-width:420px;margin:1rem;position:relative;border:1px solid #000;">
            <div style="padding:1.5rem 1.75rem 0;">
                <p id="confirm-modal-label"
                    style="font-size:0.68rem;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#005bf0;margin:0 0 0.3rem;"></p>
                <h3 id="confirm-modal-title"
                    style="font-size:0.95rem;font-weight:800;color:#191919;margin:0 0 0.6rem;"></h3>
                <p id="confirm-modal-body"
                    style="font-size:0.78rem;color:#191919;margin:0 0 1.25rem;line-height:1.5;"></p>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:0.6rem;padding:1rem 1.75rem;border-top:1px solid #d3e2f5;">
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
