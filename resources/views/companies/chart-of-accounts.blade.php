@extends('layouts.public')

@section('title', $company->registered_name . ' — Chart of Accounts')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        /* ── COA document overrides ───────────────────────────────── */
        .doc-title {
            font-size: 10pt;
            font-weight: 800;
            text-align: right;
            letter-spacing: 0.04em;
            color: #1a345b;
        }

        .doc-meta-line { text-align: right; font-size: 7pt; color: #5a7186; }

        .divider {
            border: none;
            border-top: 1.5pt solid #1a345b;
            margin: 6pt 0 8pt;
        }

        .divider.light {
            border-top: 0.5pt solid #d3e2f5;
            margin: 8pt 0;
        }

        .section-header {
            font-weight: 700;
            font-size: 7.5pt;
            text-transform: none;
            letter-spacing: 0;
            border-bottom: 1.5pt solid #1a345b;
            padding-bottom: 2pt;
            margin-bottom: 4pt;
            color: #1a345b;
        }

        /* ── Date filter bar inside doc ─────────────────────────── */
        .coa-filter-bar {
            display: flex;
            align-items: flex-end;
            gap: 6pt;
            flex-wrap: wrap;
            margin-bottom: 10pt;
        }

        .coa-filter-bar label {
            display: block;
            font-size: 10pt;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: none;
            color: #5a7186;
            margin-bottom: 2pt;
        }

        .coa-filter-bar input[type="date"] {
            border: 1px solid #9ec1f5;
            padding: 2pt 4pt;
            font-size: 7pt;
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
            color: #1a345b;
        }

        .coa-filter-bar input[type="date"]:focus {
            border-color: #1a345b;
            outline: none;
        }

        .coa-filter-bar button[type="submit"] {
            border: 0.5pt solid #000000;
            background: #1a345b;
            color: #fff;
            font-size: 10pt;
            font-weight: 700;
            text-transform: none;
            letter-spacing: 0;
            padding: 3pt 8pt;
            cursor: pointer;
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        .coa-filter-bar a.reset-link {
            font-size: 10pt;
            color: #6f869b;
            text-decoration: none;
            align-self: flex-end;
            padding-bottom: 3pt;
        }

        .coa-period-badge {
            margin-left: 3pt;
            background: #f4fafc;
            color: #1a345b;
            font-size: 7pt;
            font-weight: 700;
            padding: 1pt 3pt;
            text-transform: none;
            letter-spacing: 0;
            border: 1px solid #9ec1f5;
        }

        /* ── Summary row ────────────────────────────────────────── */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2pt;
        }

        .summary-table td {
            padding: 0 8pt 0 0;
            font-size: 7pt;
            vertical-align: top;
        }

        .summary-table .lbl {
            display: block;
            font-weight: 700;
            font-size: 10pt;
            text-transform: none;
            letter-spacing: 0;
            margin-bottom: 1pt;
            color: #5a7186;
        }

        .summary-table .amt {
            font-size: 10pt;
            font-weight: 800;
            color: #1a345b;
        }

        /* ── COA table ──────────────────────────────────────────── */
        #coa-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
        }

        #coa-table thead td {
            font-weight: 700;
            font-size: 10.5pt;
            text-transform: none;
            letter-spacing: 0;
            border-bottom: 1.5pt solid #1a345b;
            padding-bottom: 3pt;
            padding-top: 0;
            color: #1a345b;
            background: #f4fafc;
        }

        #coa-table thead td.num { text-align: right; }

        #coa-table tbody td {
            padding: 3pt 0;
            border-bottom: 1px solid #d3e2f5;
            vertical-align: middle;
        }

        #coa-table tbody tr:last-child td { border-bottom: none; }
        #coa-table tbody tr:hover td { background: #f4fafc; }

        /* Section headers (Assets / Liabilities / Equity ...) */
        #coa-table .is-section-label-lg td {
            font-size: 10.5pt;
            font-weight: 800;
            color: #1a345b;
            text-transform: none;
            letter-spacing: 0;
            padding: 8pt 0 2pt;
            border-bottom: 0.5pt solid #000000;
        }

        #coa-table .is-subsection-header td {
            font-size: 10.5pt;
            font-weight: 700;
            color: #005bf0;
            text-transform: none;
            letter-spacing: 0;
            padding: 4pt 0 2pt;
            border-bottom: none;
        }

        #coa-table .is-group-header td { font-weight: 700; color: #1a345b; }

        #coa-table .is-group-subtotal td {
            font-size: 10.5pt;
            font-weight: 700;
            color: #1a345b;
            border-top: 1.5pt solid #1a345b;
            border-bottom: 1.5pt solid #1a345b;
            background: #eaf8fb;
        }

        #coa-table .is-item-row td { padding-left: 8pt; }
        #coa-table .is-item-row td:first-child { padding-left: 8pt; }

        /* Code column */
        .coa-code {
            font-family: inherit; font-variant-numeric: tabular-nums;
            font-size: 10pt;
            color: #5a7186;
            white-space: nowrap;
            width: 70px;
        }

        .coa-code.parent { color: #1a345b; font-weight: 700; }

        /* Category column */
        .coa-cat {
            font-size: 10pt;
            color: #6f869b;
            white-space: nowrap;
            width: 110px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Balance column */
        .num {
            text-align: right;
            font-family: inherit; font-variant-numeric: tabular-nums;
            white-space: nowrap;
            width: 130px;
        }

        /* Role badge */
        .coa-role-badge {
            display: inline-block;
            font-size: 7pt;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: none;
            padding: 1pt 3pt;
            vertical-align: middle;
            margin-left: 3pt;
            border: 1px solid #9ec1f5;
            background: #f4fafc;
            color: #005bf0;
        }

        /* Add-child button */
        .coa-add-child-btn {
            display: inline-flex;
            align-items: center;
            gap: 2pt;
            font-size: 10pt;
            font-weight: 700;
            color: #5a7186;
            background: #f4fafc;
            border: 1px solid #9ec1f5;
            padding: 1pt 3pt;
            cursor: pointer;
            text-decoration: none;
            vertical-align: middle;
            margin-left: 4pt;
            line-height: 1.4;
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        .coa-add-child-btn:hover { background: #eaf8fb; }

        /* Sep / OCI / IAS toggles */
        .coa-sep-toggle {
            display: inline-flex;
            align-items: center;
            gap: 2pt;
            font-size: 10pt;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: none;
            color: #6f869b;
            background: #f4fafc;
            border: 1px solid #9ec1f5;
            padding: 1pt 3pt;
            cursor: pointer;
            vertical-align: middle;
            margin-left: 3pt;
        }

        .coa-sep-toggle input[type="checkbox"] {
            width: 11px; height: 11px; margin: 0;
            accent-color: #1a345b; cursor: pointer;
        }

        .coa-sep-toggle.is-on { color: #1a345b; background: #eaf8fb; border-color: #9ec1f5; }

        /* Rename / delete inline buttons */
        .coa-rename-btn {
            background: none; border: none; color: #9ec1f5;
            cursor: pointer; font-size: 10pt; padding: 0 2pt;
            vertical-align: middle; line-height: 1; transition: color 0.15s;
        }

        .coa-rename-btn:hover { color: #1a345b; }

        /* Account link */
        .coa-account-link { color: inherit; text-decoration: none; }
        .coa-account-link:hover { text-decoration: underline; color: #005bf0; }

        /* File / folder icons */
        .coa-toggle {
            display: inline-flex; align-items: center; gap: 2pt;
            cursor: pointer; margin-right: 3pt; vertical-align: middle;
        }

        .coa-chevron { flex-shrink: 0; transition: transform 0.15s ease; color: #6f869b; }
        tr.coa-collapsed .coa-chevron { transform: rotate(-90deg); }

        .coa-icon-folder { flex-shrink: 0; color: #5a7186; }
        .coa-icon-file {
            display: inline-block; flex-shrink: 0; float: left;
            color: #9ec1f5; margin-right: 3pt; margin-top: 1pt;
        }

        /* Collapsed total */
        .coa-collapsed-total {
            display: none;
            font-family: inherit; font-variant-numeric: tabular-nums;
            font-size: 10pt; color: #6f869b;
            margin-left: 4pt; font-weight: 400;
        }

        tr.coa-collapsed > td > .coa-collapsed-total { display: inline; }

        /* Modal overlay */
        .coa-modal-backdrop {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.45); z-index: 1000;
            align-items: center; justify-content: center;
        }

        .coa-modal-backdrop.is-open { display: flex; }

        .coa-modal {
            background: #fff;
            border-top: 3pt solid #1a345b;
            border-left: 1px solid #9ec1f5;
            border-right: 1px solid #9ec1f5;
            border-bottom: 1px solid #9ec1f5;
            width: 100%; max-width: 480px;
            margin: 8pt; overflow: hidden;
        }

        .coa-modal-head {
            border-bottom: 1.5pt solid #1a345b;
            padding: 8pt 12pt;
            display: flex; align-items: flex-start;
            justify-content: space-between; gap: 8pt;
        }

        .coa-modal-head-label {
            font-size: 10pt; font-weight: 800;
            letter-spacing: 0; text-transform: none;
            color: #005bf0; margin: 0 0 2pt;
        }

        .coa-modal-head h3 {
            font-size: 10pt; font-weight: 800;
            color: #1a345b; margin: 0;
        }

        .coa-modal-close {
            background: none; border: none; color: #9ec1f5;
            font-size: 11.5pt; line-height: 1;
            cursor: pointer; flex-shrink: 0;
        }

        .coa-modal-close:hover { color: #1a345b; }

        .coa-modal-body { padding: 10pt 12pt; }

        .coa-field { margin-bottom: 8pt; }

        .coa-field > label {
            display: block; font-size: 10pt; font-weight: 700;
            color: #5a7186; margin-bottom: 2pt;
            text-transform: none; letter-spacing: 0;
        }

        .coa-field input:not([type="checkbox"]),
        .coa-field select,
        .coa-field textarea {
            width: 100%; box-sizing: border-box;
            border: 1px solid #9ec1f5;
            padding: 2pt 4pt;
            font-size: 7pt; font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
            color: #1a345b; outline: none;
            transition: border-color 0.15s;
        }

        .coa-field input:not([type="checkbox"]):focus,
        .coa-field select:focus,
        .coa-field textarea:focus {
            border-color: #1a345b;
            box-shadow: 0 0 0 3px rgba(26, 52, 91,0.08);
        }

        .coa-field textarea { resize: vertical; min-height: 54pt; }

        .coa-checkbox-row {
            display: flex; align-items: center; gap: 4pt; margin-top: 2pt;
        }

        .coa-checkbox-row input[type="checkbox"] {
            width: 15px; height: 15px; accent-color: #1a345b; cursor: pointer;
        }

        .coa-checkbox-row label {
            font-size: 7pt; font-weight: 600; color: #5a7186;
            cursor: pointer; text-transform: none; letter-spacing: 0;
        }

        .coa-modal-footer {
            padding: 6pt 12pt;
            border-top: 1px solid #d3e2f5;
            display: flex; gap: 4pt; justify-content: flex-end;
        }

        .coa-btn-cancel {
            background: #fff; border: 0.5pt solid #000000; color: #1a345b;
            padding: 3pt 8pt; font-size: 10pt; font-weight: 700;
            text-transform: none; letter-spacing: 0;
            cursor: pointer; font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        .coa-btn-cancel:hover { background: #f4fafc; }

        .coa-btn-submit {
            background: #1a345b; border: 0.5pt solid #000000; color: #fff;
            padding: 3pt 8pt; font-size: 10pt; font-weight: 700;
            text-transform: none; letter-spacing: 0;
            cursor: pointer; font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        .coa-btn-submit:hover { background: #0d2847; }

        @media (max-width: 640px) {
            .reg-doc-body { padding: 8pt 6pt; }
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">

        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                @if (session('success'))
                    <div style="background:#eaf8fb;border:1px solid #9ec1f5;color:#1a345b;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:10pt;">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- ── Management bar ── --}}
                <div class="reg-mgmt-bar">
<div style="display:flex;align-items:center;gap:4pt;flex-wrap:wrap;">
                        <button type="button" class="reg-btn primary"
                            onclick="document.getElementById('modal-add-parent').classList.add('is-open')">
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add Account
                        </button>
                        @if ($chartOfAccounts->isNotEmpty())
                            <a href="{{ route('companies.opening-balances.edit', $company) }}" class="reg-btn">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Opening Balances
                            </a>
                        @endif
                        {{-- Export dropdown --}}
                        <div style="position:relative;" id="export-dropdown-wrapper">
                            <button type="button" class="reg-btn"
                                onclick="(function(){var m=document.getElementById('export-menu');m.style.display=m.style.display==='block'?'none':'block';})()">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                Export
                                <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            <div id="export-menu"
                                style="display:none;position:absolute;right:0;top:calc(100% + 4px);background:#fff;border:1px solid #d3e2f5;box-shadow:0 4px 16px rgba(0,0,0,0.10);min-width:175px;z-index:50;overflow:hidden;">
                                @php $exportParams = array_filter(['start_date' => $startDate, 'end_date' => $endDate]); @endphp
                                @foreach (['csv' => 'CSV (.csv)', 'xlsx' => 'Excel (.xlsx)', 'ods' => 'Spreadsheet (.ods)', 'pdf' => 'PDF (.pdf)'] as $fmt => $label)
                                    <a href="{{ route('companies.chart-of-accounts.export', $company) . '?' . http_build_query(array_merge($exportParams, ['format' => $fmt])) }}"
                                        style="display:flex;align-items:center;gap:0.55rem;padding:0.55rem 0.9rem;font-size:10pt;font-weight:600;color:#191919;text-decoration:none;border-bottom:1px solid #f4fafc;">
                                        <svg width="12" height="12" fill="none" stroke="#005bf0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Document card ── --}}
                <div class="reg-doc">
                    <div class="reg-doc-body">

                        {{-- Header --}}
                        <table style="width:100%;border-collapse:collapse;margin-bottom:6pt;">
                            <tr>
                                <td style="vertical-align:top;width:55%;">
                                    <div style="font-size:11.5pt;font-weight:800;letter-spacing:-0.01em;color:#1a345b;">{{ $company->registered_name }}</div>
                                    <div style="font-size:7pt;color:#5a7186;margin-top:2pt;">
                                        {{ $company->company_type_label }}
                                        @if ($company->industry) &middot; {{ \App\Models\Company::industries()[$company->industry] ?? $company->industry }} @endif
                                        @if ($company->city) &middot; {{ $company->city }} @endif
                                    </div>
                                </td>
                                <td style="vertical-align:top;text-align:right;">
                                    <div class="doc-title">CHART OF ACCOUNTS</div>
                                    <div class="doc-meta-line">
                                        @if ($startDate)
                                            {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                                        @else
                                            To {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                                        @endif
                                    </div>
                                    <div class="doc-meta-line" style="margin-top:2pt;">
                                        <span style="display:inline-block;border:1px solid #1a345b;padding:1pt 4pt;font-size:7pt;font-weight:700;letter-spacing:0;text-transform:none;color:#1a345b;">
                                            {{ $chartOfAccounts->sum(fn($g) => $g->count()) }} Accounts
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <hr class="divider">

                        {{-- Date filter --}}
                        <form method="GET" action="{{ route('companies.chart-of-accounts', $company) }}" class="coa-filter-bar">
                            <div>
                                <label>From</label>
                                <input type="date" name="start_date" value="{{ $startDate }}">
                            </div>
                            <div>
                                <label>To</label>
                                <input type="date" name="end_date" value="{{ $endDate }}">
                            </div>
                            <button type="submit">Apply</button>
                            @if ($startDate !== $defaultStartDate || $endDate !== now()->format('Y-m-d'))
                                <a href="{{ route('companies.chart-of-accounts', $company) }}" class="reset-link">Reset</a>
                            @endif
                            <div style="position:relative;margin-left:auto;display:flex;align-items:center;gap:3pt;">
                                <input type="text" id="coa-search-input" placeholder="Search accounts…" autocomplete="off"
                                    style="height:14pt;border:1px solid #9ec1f5;padding:0 4pt;font-size:7pt;font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;width:150pt;box-sizing:border-box;border-radius:0;color:#1a345b;"
                                    oninput="coaLiveFilter(this.value)">
                                <button type="button" id="coa-search-clear" onclick="coaLiveFilter('');document.getElementById('coa-search-input').value='';"
                                    style="display:none;background:none;border:none;cursor:pointer;font-size:7pt;color:#5a7186;padding:0 2pt;line-height:1;"
                                    title="Clear search">&times;</button>
                                <span id="coa-match-count" style="font-size:7pt;color:#6f869b;white-space:nowrap;"></span>
                            </div>
                            <span style="font-size:7pt;color:#6f869b;align-self:flex-end;padding-bottom:3pt;">
                                @if ($startDate)
                                    Period activity
                                    <span class="coa-period-badge">Period</span>
                                @else
                                    Cumulative to <strong>{{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</strong>
                                @endif
                            </span>
                        </form>

                        @if ($chartOfAccounts->isEmpty())
                            <div style="text-align:center;padding:20pt 8pt;color:#6f869b;">
                                <svg width="36" height="36" fill="none" stroke="#9ec1f5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="display:block;margin:0 auto 6pt;">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                                <p style="font-weight:700;color:#5a7186;margin:0 0 2pt;">Chart of accounts is being generated</p>
                                <p style="font-size:7pt;margin:0;color:#6f869b;">The AI agent is building your accounts in the background. Check back shortly.</p>
                            </div>
                        @else
                            @php
                                $typeOrder = ['assets', 'liabilities', 'equity', 'income', 'expenses'];
                                $typeLabels = [
                                    'assets'      => 'Assets',
                                    'liabilities' => 'Liabilities',
                                    'equity'      => 'Equity',
                                    'income'      => 'Income',
                                    'expenses'    => 'Expenses',
                                ];
                                // Matches the account-code ranges the chart-of-accounts AI agent uses:
                                // assets 1006000+ (PPE) and liabilities 2006000+ (long-term) are non-current.
                                $currentThreshold = ['assets' => 1006000, 'liabilities' => 2006000];
                                $currentLabel  = ['assets' => 'Current Assets', 'liabilities' => 'Current Liabilities'];
                                $nonCurrentLabel = ['assets' => 'Non-Current Assets', 'liabilities' => 'Non-Current Liabilities'];

                                $coaToggle = '<span class="coa-toggle" onclick="toggleCoaGroup(this)">'
                                    . '<svg class="coa-chevron" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>'
                                    . '<svg class="coa-icon-folder" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>'
                                    . '</span>';

                                $coaFileIcon = '<svg class="coa-icon-file" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';

                                $calcCoaTotal = function ($accounts) {
                                    return $accounts->sum(function ($account) {
                                        return $account->items->isNotEmpty()
                                            ? (float) ($account->balance ?? 0) + $account->items->sum('balance')
                                            : (float) ($account->balance ?? $account->opening_balance);
                                    });
                                };

                                $coaTotalSpan = function ($total) {
                                    $formatted = 'R ' . number_format(abs($total), 2) . ($total < 0 ? ' Cr' : '');
                                    return '<span class="coa-collapsed-total">' . $formatted . '</span>';
                                };
                            @endphp

                            {{-- Summary KPIs --}}
                            @php
                                $totalAssets      = $chartOfAccounts->has('assets')      ? $calcCoaTotal($chartOfAccounts['assets'])      : 0;
                                $totalLiabilities = $chartOfAccounts->has('liabilities') ? $calcCoaTotal($chartOfAccounts['liabilities']) : 0;
                                $totalEquity      = $chartOfAccounts->has('equity')      ? $calcCoaTotal($chartOfAccounts['equity'])      : 0;
                                $totalIncome      = $chartOfAccounts->has('income')      ? $calcCoaTotal($chartOfAccounts['income'])      : 0;
                                $totalExpenses    = $chartOfAccounts->has('expenses')    ? $calcCoaTotal($chartOfAccounts['expenses'])    : 0;
                                $netProfit        = $totalIncome - $totalExpenses;
                            @endphp
                            <table class="summary-table" style="margin-bottom:10pt;">
                                <tr>
                                    <td>
                                        <span class="lbl">Total Assets</span>
                                        <span class="amt">R {{ number_format($totalAssets, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="lbl">Total Liabilities</span>
                                        <span class="amt">R {{ number_format($totalLiabilities, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="lbl">Equity</span>
                                        <span class="amt">R {{ number_format($totalEquity, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="lbl">Net Profit / (Loss)</span>
                                        <span class="amt" style="{{ $netProfit < 0 ? 'color:#dc2626;' : '' }}">
                                            R {{ number_format(abs($netProfit), 2) }}{{ $netProfit < 0 ? ' Loss' : '' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="lbl">Accounts</span>
                                        <span class="amt">{{ $chartOfAccounts->sum(fn($g) => $g->count()) }}</span>
                                    </td>
                                </tr>
                            </table>

                            <hr class="divider light" style="margin-top:0;">

                            {{-- COA table --}}
                            <div style="overflow-x:auto;">
                                <table id="coa-table">
                                    <thead>
                                        <tr>
                                            <td class="coa-code">Code</td>
                                            <td class="coa-cat">Category</td>
                                            <td>Account Name</td>
                                            <td class="hide-mobile" style="max-width:200px;">Description</td>
                                            <td class="num">Balance</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($typeOrder as $type)
                                            @if (!$chartOfAccounts->has($type))
                                                @continue
                                            @endif

                                            @php
                                                $typeId    = 'type-' . $type;
                                                $accounts  = $chartOfAccounts[$type];
                                                $threshold = $currentThreshold[$type] ?? null;
                                                $typeTotal = $calcCoaTotal($accounts);

                                                if ($threshold) {
                                                    $currentGroup    = $accounts->filter(fn($a) => (int) $a->account_code < $threshold);
                                                    $nonCurrentGroup = $accounts->filter(fn($a) => (int) $a->account_code >= $threshold);
                                                }
                                            @endphp

                                            {{-- Section header --}}
                                            <tr class="is-section-label-lg coa-header" data-coa-id="{{ $typeId }}">
                                                <td colspan="5">
                                                    {!! $coaToggle !!}{{ $typeLabels[$type] }}{!! $coaTotalSpan($typeTotal) !!}
                                                </td>
                                            </tr>

                                            @if ($threshold)
                                                @if ($currentGroup->isNotEmpty())
                                                    @php $currentId = $typeId . '-current'; $currentTotal = $calcCoaTotal($currentGroup); @endphp
                                                    <tr class="is-subsection-header coa-header" data-coa-id="{{ $currentId }}" data-coa-parents="{{ $typeId }}">
                                                        <td colspan="5">{!! $coaToggle !!}{{ $currentLabel[$type] }}{!! $coaTotalSpan($currentTotal) !!}</td>
                                                    </tr>
                                                    @foreach ($currentGroup as $account)
                                                        @include('companies._coa-account-rows', ['account' => $account, 'typeLabels' => $typeLabels, 'ancestorIds' => [$typeId, $currentId], 'coaToggle' => $coaToggle, 'coaFileIcon' => $coaFileIcon])
                                                    @endforeach
                                                @endif

                                                @if ($nonCurrentGroup->isNotEmpty())
                                                    @php $nonCurrentId = $typeId . '-noncurrent'; $nonCurrentTotal = $calcCoaTotal($nonCurrentGroup); @endphp
                                                    <tr class="is-subsection-header coa-header" data-coa-id="{{ $nonCurrentId }}" data-coa-parents="{{ $typeId }}">
                                                        <td colspan="5">{!! $coaToggle !!}{{ $nonCurrentLabel[$type] }}{!! $coaTotalSpan($nonCurrentTotal) !!}</td>
                                                    </tr>
                                                    @foreach ($nonCurrentGroup as $account)
                                                        @include('companies._coa-account-rows', ['account' => $account, 'typeLabels' => $typeLabels, 'ancestorIds' => [$typeId, $nonCurrentId], 'coaToggle' => $coaToggle, 'coaFileIcon' => $coaFileIcon])
                                                    @endforeach
                                                @endif
                                            @else
                                                @foreach ($accounts as $account)
                                                    @include('companies._coa-account-rows', ['account' => $account, 'typeLabels' => $typeLabels, 'ancestorIds' => [$typeId], 'coaToggle' => $coaToggle, 'coaFileIcon' => $coaFileIcon])
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                    </div>{{-- /.reg-doc-body --}}
                </div>{{-- /.reg-doc --}}

            </main>
        </div>
    </div>

    {{-- ═══ Add Parent Account Modal ═══ --}}
    <div id="modal-add-parent" class="coa-modal-backdrop"
        onclick="if(event.target===this)this.classList.remove('is-open')">
        <div class="coa-modal">
            <div class="coa-modal-head">
                <div>
                    <p class="coa-modal-head-label">Chart of Accounts</p>
                    <h3>Add Account</h3>
                </div>
                <button class="coa-modal-close"
                    onclick="document.getElementById('modal-add-parent').classList.remove('is-open')">&times;</button>
            </div>
            <form method="POST" action="{{ route('companies.chart-of-accounts.store', $company) }}">
                @csrf
                <div class="coa-modal-body">
                    <div class="coa-field">
                        <label for="pa-name">Account Name <span style="color:#b91c1c;">*</span></label>
                        <input id="pa-name" name="account_name" type="text" required placeholder="e.g. Bank Accounts" autofocus>
                    </div>
                    <div class="coa-field">
                        <label for="pa-type">Account Type <span style="color:#b91c1c;">*</span></label>
                        <select id="pa-type" name="account_type" required onchange="toggleExpenseClass()">
                            <option value="">— Select type —</option>
                            <option value="assets">Assets</option>
                            <option value="liabilities">Liabilities</option>
                            <option value="equity">Equity</option>
                            <option value="income">Income</option>
                            <option value="expenses">Expenses</option>
                        </select>
                    </div>
                    <div class="coa-field" id="pa-expense-class-field" style="display:none;">
                        <label for="pa-expense-class">Expense classification <span style="color:#b91c1c;">*</span></label>
                        <select id="pa-expense-class" name="expense_class">
                            <option value="operating">Operating expenses</option>
                            <option value="cost_of_sales">Cost of sales (direct costs)</option>
                            <option value="finance">Finance costs</option>
                            <option value="tax">Income tax</option>
                        </select>
                        <small style="display:block;margin-top:2pt;color:#6f869b;font-size:7pt;">
                            Determines where this account appears in the income statement.
                        </small>
                    </div>
                    <div class="coa-field">
                        <label for="pa-category">Category</label>
                        <input id="pa-category" name="category" type="text" placeholder="e.g. Banking">
                    </div>
                    <div class="coa-field">
                        <label for="pa-description">Description</label>
                        <textarea id="pa-description" name="description" placeholder="Optional description…"></textarea>
                    </div>
                    <div class="coa-field">
                        <div class="coa-checkbox-row">
                            <input type="checkbox" id="pa-is-contra" name="is_contra" value="1">
                            <label for="pa-is-contra">Contra account <span style="color:#6f869b;font-weight:400;">(opposite normal balance)</span></label>
                        </div>
                    </div>
                </div>
                <div class="coa-modal-footer">
                    <button type="button" class="coa-btn-cancel"
                        onclick="document.getElementById('modal-add-parent').classList.remove('is-open')">Cancel</button>
                    <button type="submit" class="coa-btn-submit">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ Add Child Account Modal ═══ --}}
    <div id="modal-add-child" class="coa-modal-backdrop"
        onclick="if(event.target===this)this.classList.remove('is-open')">
        <div class="coa-modal">
            <div class="coa-modal-head">
                <div>
                    <p class="coa-modal-head-label">Chart of Accounts</p>
                    <h3>Add Child Account</h3>
                    <p id="child-modal-subtitle" style="font-size:7pt;color:#6f869b;margin:2pt 0 0;">Under <strong id="child-parent-name"></strong></p>
                </div>
                <button class="coa-modal-close"
                    onclick="document.getElementById('modal-add-child').classList.remove('is-open')">&times;</button>
            </div>
            <form id="form-add-child" method="POST" action="">
                @csrf
                <div class="coa-modal-body">
                    <div class="coa-field">
                        <label for="ch-name">Account Name <span style="color:#b91c1c;">*</span></label>
                        <input id="ch-name" name="account_name" type="text" required placeholder="e.g. Food Inventory">
                    </div>
                    <div class="coa-field">
                        <label for="ch-category">Category</label>
                        <input id="ch-category" name="category" type="text" placeholder="e.g. Inventory">
                    </div>
                    <div class="coa-field">
                        <label for="ch-description">Description</label>
                        <textarea id="ch-description" name="description" placeholder="Optional description…"></textarea>
                    </div>
                    <div class="coa-field">
                        <label for="ch-balance">Opening Balance</label>
                        <input id="ch-balance" name="opening_balance" type="number" step="0.01" placeholder="0.00">
                    </div>
                    <div class="coa-field">
                        <div class="coa-checkbox-row">
                            <input type="checkbox" id="ch-is-contra" name="is_contra" value="1">
                            <label for="ch-is-contra">Contra account <span style="color:#6f869b;font-weight:400;">(opposite normal balance)</span></label>
                        </div>
                    </div>
                </div>
                <div class="coa-modal-footer">
                    <button type="button" class="coa-btn-cancel"
                        onclick="document.getElementById('modal-add-child').classList.remove('is-open')">Cancel</button>
                    <button type="submit" class="coa-btn-submit">Create Child Account</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ Edit Account Modal ═══ --}}
    <div id="modal-rename-account" class="coa-modal-backdrop"
        onclick="if(event.target===this)this.classList.remove('is-open')">
        <div class="coa-modal">
            <div class="coa-modal-head">
                <div>
                    <p class="coa-modal-head-label">Chart of Accounts</p>
                    <h3>Edit Account</h3>
                </div>
                <button class="coa-modal-close"
                    onclick="document.getElementById('modal-rename-account').classList.remove('is-open')">&times;</button>
            </div>
            <form id="form-rename-account" method="POST" action="">
                @csrf
                @method('PATCH')
                <div class="coa-modal-body">
                    <div class="coa-field">
                        <label for="rename-account-name">Account Name <span style="color:#b91c1c;">*</span></label>
                        <input id="rename-account-name" name="account_name" type="text" required autofocus>
                    </div>
                    <div class="coa-field">
                        <label style="display:flex;align-items:flex-start;gap:4pt;cursor:pointer;font-weight:500;text-transform:none;letter-spacing:0;">
                            <input id="rename-is-contra" name="is_contra" type="checkbox" value="1"
                                style="margin-top:2pt;flex-shrink:0;accent-color:#1a345b;width:14px;height:14px;">
                            <span style="font-size:7pt;font-weight:600;color:#5a7186;">
                                Contra account
                                <span style="display:block;font-size:7pt;color:#6f869b;font-weight:400;margin-top:1pt;">
                                    Carries an opposite normal balance (e.g. accumulated depreciation). Excluded from financial statements, appears on trial balance only.
                                </span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="coa-modal-footer">
                    <button type="button" class="coa-btn-cancel"
                        onclick="document.getElementById('modal-rename-account').classList.remove('is-open')">Cancel</button>
                    <button type="submit" class="coa-btn-submit">Save</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', recomputeCoaVisibility);

            var COA_KEY = 'coa-collapsed-ids';

            function saveCoaState() {
                var table = document.getElementById('coa-table');
                if (!table) return;
                var ids = [];
                table.querySelectorAll('tbody tr.coa-collapsed').forEach(function(row) {
                    if (row.dataset.coaId) ids.push(row.dataset.coaId);
                });
                localStorage.setItem(COA_KEY, JSON.stringify(ids));
            }

            function restoreCoaState() {
                var saved = localStorage.getItem(COA_KEY);
                if (!saved) return;
                var ids = JSON.parse(saved);
                var table = document.getElementById('coa-table');
                if (!table) return;
                table.querySelectorAll('tbody tr').forEach(function(row) {
                    if (ids.indexOf(row.dataset.coaId) !== -1) row.classList.add('coa-collapsed');
                });
                recomputeCoaVisibility();
            }

            function toggleCoaGroup(el) {
                var row = el.closest('tr');
                row.classList.toggle('coa-collapsed');
                recomputeCoaVisibility();
                saveCoaState();
            }

            function recomputeCoaVisibility() {
                var table = document.getElementById('coa-table');
                if (!table) return;
                var rows = table.querySelectorAll('tbody tr');
                var collapsedIds = [];
                rows.forEach(function(row) {
                    if (row.classList.contains('coa-collapsed') && row.dataset.coaId)
                        collapsedIds.push(row.dataset.coaId);
                });
                rows.forEach(function(row) {
                    var parents = (row.dataset.coaParents || '').split(' ').filter(Boolean);
                    row.style.display = parents.some(p => collapsedIds.indexOf(p) !== -1) ? 'none' : '';
                });
            }

            document.addEventListener('DOMContentLoaded', restoreCoaState);

            function toggleExpenseClass() {
                var isExpense = document.getElementById('pa-type').value === 'expenses';
                var field  = document.getElementById('pa-expense-class-field');
                var select = document.getElementById('pa-expense-class');
                field.style.display = isExpense ? '' : 'none';
                select.disabled = !isExpense;
                select.required = isExpense;
            }

            function openAddChildModal(parentId, parentName, parentCode, actionUrl) {
                document.getElementById('child-parent-name').textContent = parentName + ' (' + parentCode + ')';
                document.getElementById('form-add-child').action = actionUrl;
                document.getElementById('ch-name').value = '';
                document.getElementById('ch-category').value = '';
                document.getElementById('ch-description').value = '';
                document.getElementById('ch-balance').value = '';
                document.getElementById('ch-is-contra').checked = false;
                document.getElementById('modal-add-child').classList.add('is-open');
                setTimeout(function() { document.getElementById('ch-name').focus(); }, 100);
            }

            function openRenameModal(actionUrl, accountName, isContra) {
                document.getElementById('form-rename-account').action = actionUrl;
                document.getElementById('rename-account-name').value = accountName;
                document.getElementById('rename-is-contra').checked = !!isContra;
                document.getElementById('modal-rename-account').classList.add('is-open');
                setTimeout(function() {
                    var input = document.getElementById('rename-account-name');
                    input.focus(); input.select();
                }, 100);
            }

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    ['modal-add-parent', 'modal-add-child', 'modal-rename-account'].forEach(function(id) {
                        document.getElementById(id).classList.remove('is-open');
                    });
                }
            });

            document.addEventListener('click', function(e) {
                var wrapper = document.getElementById('export-dropdown-wrapper');
                var menu    = document.getElementById('export-menu');
                if (wrapper && menu && !wrapper.contains(e.target)) menu.style.display = 'none';
            });

            toggleExpenseClass();

            const COA_CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            function toggleContra(el) {
                const turnOn = !el.classList.contains('is-on');
                fetch(el.dataset.url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': COA_CSRF },
                    body: JSON.stringify({ is_contra: turnOn }),
                }).then(r => r.json()).then(data => {
                    if (data.success) el.classList.toggle('is-on', data.is_contra);
                    else alert('Could not update the contra status.');
                }).catch(() => alert('Could not update the contra status.'));
            }

            function toggleShowSeparately(el) {
                const turnOn = el.checked;
                const label  = el.closest('.coa-sep-toggle');
                fetch(el.dataset.url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': COA_CSRF },
                    body: JSON.stringify({ show_separately: turnOn }),
                }).then(r => r.json()).then(data => {
                    if (data.success) label.classList.toggle('is-on', data.show_separately);
                    else { el.checked = !turnOn; alert('Could not update the display setting.'); }
                }).catch(() => { el.checked = !turnOn; alert('Could not update the display setting.'); });
            }

            function toggleOci(el) {
                const turnOn = el.checked;
                const label  = el.closest('.coa-sep-toggle');
                fetch(el.dataset.url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': COA_CSRF },
                    body: JSON.stringify({ is_oci: turnOn }),
                }).then(r => r.json()).then(data => {
                    if (data.success) label.classList.toggle('is-on', data.is_oci);
                    else { el.checked = !turnOn; alert('Could not update the OCI setting.'); }
                }).catch(() => { el.checked = !turnOn; alert('Could not update the OCI setting.'); });
            }

            function togglePpe(el) {
                const turnOn = el.checked;
                const label  = el.closest('.coa-sep-toggle');
                fetch(el.dataset.url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': COA_CSRF },
                    body: JSON.stringify({ is_ppe: turnOn }),
                }).then(r => r.json()).then(data => {
                    if (data.success) label.classList.toggle('is-on', data.is_ppe);
                    else { el.checked = !turnOn; alert('Could not update the PPE setting.'); }
                }).catch(() => { el.checked = !turnOn; alert('Could not update the PPE setting.'); });
            }

            function toggleInventory(el) {
                const turnOn = el.checked;
                const label  = el.closest('.coa-sep-toggle');
                fetch(el.dataset.url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': COA_CSRF },
                    body: JSON.stringify({ is_inventory: turnOn }),
                }).then(r => r.json()).then(data => {
                    if (data.success) label.classList.toggle('is-on', data.is_inventory);
                    else { el.checked = !turnOn; alert('Could not update the inventory setting.'); }
                }).catch(() => { el.checked = !turnOn; alert('Could not update the inventory setting.'); });
            }

            function toggleIntangible(el) {
                const turnOn = el.checked;
                const label  = el.closest('.coa-sep-toggle');
                fetch(el.dataset.url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': COA_CSRF },
                    body: JSON.stringify({ is_intangible: turnOn }),
                }).then(r => r.json()).then(data => {
                    if (data.success) label.classList.toggle('is-on', data.is_intangible);
                    else { el.checked = !turnOn; alert('Could not update the intangible setting.'); }
                }).catch(() => { el.checked = !turnOn; alert('Could not update the intangible setting.'); });
            }

            function toggleInvestmentProperty(el) {
                const turnOn = el.checked;
                const label  = el.closest('.coa-sep-toggle');
                fetch(el.dataset.url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': COA_CSRF },
                    body: JSON.stringify({ is_investment_property: turnOn }),
                }).then(r => r.json()).then(data => {
                    if (data.success) label.classList.toggle('is-on', data.is_investment_property);
                    else { el.checked = !turnOn; alert('Could not update the investment property setting.'); }
                }).catch(() => { el.checked = !turnOn; alert('Could not update the investment property setting.'); });
            }

            function toggleBiologicalAsset(el) {
                const turnOn = el.checked;
                const label  = el.closest('.coa-sep-toggle');
                fetch(el.dataset.url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': COA_CSRF },
                    body: JSON.stringify({ is_biological_asset: turnOn }),
                }).then(r => r.json()).then(data => {
                    if (data.success) label.classList.toggle('is-on', data.is_biological_asset);
                    else { el.checked = !turnOn; alert('Could not update the biological asset setting.'); }
                }).catch(() => { el.checked = !turnOn; alert('Could not update the biological asset setting.'); });
            }

            function toggleLeaseAsset(el) {
                const turnOn = el.checked;
                const label  = el.closest('.coa-sep-toggle');
                fetch(el.dataset.url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': COA_CSRF },
                    body: JSON.stringify({ is_lease_asset: turnOn }),
                }).then(r => r.json()).then(data => {
                    if (data.success) label.classList.toggle('is-on', data.is_lease_asset);
                    else { el.checked = !turnOn; alert('Could not update the lease asset setting.'); }
                }).catch(() => { el.checked = !turnOn; alert('Could not update the lease asset setting.'); });
            }

            function toggleCash(el) {
                const turnOn = el.checked;
                const label  = el.closest('.coa-sep-toggle');
                fetch(el.dataset.url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': COA_CSRF },
                    body: JSON.stringify({ is_cash: turnOn }),
                }).then(r => r.json()).then(data => {
                    if (data.success) label.classList.toggle('is-on', data.is_cash);
                    else { el.checked = !turnOn; alert('Could not update the cash setting.'); }
                }).catch(() => { el.checked = !turnOn; alert('Could not update the cash setting.'); });
            }

            // ── COA live account search with highlight ─────────────────
            window.coaLiveFilter = function (raw) {
                const q = raw.trim().toLowerCase();
                const clearBtn    = document.getElementById('coa-search-clear');
                const countLabel  = document.getElementById('coa-match-count');
                clearBtn.style.display = q ? 'inline' : 'none';

                // All section-level headers (type rows) and section rows that are not account rows
                const sectionRows = document.querySelectorAll('#coa-table tr:not(.coa-searchable)');
                // All searchable account rows
                const acctRows = document.querySelectorAll('#coa-table tr.coa-searchable');

                if (!q) {
                    // Restore everything to normal state
                    sectionRows.forEach(r => r.style.display = '');
                    acctRows.forEach(r => {
                        r.style.display = '';
                        // Remove any highlights
                        r.querySelectorAll('.coa-hl').forEach(m => {
                            m.outerHTML = m.textContent;
                        });
                    });
                    countLabel.textContent = '';
                    return;
                }

                const re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                let matches = 0;

                acctRows.forEach(r => {
                    const haystack = (r.dataset.search || '');
                    const show = haystack.includes(q);
                    r.style.display = show ? '' : 'none';
                    if (show) {
                        matches++;
                        // Highlight in code (td:nth-child(1)) and name (td:nth-child(3) a)
                        [r.querySelector('td:first-child'), r.querySelector('td:nth-child(3) a.coa-account-link')].forEach(cell => {
                            if (!cell) return;
                            // Strip previous highlights first
                            cell.querySelectorAll('.coa-hl').forEach(m => { m.outerHTML = m.textContent; });
                            cell.normalize();
                            // Walk text nodes and wrap matches
                            const walker = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT);
                            const nodes = [];
                            let node;
                            while ((node = walker.nextNode())) nodes.push(node);
                            nodes.forEach(tn => {
                                if (!re.test(tn.textContent)) return;
                                re.lastIndex = 0;
                                const span = document.createElement('span');
                                span.innerHTML = tn.textContent.replace(re, '<mark class="coa-hl" style="background:#fef08a;border-radius:2px;padding:0 1px;">$1</mark>');
                                tn.parentNode.replaceChild(span, tn);
                            });
                            re.lastIndex = 0;
                        });
                    } else {
                        // Remove any stale highlights on hidden rows
                        r.querySelectorAll('.coa-hl').forEach(m => { m.outerHTML = m.textContent; });
                    }
                });

                // Hide section headers that have no visible account rows
                sectionRows.forEach(r => {
                    // Keep type-level headers and subsection headers visible only if they have visible children
                    const id = r.dataset.coaId;
                    if (!id) return;
                    const hasVisible = document.querySelector(`.coa-searchable[data-coa-parents~="${id}"][style=""]`) ||
                                       Array.from(document.querySelectorAll(`.coa-searchable[data-coa-parents~="${id}"]`))
                                            .some(c => c.style.display !== 'none');
                    r.style.display = hasVisible ? '' : 'none';
                });

                countLabel.textContent = matches + ' match' + (matches !== 1 ? 'es' : '');
            };
        </script>
    @endpush
@endsection
