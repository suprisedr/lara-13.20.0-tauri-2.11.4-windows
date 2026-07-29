@extends('layouts.public')

@section('title', 'Dashboard')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    <style>
        /* ── Layout ──────────────────────────────── */
        .dash-wrap { min-height: 100vh; background: #f6f9fc; }

        .dash-hero {
            background: linear-gradient(135deg, #4c1d95 0%, #7c3aed 50%, #6d28d9 100%);
            padding: 28pt 24pt 32pt;
            margin: 0;
            position: relative;
            overflow: hidden;
        }

        .dash-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                -45deg,
                transparent,
                transparent 40px,
                rgba(255,255,255,0.025) 40px,
                rgba(255,255,255,0.025) 41px
            );
            pointer-events: none;
        }

        .dash-hero::after {
            content: '';
            position: absolute;
            top: -60%;
            right: -20%;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(167,139,250,0.15) 0%, transparent 70%);
            pointer-events: none;
        }

        .dash-hero-inner {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
        }

        .dash-hero-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10pt;
            margin-bottom: 20pt;
        }

        .dash-hero-brand {
            display: flex;
            align-items: center;
            gap: 10pt;
        }

        .dash-hero-brand img {
            width: 32pt;
            height: 32pt;
            object-fit: contain;
        }

        .dash-hero-brand-text {
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        .dash-hero-brand-text .label {
            font-size: 5.5pt;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: rgba(167,139,250,0.8);
            margin: 0 0 1pt;
        }

        .dash-hero-brand-text .name {
            font-size: 10pt;
            font-weight: 800;
            color: #fff;
            letter-spacing: 0.02em;
        }

        .dash-hero-actions {
            display: flex;
            align-items: center;
            gap: 5pt;
        }

        .dash-hero-btn {
            display: inline-flex;
            align-items: center;
            gap: 3pt;
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
            font-size: 6pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 4pt 10pt;
            text-decoration: none;
            transition: background 0.15s;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            cursor: pointer;
        }

        .dash-hero-btn:hover { background: rgba(255,255,255,0.2); }
        .dash-hero-btn.ghost { color: #c4b5fd; border-color: rgba(167,139,250,0.3); }
        .dash-hero-btn.ghost:hover { color: #fff; }

        .dash-greeting {
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        .dash-greeting h1 {
            font-size: 18pt;
            font-weight: 900;
            color: #fff;
            margin: 0 0 4pt;
            letter-spacing: -0.02em;
        }

        .dash-greeting p {
            font-size: 7.5pt;
            color: #c4b5fd;
            margin: 0;
            line-height: 1.5;
        }

        /* ── Stats row ───────────────────────────── */
        .dash-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130pt, 1fr));
            gap: 10pt;
            margin-top: 18pt;
        }

        .dash-stat-card {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 10pt 12pt;
            backdrop-filter: blur(8px);
        }

        .dash-stat-label {
            font-size: 5pt;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(167,139,250,0.7);
            margin: 0 0 4pt;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        .dash-stat-value {
            font-size: 16pt;
            font-weight: 900;
            color: #fff;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            letter-spacing: -0.02em;
        }

        .dash-stat-sub {
            font-size: 5.5pt;
            color: rgba(167,139,250,0.6);
            margin-top: 2pt;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        @media (max-width: 760px) {
            .dash-hero { padding: 16pt 12pt 20pt; }
            .dash-greeting h1 { font-size: 14pt; }
            .dash-stats { grid-template-columns: 1fr 1fr; gap: 6pt; }
        }

        /* ── Content area ────────────────────────── */
        .dash-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 16pt 20pt 24pt;
        }

        @media (max-width: 760px) {
            .dash-content { padding: 10pt 8pt; }
        }

        /* ── Document shell ──────────────────────── */
        .cust-doc { background: #fff; border: 1px solid #e5e7eb; color: #4c1d95; font-size: 7pt; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif; line-height: 1.45; box-shadow: 0 1px 3px rgba(76,29,149,0.06); }
        .cust-doc-body { padding: 16pt 18pt; }

        .section-header { border-top: 1.5pt solid #4c1d95; margin: 0 0 8pt; padding-top: 3pt; display: flex; justify-content: space-between; align-items: baseline; }
        .section-header-title { font-size: 5.5pt; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: #4c1d95; }
        .section-header-sub { font-size: 5.5pt; color: #8b7aad; }

        /* ── Companies table ─────────────────────── */
        .cust-items-table { width: 100%; border-collapse: collapse; font-size: 7pt; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif; }
        .cust-items-table thead tr { border-bottom: 1.5pt solid #4c1d95; }
        .cust-items-table thead th { padding: 3pt 6pt; font-size: 5.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: #8b7aad; text-align: left; }
        .cust-items-table tbody tr { border-bottom: 0.4pt solid #ede9fe; transition: background 0.1s; cursor: pointer; }
        .cust-items-table tbody tr:hover { background: #f5f3ff; }
        .cust-items-table tbody tr:last-child { border-bottom: none; }
        .cust-items-table tbody td { padding: 5pt 6pt; vertical-align: middle; color: #4c1d95; }

        /* ── Status badges ───────────────────────── */
        .status-box { display: inline-block; font-size: 5pt; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; padding: 1pt 4pt; }
        .status-active   { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .status-pending  { background: #fef9c3; color: #854d0e; border: 1px solid #fde68a; }
        .status-inactive { background: #f5f3ff; color: #8b7aad; border: 1px solid #c4b5fd; }

        /* ── Empty state ─────────────────────────── */
        .empty-state { padding: 28pt 16pt; text-align: center; }
        .empty-state-icon {
            width: 48pt;
            height: 48pt;
            margin: 0 auto 10pt;
            background: linear-gradient(135deg, #7c3aed, #4c1d95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .empty-state-title { font-size: 9pt; font-weight: 800; color: #4c1d95; margin: 0 0 4pt; }
        .empty-state-body { font-size: 7pt; color: #8b7aad; margin: 0 0 12pt; max-width: 260pt; margin-left: auto; margin-right: auto; line-height: 1.65; }

        /* ── Buttons ─────────────────────────────── */
        .mgmt-btn { display: inline-flex; align-items: center; gap: 3pt; background: #fff; border: 1px solid #c4b5fd; color: #4c1d95; font-size: 6pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 3pt 7pt; text-decoration: none; cursor: pointer; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif; transition: background 0.15s, color 0.15s; }
        .mgmt-btn:hover { background: #f5f3ff; }
        .mgmt-btn.primary { background: #7c3aed; color: #fff; border-color: #7c3aed; }
        .mgmt-btn.primary:hover { background: #6d28d9; }
        .mgmt-btn.warn { border-color: #d97706; color: #d97706; }
        .mgmt-btn.warn:hover { background: #d97706; color: #fff; }

        /* ── Actions modal ───────────────────────── */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(22,53,92,0.45); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: #fff; border: 1px solid #c4b5fd; width: 100%; max-width: 240pt; margin: 0 10pt; }
        .modal-head { display: flex; align-items: center; justify-content: space-between; padding: 7pt 10pt; border-bottom: 1px solid #ddd6fe; }
        .modal-head h3 { font-size: 8pt; font-weight: 800; color: #4c1d95; margin: 0; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif; }
        .modal-close { border: none; background: transparent; font-size: 11pt; line-height: 1; color: #c4b5fd; cursor: pointer; padding: 0; }
        .modal-close:hover { color: #4c1d95; }
        .modal-body { padding: 8pt 10pt; display: flex; flex-direction: column; gap: 5pt; }

        /* ── Pagination ──────────────────────────── */
        .dash-pagination { margin-top: 10pt; border-top: 0.4pt solid #ddd6fe; padding-top: 8pt; }
        .dash-pagination nav { font-size: 6pt; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif; }
    </style>
@endpush

@section('content')
    <div class="dash-wrap">

        {{-- Hero section --}}
        <div class="dash-hero">
            <div class="dash-hero-inner">

                <div class="dash-hero-top">
                    <div class="dash-hero-brand">
                        <img src="{{ asset('storage/images/chainbook-icon-light.png') }}" alt="">
                        <div class="dash-hero-brand-text">
                            <p class="label">Chainbook Intelligence</p>
                            <span class="name">Accounting Platform</span>
                        </div>
                    </div>
                    <div class="dash-hero-actions">
                        <a href="{{ route('onboarding.step1') }}" class="dash-hero-btn">
                            <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            Add Company
                        </a>
                        <a href="{{ route('settings.edit') }}" class="dash-hero-btn ghost">
                            <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            Settings
                        </a>
                        @php $__graceSub = auth()->user()->activeSubscription(); @endphp
                        @if ($__graceSub?->isCancelled())
                            <span style="display:inline-flex;align-items:center;font-size:6pt;font-weight:700;padding:3pt 7pt;color:#fde68a;border:1px solid #d97706;white-space:nowrap;" title="Access ends {{ $__graceSub->current_period_end?->format('d M Y') }}">
                                {{ $__graceSub->daysRemaining() }} {{ Str::plural('day', $__graceSub->daysRemaining()) }} left
                            </span>
                        @endif
                        <a href="{{ route('subscriptions.manage') }}" class="dash-hero-btn ghost">
                            <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>
                            </svg>
                            Subscription
                        </a>
                        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="dash-hero-btn ghost">
                                <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                                </svg>
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>

                <div class="dash-greeting">
                    @php
                        $hour = (int) now()->format('H');
                        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
                    @endphp
                    <h1>{{ $greeting }}, {{ auth()->user()->name }}</h1>
                    <p>Welcome to your accounting workspace. You have {{ $companies->total() }} {{ Str::plural('company', $companies->total()) }} registered.</p>
                </div>

                @php
                    $activeCount = $companies->filter(fn($c) => $c->isActive() && $c->isOnboardingComplete())->count();
                    $pendingCount = $companies->filter(fn($c) => $c->isActive() && !$c->isOnboardingComplete())->count();
                @endphp
                <div class="dash-stats">
                    <div class="dash-stat-card">
                        <p class="dash-stat-label">Total Companies</p>
                        <p class="dash-stat-value">{{ $companies->total() }}</p>
                        <p class="dash-stat-sub">Registered entities</p>
                    </div>
                    <div class="dash-stat-card">
                        <p class="dash-stat-label">Active</p>
                        <p class="dash-stat-value">{{ $activeCount }}</p>
                        <p class="dash-stat-sub">Fully onboarded</p>
                    </div>
                    @if ($pendingCount > 0)
                        <div class="dash-stat-card">
                            <p class="dash-stat-label">Pending Setup</p>
                            <p class="dash-stat-value">{{ $pendingCount }}</p>
                            <p class="dash-stat-sub">Onboarding incomplete</p>
                        </div>
                    @endif
                </div>

            </div>
        </div>

        <div class="dash-content">

            {{-- Flash messages --}}
            @foreach (['company_onboarded', 'success'] as $key)
                @if (session($key))
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:5pt 8pt;font-size:7pt;font-weight:600;margin-bottom:10pt;display:flex;align-items:center;gap:4pt;font-family:Helvetica,Arial,'DejaVu Sans',sans-serif;">
                        <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        {{ session($key) }}
                    </div>
                @endif
            @endforeach

            <div class="cust-doc">
                <div class="cust-doc-body">

                    <div class="section-header">
                        <span class="section-header-title">Your Companies</span>
                        <span class="section-header-sub">{{ $companies->total() }} total</span>
                    </div>

                    @if ($companies->isEmpty())
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                    <polyline points="9 22 9 12 15 12 15 22"/>
                                </svg>
                            </div>
                            <p class="empty-state-title">No companies yet</p>
                            <p class="empty-state-body">Set up your first company to start bookkeeping, payroll, and financial reporting.</p>
                            <a href="{{ route('onboarding.step1') }}" class="mgmt-btn primary" style="padding:5pt 14pt;font-size:6.5pt;">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24">
                                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                Start Company Onboarding
                            </a>
                        </div>
                    @else
                        <table class="cust-items-table">
                            <thead>
                                <tr>
                                    <th style="width:36pt;">#</th>
                                    <th>Company Name</th>
                                    <th>Type</th>
                                    <th>Reg. Number</th>
                                    <th>Industry</th>
                                    <th>City</th>
                                    <th style="width:60pt;">Status</th>
                                    <th style="width:54pt;text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($companies as $company)
                                    @php
                                        $isActive   = $company->isActive();
                                        $isComplete = $company->isOnboardingComplete();
                                        $nextRoute  = null;
                                        $openUrl    = null;

                                        if ($isActive && ! $isComplete) {
                                            $nextRoute = match ($company->onboarding_step) {
                                                2 => route('onboarding.step2', $company),
                                                3 => route('onboarding.step3', $company),
                                                default => route('onboarding.step1'),
                                            };
                                        } elseif ($isActive) {
                                            $openUrl = route('companies.show', $company);
                                        }
                                    @endphp
                                    <tr onclick="{{ $openUrl ? "window.location='{$openUrl}'" : ($nextRoute ? "window.location='{$nextRoute}'" : 'void(0)') }}">
                                        <td>
                                            <span style="font-family:'DejaVu Sans Mono',monospace;font-size:6pt;color:#8b7aad;">{{ $company->id }}</span>
                                        </td>
                                        <td>
                                            <span style="font-weight:700;color:#4c1d95;">{{ $company->registered_name }}</span>
                                        </td>
                                        <td style="color:#6b5b8a;white-space:nowrap;">{{ $company->company_type_label }}</td>
                                        <td style="font-family:'DejaVu Sans Mono',monospace;font-size:6.5pt;color:#8b7aad;">{{ $company->registration_number ?? '—' }}</td>
                                        <td style="color:#6b5b8a;white-space:nowrap;">
                                            @if ($company->industry)
                                                {{ \App\Models\Company::industries()[$company->industry] ?? $company->industry }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td style="color:#6b5b8a;">{{ $company->city ?? '—' }}</td>
                                        <td>
                                            @if (! $isActive)
                                                <span class="status-box status-inactive">Inactive</span>
                                            @elseif ($isComplete)
                                                <span class="status-box status-active">Active</span>
                                            @else
                                                <span class="status-box status-pending">Incomplete</span>
                                            @endif
                                        </td>
                                        <td style="text-align:right;" onclick="event.stopPropagation()">
                                            <button type="button" class="mgmt-btn" style="font-size:5.5pt;padding:2pt 5pt;"
                                                onclick="openModal(this)"
                                                data-name="{{ $company->registered_name }}"
                                                data-status-action="{{ route('companies.status.toggle', $company) }}"
                                                data-is-active="{{ $isActive ? '1' : '0' }}"
                                                @if ($nextRoute) data-continue-url="{{ $nextRoute }}" @endif
                                                @if ($openUrl)   data-open-url="{{ $openUrl }}"     @endif
                                            >Actions</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @if ($companies->hasPages())
                            <div class="dash-pagination">
                                {{ $companies->links() }}
                            </div>
                        @endif
                    @endif

                </div>
            </div>

        </div>
    </div>

    {{-- Actions modal --}}
    <div id="modalOverlay" class="modal-overlay" onclick="closeModal(event)">
        <div class="modal-box">
            <div class="modal-head">
                <h3 id="modalTitle"></h3>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div id="modalBody" class="modal-body"></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function openModal(btn) {
    var title = document.getElementById('modalTitle');
    var body  = document.getElementById('modalBody');
    title.textContent = btn.dataset.name;
    body.innerHTML = '';

    if (btn.dataset.continueUrl) {
        var a = document.createElement('a');
        a.href = btn.dataset.continueUrl;
        a.className = 'mgmt-btn warn';
        a.style.width = '100%';
        a.style.justifyContent = 'center';
        a.textContent = 'Continue Setup →';
        body.appendChild(a);
    } else if (btn.dataset.openUrl) {
        var a2 = document.createElement('a');
        a2.href = btn.dataset.openUrl;
        a2.className = 'mgmt-btn primary';
        a2.style.width = '100%';
        a2.style.justifyContent = 'center';
        a2.textContent = 'Open Books →';
        body.appendChild(a2);
    }

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = btn.dataset.statusAction;
    form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH">';
    var submit = document.createElement('button');
    submit.type = 'submit';
    submit.style.width = '100%';
    submit.style.justifyContent = 'center';
    submit.className = 'mgmt-btn';
    submit.textContent = btn.dataset.isActive === '1' ? 'Deactivate Company' : 'Activate Company';
    form.appendChild(submit);
    body.appendChild(form);

    document.getElementById('modalOverlay').classList.add('open');
}

function closeModal(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('modalOverlay').classList.remove('open');
}

// ── Reverb real-time refresh ──────────────────────────────────
(function () {
    var companyIds = @json($companies->pluck('id')->values());
    var debounceTimer = null;

    function refreshContent() {
        var active = document.activeElement;
        if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.tagName === 'SELECT')) return;

        fetch(window.location.href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-Reverb-Refresh': '1' },
            credentials: 'same-origin',
        })
        .then(function (r) { return r.text(); })
        .then(function (html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var fresh = doc.querySelector('.dash-content');
            var target = document.querySelector('.dash-content');
            if (fresh && target) {
                target.innerHTML = fresh.innerHTML;
                target.querySelectorAll('script').forEach(function (old) {
                    var s = document.createElement('script');
                    if (old.src) s.src = old.src; else s.textContent = old.textContent;
                    old.parentNode.replaceChild(s, old);
                });
            }
        })
        .catch(function () {});
    }

    function schedule() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(refreshContent, 800);
    }

    function subscribe() {
        if (typeof window.Echo === 'undefined') return;
        companyIds.forEach(function (id) {
            window.Echo.private('company.' + id)
                .listen('.record.changed', schedule)
                .listen('.posting.status.updated', schedule)
                .listen('.action.updated', schedule);
        });
    }

    if (typeof window.Echo !== 'undefined') subscribe();
    else window.addEventListener('echo:ready', subscribe);
})();
</script>
@endpush
