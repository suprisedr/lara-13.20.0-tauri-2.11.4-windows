@extends('layouts.public')

@section('title', 'Dashboard')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    <style>
        /* ── Layout ──────────────────────────────── */
        .dash-wrap { min-height: 100vh; background: #f6f9fc; }

        .co-topbar {
            background: #16355c;
            padding: 14pt 16pt;
            margin: 28pt 16pt 0;
            position: relative;
            overflow: hidden;
        }

        .co-topbar::before {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                -45deg,
                transparent,
                transparent 40px,
                rgba(255,255,255,0.02) 40px,
                rgba(255,255,255,0.02) 41px
            );
            pointer-events: none;
        }

        @media (max-width: 760px) {
            .co-topbar { margin: 6pt 8pt 0; padding: 10pt 10pt; }
        }

        .dash-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 16pt 20pt;
        }

        @media (max-width: 760px) {
            .dash-content { padding: 10pt 8pt; }
        }

        /* ── Document shell ──────────────────────── */
        .cust-doc { background: #fff; border: 1px solid #c9dff0; color: #16355c; font-size: 7pt; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif; line-height: 1.45; }
        .cust-doc-body { padding: 16pt 18pt; }

        .section-header { border-top: 1.5pt solid #16355c; margin: 0 0 8pt; padding-top: 3pt; display: flex; justify-content: space-between; align-items: baseline; }
        .section-header-title { font-size: 5.5pt; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: #16355c; }
        .section-header-sub { font-size: 5.5pt; color: #7a90a5; }

        /* ── Companies table ─────────────────────── */
        .cust-items-table { width: 100%; border-collapse: collapse; font-size: 7pt; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif; }
        .cust-items-table thead tr { border-bottom: 1.5pt solid #16355c; }
        .cust-items-table thead th { padding: 3pt 6pt; font-size: 5.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: #7a90a5; text-align: left; }
        .cust-items-table tbody tr { border-bottom: 0.4pt solid #ddebf5; transition: background 0.1s; cursor: pointer; }
        .cust-items-table tbody tr:hover { background: #eef6fc; }
        .cust-items-table tbody tr:last-child { border-bottom: none; }
        .cust-items-table tbody td { padding: 5pt 6pt; vertical-align: middle; color: #16355c; }

        /* ── Status badges ───────────────────────── */
        .status-box { display: inline-block; font-size: 5pt; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; padding: 1pt 4pt; }
        .status-active   { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .status-pending  { background: #fef9c3; color: #854d0e; border: 1px solid #fde68a; }
        .status-inactive { background: #eef6fc; color: #7a90a5; border: 1px solid #c9dff0; }

        /* ── Empty state ─────────────────────────── */
        .empty-state { padding: 28pt 16pt; text-align: center; }
        .empty-state-title { font-size: 9pt; font-weight: 800; color: #16355c; margin: 0 0 4pt; }
        .empty-state-body { font-size: 7pt; color: #7a90a5; margin: 0 0 12pt; max-width: 260pt; margin-left: auto; margin-right: auto; line-height: 1.65; }

        /* ── Buttons ─────────────────────────────── */
        .mgmt-btn { display: inline-flex; align-items: center; gap: 3pt; background: #fff; border: 1px solid #c9dff0; color: #16355c; font-size: 6pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 3pt 7pt; text-decoration: none; cursor: pointer; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif; transition: background 0.15s, color 0.15s; }
        .mgmt-btn:hover { background: #eef6fc; }
        .mgmt-btn.primary { background: #0079c8; color: #fff; border-color: #0079c8; }
        .mgmt-btn.primary:hover { background: #005f9e; }
        .mgmt-btn.warn { border-color: #d97706; color: #d97706; }
        .mgmt-btn.warn:hover { background: #d97706; color: #fff; }

        /* ── Actions modal ───────────────────────── */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(22,53,92,0.45); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: #fff; border: 1px solid #c9dff0; width: 100%; max-width: 240pt; margin: 0 10pt; }
        .modal-head { display: flex; align-items: center; justify-content: space-between; padding: 7pt 10pt; border-bottom: 1px solid #ddebf5; }
        .modal-head h3 { font-size: 8pt; font-weight: 800; color: #16355c; margin: 0; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif; }
        .modal-close { border: none; background: transparent; font-size: 11pt; line-height: 1; color: #a9bccd; cursor: pointer; padding: 0; }
        .modal-close:hover { color: #16355c; }
        .modal-body { padding: 8pt 10pt; display: flex; flex-direction: column; gap: 5pt; }

        /* ── Pagination ──────────────────────────── */
        .dash-pagination { margin-top: 10pt; border-top: 0.4pt solid #ddebf5; padding-top: 8pt; }
        .dash-pagination nav { font-size: 6pt; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif; }
    </style>
@endpush

@section('content')
    <div class="dash-wrap">

        {{-- Top bar --}}
        <div class="co-topbar">
            <div style="position:relative;z-index:1;display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:8pt;">
                <div>
                    <p style="font-size:5pt;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#7a90a5;margin:0 0 3pt;font-family:Helvetica,Arial,'DejaVu Sans',sans-serif;">
                        Chainbook Intelligence
                    </p>
                    <h1 style="font-size:16pt;font-weight:900;color:#fff;margin:0;letter-spacing:-0.02em;font-family:Helvetica,Arial,'DejaVu Sans',sans-serif;">
                        {{ auth()->user()->name }}
                    </h1>
                    <p style="font-size:7pt;color:#9cc3e0;margin:2pt 0 0;font-family:Helvetica,Arial,'DejaVu Sans',sans-serif;">
                        {{ $companies->total() }} {{ Str::plural('company', $companies->total()) }} registered
                    </p>
                </div>
                <a href="{{ route('onboarding.step1') }}"
                    style="display:inline-flex;align-items:center;gap:3pt;background:rgba(255,255,255,0.1);color:#fff;border:1px solid #4a5f78;font-size:6pt;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:4pt 8pt;text-decoration:none;transition:background 0.15s;font-family:Helvetica,Arial,'DejaVu Sans',sans-serif;"
                    onmouseover="this.style.background='rgba(255,255,255,0.18)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                    <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Add Company
                </a>
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
                        <span class="section-header-title">Companies</span>
                        <span class="section-header-sub">{{ $companies->total() }} total</span>
                    </div>

                    @if ($companies->isEmpty())
                        <div class="empty-state">
                            <p class="empty-state-title">No companies yet</p>
                            <p class="empty-state-body">Set up your first company to start bookkeeping, payroll, and financial reporting.</p>
                            <a href="{{ route('onboarding.step1') }}" class="mgmt-btn primary">
                                Start Company Onboarding &rarr;
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
                                            <span style="font-family:'DejaVu Sans Mono',monospace;font-size:6pt;color:#7a90a5;">{{ $company->id }}</span>
                                        </td>
                                        <td>
                                            <span style="font-weight:700;color:#16355c;">{{ $company->registered_name }}</span>
                                        </td>
                                        <td style="color:#4a5f78;white-space:nowrap;">{{ $company->company_type_label }}</td>
                                        <td style="font-family:'DejaVu Sans Mono',monospace;font-size:6.5pt;color:#7a90a5;">{{ $company->registration_number ?? '—' }}</td>
                                        <td style="color:#4a5f78;white-space:nowrap;">
                                            @if ($company->industry)
                                                {{ \App\Models\Company::industries()[$company->industry] ?? $company->industry }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td style="color:#4a5f78;">{{ $company->city ?? '—' }}</td>
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
</script>
@endpush
