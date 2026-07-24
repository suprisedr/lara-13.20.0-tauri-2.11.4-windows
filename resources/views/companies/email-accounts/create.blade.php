@extends('layouts.public')

@section('title', $company->registered_name . ' — Link Email Account')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .cn-form { max-width:920px; }
        .cn-section { margin-bottom:1.75rem; }
        .cn-section-title { font-size:0.65rem; font-weight:800; letter-spacing:0.1em; text-transform:uppercase; color:#5e17eb; margin:0 0 0.75rem; }
        .cn-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem 1.25rem; }
        .cn-field label { display:block; font-size:0.6rem; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:#555; margin-bottom:0.22rem; }
        .cn-field input, .cn-field select {
            width:100%; border:1px solid #ccc; padding:0.35rem 0.55rem;
            font-size:0.8rem; font-family:inherit; color:#000; background:#fff; box-sizing:border-box;
        }
        .cn-field input:focus, .cn-field select:focus { outline:none; border-color:#5e17eb; }
        .cn-field .field-hint { font-size:0.68rem; color:#9ca3af; margin-top:0.15rem; }
        .btn-submit { background:#000; color:#fff; border:none; padding:0.55rem 1.5rem; font-size:0.82rem; font-weight:700; cursor:pointer; transition:background 0.15s; font-family:inherit; display:inline-flex; align-items:center; gap:0.4rem; }
        .btn-submit:hover { background:#333; }
        .form-error { color:#dc2626; font-size:0.72rem; margin-top:0.2rem; }

        .ea-provider-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.75rem; }
        @media (max-width:640px) { .ea-provider-grid { grid-template-columns:1fr; } }

        .ea-provider-card {
            border:1px solid #ccc; padding:0.6rem 0.85rem; cursor:pointer;
            display:flex; align-items:center; gap:0.65rem;
            text-decoration:none; background:#fff; transition:border-color 0.15s;
        }
        .ea-provider-card:hover { border-color:#000; }
        .ea-provider-card .ea-plogo { width:24px; height:24px; flex-shrink:0; }
        .ea-provider-card .ea-ptext { flex:1; min-width:0; }
        .ea-provider-card h3 { font-size:0.78rem; font-weight:700; color:#000; margin:0 0 0.05rem; }
        .ea-provider-card .ea-pdesc { font-size:0.65rem; color:#888; margin:0; line-height:1.3; }
        .ea-provider-card .ea-parrow { color:#ccc; flex-shrink:0; transition:color 0.15s, transform 0.15s; }
        .ea-provider-card:hover .ea-parrow { color:#000; transform:translateX(2px); }

        .ea-imap-toggle {
            border:1px solid #ccc; padding:0.6rem 0.85rem; cursor:pointer;
            display:flex; align-items:center; gap:0.65rem;
            background:#fff; user-select:none; transition:border-color 0.15s;
        }
        .ea-imap-toggle:hover { border-color:#000; }
        .ea-imap-toggle .ea-ptext { flex:1; min-width:0; }
        .ea-imap-toggle h3 { font-size:0.78rem; font-weight:700; color:#000; margin:0 0 0.05rem; }
        .ea-imap-toggle .ea-pdesc { font-size:0.65rem; color:#888; margin:0; }
        .ea-imap-toggle .ea-chevron { color:#ccc; flex-shrink:0; transition:transform 0.2s, color 0.15s; }
        .ea-imap-toggle.open .ea-chevron { transform:rotate(180deg); color:#000; }

        .ea-imap-body { display:none; border:1px solid #ccc; border-top:none; padding:1rem 1.25rem; background:#fafafa; }
        .ea-imap-body.visible { display:block; }

        .ea-info-callout {
            display:flex; align-items:flex-start; gap:0.5rem;
            padding:0.5rem 0.75rem; background:#f9f9f9; border:1px solid #ddd; margin-bottom:1.25rem;
        }
        .ea-info-callout svg { flex-shrink:0; margin-top:0.05rem; }
        .ea-info-callout p { font-size:0.74rem; color:#555; margin:0; line-height:1.45; }

        @keyframes spin { to { transform:rotate(360deg); } }
    </style>
@endpush

@section('content')
    <div class="co-wrap">

        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                <div class="cn-form">

                    @if (session('error'))
                        <div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:0.75rem 1.25rem;font-size:0.82rem;margin-bottom:1.25rem;">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:0.75rem 1.25rem;font-size:0.82rem;margin-bottom:1.25rem;">
                            <strong>Please fix the following:</strong>
                            <ul style="margin:0.4rem 0 0 1.1rem;padding:0;">
                                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="ea-info-callout">
                        <svg width="14" height="14" fill="none" stroke="#555" stroke-width="1.75" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                        <p>Connect a mailbox to automatically import supplier emails. Incoming senders are matched to existing suppliers &mdash; new senders create a pending supplier profile for your review.</p>
                    </div>

                    <div class="cn-section">
                        <p class="cn-section-title">Choose a Provider</p>

                        <div class="ea-provider-grid">
                            <a href="{{ route('companies.email-accounts.oauth', [$company, 'gmail']) }}" class="ea-provider-card">
                                <svg class="ea-plogo" viewBox="0 0 48 48">
                                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                    <path fill="#FBBC05" d="M10.53 28.59A14.5 14.5 0 0 1 9.5 24c0-1.59.28-3.14.76-4.59l-7.98-6.19A23.99 23.99 0 0 0 0 24c0 3.77.9 7.35 2.56 10.52l7.97-5.93z"/>
                                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 5.93C6.51 42.62 14.62 48 24 48z"/>
                                </svg>
                                <div class="ea-ptext">
                                    <h3>Gmail</h3>
                                    <p class="ea-pdesc">Personal and Google Workspace inboxes</p>
                                </div>
                                <svg class="ea-parrow" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>

                            <a href="{{ route('companies.email-accounts.oauth', [$company, 'outlook']) }}" class="ea-provider-card">
                                <svg class="ea-plogo" viewBox="0 0 48 48">
                                    <rect x="1" y="1" width="21" height="21" fill="#F25022"/>
                                    <rect x="26" y="1" width="21" height="21" fill="#7FBA00"/>
                                    <rect x="1" y="26" width="21" height="21" fill="#00A4EF"/>
                                    <rect x="26" y="26" width="21" height="21" fill="#FFB900"/>
                                </svg>
                                <div class="ea-ptext">
                                    <h3>Outlook / Microsoft 365</h3>
                                    <p class="ea-pdesc">Outlook.com and Office 365 accounts</p>
                                </div>
                                <svg class="ea-parrow" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        </div>

                        <div class="ea-imap-toggle" id="imap-toggle" role="button" tabindex="0" aria-expanded="false">
                            <svg class="ea-plogo" viewBox="0 0 48 48" fill="none">
                                <rect x="4" y="6" width="40" height="16" rx="3" fill="#eee" stroke="#555" stroke-width="2"/>
                                <rect x="4" y="26" width="40" height="16" rx="3" fill="#eee" stroke="#555" stroke-width="2"/>
                                <circle cx="12" cy="14" r="2.5" fill="#555"/>
                                <circle cx="12" cy="34" r="2.5" fill="#555"/>
                                <line x1="19" y1="14" x2="36" y2="14" stroke="#555" stroke-width="2" stroke-linecap="round"/>
                                <line x1="19" y1="34" x2="36" y2="34" stroke="#555" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <div class="ea-ptext">
                                <h3>IMAP / POP</h3>
                                <p class="ea-pdesc">Custom mail servers and other providers</p>
                            </div>
                            <svg class="ea-chevron" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </div>

                        <div class="ea-imap-body" id="imap-form">
                            <form method="POST" action="{{ route('companies.email-accounts.store', $company) }}">
                                @csrf

                                <div class="cn-grid">
                                    <div class="cn-field">
                                        <label>Protocol</label>
                                        <select name="provider">
                                            <option value="imap" {{ old('provider') === 'imap' ? 'selected' : '' }}>IMAP (recommended)</option>
                                            <option value="pop" {{ old('provider') === 'pop' ? 'selected' : '' }}>POP3</option>
                                        </select>
                                    </div>

                                    <div class="cn-field">
                                        <label>Email Address <span style="color:#dc2626;">*</span></label>
                                        <input type="email" name="email_address" value="{{ old('email_address') }}" placeholder="you@company.com" required>
                                    </div>

                                    <div class="cn-field">
                                        <label>Server Host <span style="color:#dc2626;">*</span></label>
                                        <input type="text" name="imap_host" value="{{ old('imap_host') }}" placeholder="imap.example.com" required>
                                        <p class="field-hint">Your mail server's IMAP or POP3 hostname</p>
                                    </div>

                                    <div class="cn-field">
                                        <label>Port <span style="color:#dc2626;">*</span></label>
                                        <input type="number" name="imap_port" value="{{ old('imap_port', 993) }}" required>
                                        <p class="field-hint">993 for IMAP/SSL, 995 for POP3/SSL</p>
                                    </div>

                                    <div class="cn-field">
                                        <label>Encryption</label>
                                        <select name="imap_encryption">
                                            <option value="ssl" {{ old('imap_encryption', 'ssl') === 'ssl' ? 'selected' : '' }}>SSL / TLS</option>
                                            <option value="tls" {{ old('imap_encryption') === 'tls' ? 'selected' : '' }}>STARTTLS</option>
                                            <option value="none" {{ old('imap_encryption') === 'none' ? 'selected' : '' }}>None (not recommended)</option>
                                        </select>
                                    </div>

                                    <div class="cn-field">
                                        <label>Username <span style="color:#dc2626;">*</span></label>
                                        <input type="text" name="imap_username" value="{{ old('imap_username') }}" placeholder="you@company.com" required>
                                        <p class="field-hint">Usually the same as your email address</p>
                                    </div>

                                    <div class="cn-field" style="grid-column:span 2;">
                                        <label>Password <span style="color:#dc2626;">*</span></label>
                                        <input type="password" name="imap_password" placeholder="App password or mail password" required>
                                        <p class="field-hint">Your credentials are encrypted at rest and never shared</p>
                                    </div>
                                </div>

                                <div style="display:flex;gap:1rem;align-items:center;margin-top:1.25rem;">
                                    <button type="submit" class="btn-submit" id="imap-submit-btn" onclick="this.innerHTML='<svg width=&quot;14&quot; height=&quot;14&quot; fill=&quot;none&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;2&quot; viewBox=&quot;0 0 24 24&quot; style=&quot;animation:spin 1s linear infinite&quot;><path d=&quot;M21 12a9 9 0 1 1-6.22-8.56&quot;/></svg> Testing connection…';this.disabled=true;this.closest('form').submit();">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                            <polyline points="22 4 12 14.01 9 11.01"/>
                                        </svg>
                                        Test &amp; Connect
                                    </button>
                                    <a href="{{ route('companies.email-accounts.index', $company) }}" style="font-size:0.78rem;color:#6b7280;text-decoration:none;">Cancel</a>
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
