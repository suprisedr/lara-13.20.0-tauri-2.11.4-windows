@extends('layouts.public')

@section('title', $company->registered_name . ' — Link Email Account')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        /* ── OAuth provider cards ─────────────────────────────────── */
        .provider-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.65rem;
            margin-bottom: 0;
        }

        @media (max-width: 640px) {
            .provider-grid { grid-template-columns: 1fr; }
        }

        .provider-card {
            border: 1px solid #ede9fe;
            padding: 0.6rem 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            position: relative;
            background: #fff;
        }

        .provider-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 3px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .provider-card.gmail::after { background: linear-gradient(180deg, #ea4335, #fbbc05, #34a853, #4285f4); }
        .provider-card.outlook::after { background: linear-gradient(180deg, #0078d4, #28a8ea); }

        .provider-card:hover {
            border-color: #c4b5fd;
            box-shadow: 0 2px 8px rgba(94, 23, 235, 0.08);
            transform: translateY(-1px);
        }

        .provider-card:hover::after { opacity: 1; }

        .provider-card .provider-logo {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }

        .provider-card .provider-text {
            flex: 1;
            min-width: 0;
        }

        .provider-card h3 {
            font-size: 0.78rem;
            font-weight: 700;
            color: #1b1b18;
            margin: 0 0 0.05rem;
        }

        .provider-card .provider-desc {
            font-size: 0.65rem;
            color: #9ca3af;
            margin: 0;
            line-height: 1.3;
        }

        .provider-card .provider-arrow {
            color: #c4b5fd;
            flex-shrink: 0;
            transition: color 0.15s, transform 0.15s;
        }

        .provider-card:hover .provider-arrow {
            color: #5e17eb;
            transform: translateX(2px);
        }

        /* ── IMAP card (same style as OAuth cards) ─────────────────── */
        .imap-card {
            border: 1px solid #ede9fe;
            padding: 0.6rem 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            position: relative;
            background: #fff;
            user-select: none;
            margin-top: 0.45rem;
        }

        .imap-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(180deg, #5e17eb, #7c3aed);
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .imap-card:hover {
            border-color: #c4b5fd;
            box-shadow: 0 2px 8px rgba(94, 23, 235, 0.08);
        }

        .imap-card:hover::after { opacity: 1; }

        .imap-card .provider-logo {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }

        .imap-card .provider-text { flex: 1; min-width: 0; }

        .imap-card h3 {
            font-size: 0.78rem;
            font-weight: 700;
            color: #1b1b18;
            margin: 0 0 0.05rem;
        }

        .imap-card .provider-desc {
            font-size: 0.65rem;
            color: #9ca3af;
            margin: 0;
        }

        .imap-card .chevron {
            transition: transform 0.2s ease;
            color: #c4b5fd;
            flex-shrink: 0;
        }

        .imap-card.open .chevron {
            transform: rotate(180deg);
            color: #5e17eb;
        }

        .imap-form-body {
            display: none;
            border: 1px solid #ede9fe;
            border-top: none;
            padding: 0.85rem 1rem;
            background: #faf8ff;
        }

        .imap-form-body.visible { display: block; }

        /* ── Form fields (compact) ────────────────────────────────── */
        .inv-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem 0.85rem;
        }

        @media (max-width: 640px) {
            .inv-form-grid { grid-template-columns: 1fr; }
        }

        .inv-field label {
            display: block;
            font-size: 0.58rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #5e17eb;
            margin-bottom: 0.15rem;
        }

        .inv-field input,
        .inv-field select {
            width: 100%;
            border: 1px solid rgba(94, 23, 235, 0.2);
            border-radius: 0;
            padding: 0.3rem 0.5rem;
            font-size: 0.74rem;
            color: #1b1b18;
            background: #fff;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            box-sizing: border-box;
            font-family: inherit;
        }

        .inv-field input::placeholder { color: #c4b5fd; font-size: 0.7rem; }

        .inv-field input:focus,
        .inv-field select:focus {
            border-color: #5e17eb;
            box-shadow: 0 0 0 2px rgba(94, 23, 235, 0.08);
        }

        .inv-field .field-hint {
            font-size: 0.6rem;
            color: #9ca3af;
            margin-top: 0.12rem;
        }

        /* ── Form actions ─────────────────────────────────────────── */
        .form-actions {
            display: flex;
            gap: 0.45rem;
            justify-content: flex-end;
            margin-top: 0.85rem;
            padding-top: 0.7rem;
            border-top: 1px solid #f3f0ff;
        }

        .btn-cancel {
            padding: 0.28rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0;
            font-size: 0.7rem;
            color: #555;
            text-decoration: none;
            font-weight: 600;
            font-family: inherit;
            background: #fff;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-cancel:hover { background: #f9fafb; }

        .btn-primary {
            padding: 0.28rem 0.85rem;
            background: #5e17eb;
            color: #fff;
            border: none;
            border-radius: 0;
            font-size: 0.7rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-primary:hover { background: #4a10c4; }

        /* ── Info callout ──────────────────────────────────────────── */
        .info-callout {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: #f5f3ff;
            border: 1px solid #ede9fe;
            margin-bottom: 1.15rem;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .info-callout svg { flex-shrink: 0; margin-top: 0.05rem; }

        .info-callout p {
            font-size: 0.72rem;
            color: #6b7280;
            margin: 0;
            line-height: 1.4;
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">

        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">
                <div class="co-card">
                    <div class="co-card-head">
                        <div>
                            <div class="co-section-heading"><p class="co-section-label">Integrations</p><h2>Link Email Account</h2></div>
                        </div>
</div>

                    <div style="padding:1rem 1.5rem 1.25rem;">
                        @if (session('error'))
                            <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:0.75rem 1rem;border-radius:0;font-size:0.8rem;margin-bottom:1.25rem;display:flex;align-items:flex-start;gap:0.6rem;">
                                <svg width="16" height="16" fill="none" stroke="#b91c1c" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:0.1rem;">
                                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                                </svg>
                                <span>{{ session('error') }}</span>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:0.75rem 1rem;border-radius:0;font-size:0.8rem;margin-bottom:1.25rem;">
                                <strong>Please fix the following errors:</strong>
                                <ul style="margin:0.4rem 0 0 1.1rem;padding:0;">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Info callout --}}
                        <div class="info-callout">
                            <svg width="14" height="14" fill="none" stroke="#7c3aed" stroke-width="1.75" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="16" x2="12" y2="12"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                            </svg>
                            <p>
                                Connect a mailbox to automatically import supplier emails. Incoming senders are matched to existing suppliers &mdash;
                                new senders create a pending supplier profile for your review.
                            </p>
                        </div>

                        {{-- Provider section --}}
                        <p style="font-size:0.62rem;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;color:#7c3aed;margin:0 0 0.5rem;">
                            Choose a provider
                        </p>

                        <div class="provider-grid">
                            <a href="{{ route('companies.email-accounts.oauth', [$company, 'gmail']) }}" class="provider-card gmail">
                                {{-- Google "G" logo --}}
                                <svg class="provider-logo" viewBox="0 0 48 48">
                                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                    <path fill="#FBBC05" d="M10.53 28.59A14.5 14.5 0 0 1 9.5 24c0-1.59.28-3.14.76-4.59l-7.98-6.19A23.99 23.99 0 0 0 0 24c0 3.77.9 7.35 2.56 10.52l7.97-5.93z"/>
                                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 5.93C6.51 42.62 14.62 48 24 48z"/>
                                </svg>
                                <div class="provider-text">
                                    <h3>Gmail</h3>
                                    <p class="provider-desc">Personal and Google Workspace inboxes</p>
                                </div>
                                <svg class="provider-arrow" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>

                            <a href="{{ route('companies.email-accounts.oauth', [$company, 'outlook']) }}" class="provider-card outlook">
                                {{-- Microsoft logo --}}
                                <svg class="provider-logo" viewBox="0 0 48 48">
                                    <rect x="1" y="1" width="21" height="21" fill="#F25022"/>
                                    <rect x="26" y="1" width="21" height="21" fill="#7FBA00"/>
                                    <rect x="1" y="26" width="21" height="21" fill="#00A4EF"/>
                                    <rect x="26" y="26" width="21" height="21" fill="#FFB900"/>
                                </svg>
                                <div class="provider-text">
                                    <h3>Outlook / Microsoft 365</h3>
                                    <p class="provider-desc">Outlook.com and Office 365 accounts</p>
                                </div>
                                <svg class="provider-arrow" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        </div>

                        {{-- IMAP / POP card --}}
                        <div class="imap-card" id="imap-toggle" role="button" tabindex="0" aria-expanded="false">
                            {{-- Server/IMAP icon --}}
                            <svg class="provider-logo" viewBox="0 0 48 48" fill="none">
                                <rect x="4" y="6" width="40" height="16" rx="3" fill="#ede9fe" stroke="#7c3aed" stroke-width="2"/>
                                <rect x="4" y="26" width="40" height="16" rx="3" fill="#ede9fe" stroke="#7c3aed" stroke-width="2"/>
                                <circle cx="12" cy="14" r="2.5" fill="#7c3aed"/>
                                <circle cx="12" cy="34" r="2.5" fill="#7c3aed"/>
                                <line x1="19" y1="14" x2="36" y2="14" stroke="#7c3aed" stroke-width="2" stroke-linecap="round"/>
                                <line x1="19" y1="34" x2="36" y2="34" stroke="#7c3aed" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <div class="provider-text">
                                <h3>IMAP / POP</h3>
                                <p class="provider-desc">Custom mail servers and other providers</p>
                            </div>
                            <svg class="chevron" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </div>

                        <div class="imap-form-body" id="imap-form">
                            <form method="POST" action="{{ route('companies.email-accounts.store', $company) }}">
                                @csrf

                                <div class="inv-form-grid">
                                    <div class="inv-field">
                                        <label for="provider">Protocol</label>
                                        <select id="provider" name="provider">
                                            <option value="imap" {{ old('provider') === 'imap' ? 'selected' : '' }}>IMAP (recommended)</option>
                                            <option value="pop" {{ old('provider') === 'pop' ? 'selected' : '' }}>POP3</option>
                                        </select>
                                    </div>

                                    <div class="inv-field">
                                        <label for="email_address">Email Address</label>
                                        <input type="email" id="email_address" name="email_address" value="{{ old('email_address') }}" placeholder="you@company.com" required>
                                    </div>

                                    <div class="inv-field">
                                        <label for="imap_host">Server Host</label>
                                        <input type="text" id="imap_host" name="imap_host" value="{{ old('imap_host') }}" placeholder="imap.example.com" required>
                                        <p class="field-hint">Your mail server's IMAP or POP3 hostname</p>
                                    </div>

                                    <div class="inv-field">
                                        <label for="imap_port">Port</label>
                                        <input type="number" id="imap_port" name="imap_port" value="{{ old('imap_port', 993) }}" required>
                                        <p class="field-hint">993 for IMAP/SSL, 995 for POP3/SSL</p>
                                    </div>

                                    <div class="inv-field">
                                        <label for="imap_encryption">Encryption</label>
                                        <select id="imap_encryption" name="imap_encryption">
                                            <option value="ssl" {{ old('imap_encryption', 'ssl') === 'ssl' ? 'selected' : '' }}>SSL / TLS</option>
                                            <option value="tls" {{ old('imap_encryption') === 'tls' ? 'selected' : '' }}>STARTTLS</option>
                                            <option value="none" {{ old('imap_encryption') === 'none' ? 'selected' : '' }}>None (not recommended)</option>
                                        </select>
                                    </div>

                                    <div class="inv-field">
                                        <label for="imap_username">Username</label>
                                        <input type="text" id="imap_username" name="imap_username" value="{{ old('imap_username') }}" placeholder="you@company.com" required>
                                        <p class="field-hint">Usually the same as your email address</p>
                                    </div>

                                    <div class="inv-field" style="grid-column:span 2;">
                                        <label for="imap_password">Password</label>
                                        <input type="password" id="imap_password" name="imap_password" placeholder="App password or mail password" required>
                                        <p class="field-hint">Your credentials are encrypted at rest and never shared</p>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <a href="{{ route('companies.email-accounts.index', $company) }}" class="btn-cancel">Cancel</a>
                                    <button type="submit" class="btn-primary" id="imap-submit-btn" onclick="this.innerHTML='<svg width=&quot;14&quot; height=&quot;14&quot; fill=&quot;none&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;2&quot; viewBox=&quot;0 0 24 24&quot; style=&quot;animation:spin 1s linear infinite&quot;><path d=&quot;M21 12a9 9 0 1 1-6.22-8.56&quot;/></svg> Testing connection…';this.disabled=true;this.closest('form').submit();">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                            <polyline points="22 4 12 14.01 9 11.01"/>
                                        </svg>
                                        Test &amp; Connect
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        (function () {
            const toggle = document.getElementById('imap-toggle');
            const form = document.getElementById('imap-form');

            function expandForm() {
                form.classList.add('visible');
                toggle.classList.add('open');
                toggle.setAttribute('aria-expanded', 'true');
            }

            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                const isOpen = form.classList.toggle('visible');
                toggle.classList.toggle('open', isOpen);
                toggle.setAttribute('aria-expanded', isOpen);
            });

            toggle.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggle.click();
                }
            });

            @if (old('provider') || session('error'))
                expandForm();
            @endif
        })();
    </script>
@endsection
