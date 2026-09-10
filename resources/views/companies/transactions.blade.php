@extends('layouts.public')

@section('title', $company->registered_name . ' — Transactions')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .tx-table {
            width: 100%;
            border-collapse: collapse;
            font-size:10.5pt;
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        /* Header follows the source document: a #005BF0 band with
           white sentence-case text over a 0.5pt black hairline. */
        .tx-table thead th {
            padding: 4pt 0.875rem;
            text-align: left;
            font-size:10.5pt;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: none;
            color: #ffffff;
            border-bottom: 0.5pt solid #000000;
            background: #005bf0;
            vertical-align: bottom;
        }

        .tx-table thead th.num {
            text-align: right;
        }

        .tx-table tbody tr {
            border-bottom: none;
        }

        .tx-table tbody tr.tx-row {
            border-bottom: 0.4pt solid #d3e2f5;
        }

        .tx-table tbody tr:last-child {
            border-bottom: none;
        }

        .tx-table tbody tr.tx-row:hover {
            background: #f4fafc;
        }

        .tx-table td {
            padding: 0.45rem 0.875rem;
            vertical-align: top;
            color: #1a345b;
            font-size:10.5pt;
        }

        /* The document sets its figures in Century Gothic with
           tabular numerals, never a monospace face. */
        .tx-table td.num {
            text-align: right;
            font-family: inherit;
            font-variant-numeric: tabular-nums;
            font-size:10.5pt;
            white-space: nowrap;
        }

        .tx-table td.muted {
            color: #6f869b;
            font-size:10.5pt;
        }

        .tx-lines-row td {
            padding: 0 0.85rem 0.7rem;
            background: #f4fafc;
        }

        .tx-lines-table {
            width: 100%;
            border-collapse: collapse;
            font-size:10.5pt;
        }

        .tx-lines-table td {
            padding: 0.2rem 0.5rem;
        }

        .tx-lines-table td.num {
            text-align: right;
            font-family: inherit;
            font-variant-numeric: tabular-nums;
        }

        .tx-lines-table tr:first-child td {
            padding-top: 0.35rem;
        }

        .tx-lines-table tr:last-child td {
            padding-bottom: 0.35rem;
        }

        .badge-posted {
            display: inline-block;
            padding: 0.15rem 0.55rem;
            border-radius:0;
            font-size:7pt;
            font-weight: 700;
            letter-spacing:0;
            text-transform: capitalize;
            background: #dcfce7;
            color: #15803d;
        }

        .badge-draft {
            display: inline-block;
            padding: 0.15rem 0.55rem;
            border-radius:0;
            font-size:7pt;
            font-weight: 700;
            letter-spacing:0;
            text-transform: capitalize;
            background: #fef9c3;
            color: #854d0e;
        }

        /* Status controls */
        .badge-reversed {
            display: inline-block;
            padding: 0.15rem 0.55rem;
            border-radius:0;
            font-size:7pt;
            font-weight: 700;
            letter-spacing:0;
            text-transform: capitalize;
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge-reversal {
            display: inline-block;
            padding: 0.15rem 0.55rem;
            border-radius:0;
            font-size:7pt;
            font-weight: 700;
            letter-spacing:0;
            text-transform: capitalize;
            background: #fce7f3;
            color: #9d174d;
        }

        .status-select {
            border: 1px solid #ca8a04;
            border-radius: 0;
            padding: 0.18rem 0.5rem;
            font-size:10pt;
            font-weight: 700;
            font-family: inherit;
            color: #854d0e;
            background: #fef9c3;
            cursor: pointer;
            outline: none;
            text-transform: capitalize;
            letter-spacing:0;
        }

        .badge-correction {
            display: inline-block;
            padding: 0.15rem 0.55rem;
            border-radius:0;
            font-size:7pt;
            font-weight: 700;
            letter-spacing:0;
            text-transform: capitalize;
            background: #dbeafe;
            color: #1d4ed8;
        }

        /* Row action buttons — intangibles-standard outlined pill */
        .btn-reverse, .btn-correct {
            font-size:10pt;
            font-weight: 700;
            background: #fff;
            border-radius: 0;
            padding: 0.18rem 0.5rem;
            cursor: pointer;
            font-family: inherit;
            white-space: nowrap;
            text-transform:none;
            letter-spacing:0;
            transition: background 0.15s, color 0.15s;
        }
        .btn-reverse { color: #b91c1c; border: 1px solid #b91c1c; }
        .btn-reverse:hover { background: #b91c1c; color: #fff; }
        .btn-correct { color: #1d4ed8; border: 1px solid #1d4ed8; }
        .btn-correct:hover { background: #1d4ed8; color: #fff; }

        /* Inline destructive link (delete row, clear, etc.) */
        .tx-del-link {
            background:none; border:none; padding:0; cursor:pointer;
            font-family:inherit; font-size:10pt; font-weight:700;
            color:#dc2626; text-decoration:underline;
            text-transform:none; letter-spacing:0;
        }
        .tx-del-link:hover { color:#991b1b; }

        .tx-trail {
            font-size:7pt;
            color: #6f869b;
            margin-top: 0.2rem;
        }
        .tx-trail a {
            color: #1a345b;
            font-weight: 700;
            text-decoration: none;
            border-bottom: 0.5pt solid #1a345b;
        }
        .tx-trail a:hover { border-bottom-color: transparent; }

        .toggle-lines {
            cursor: pointer;
            font-size:10pt;
            font-weight: 700;
            color: #1a345b;
            text-transform:none;
            letter-spacing:0;
            border-bottom: 0.5pt solid #1a345b;
            white-space: nowrap;
        }
        .toggle-lines:hover { border-bottom-color: transparent; }

        /* Source document inline preview row (collapsed view) */
        .tx-source-row td {
            padding: 0 0.85rem 0.5rem;
            background: #f7fbfd;
            border-bottom: 1px solid #d3e2f5;
        }

        /* Deliberately monospace: this is a verbatim dump of the
           source document, where column alignment carries meaning.
           Every other numeric cell uses Century Gothic tabular
           figures, as the AFS document does. */
        .tx-source-text {
            font-family: "DejaVu Sans Mono", monospace;
            font-size:10.5pt;
            color: #000;
            white-space: pre-wrap;
            word-break: break-word;
            background: #f7fbfd;
            border: 1px solid #d3e2f5;
            border-radius: 0;
            padding: 0.5rem 0.7rem;
            max-height: 120px;
            overflow-y: auto;
            line-height: 1.5;
        }

        .tx-source-label {
            font-size:7pt;
            font-weight: 700;
            letter-spacing:0;
            text-transform:none;
            color: #5a7186;
            margin-bottom: 0.25rem;
        }

        /* Editable line note cells */
        .line-note-cell {
            cursor: text;
            min-width: 120px;
        }
        .line-note-cell:hover {
            background: #f4fafc;
            border-radius: 0;
        }

        .line-note-input {
            width: 100%;
            border: 1px solid #9ec1f5;
            border-radius: 0;
            padding: 0.18rem 0.4rem;
            font-size:10.5pt;
            font-family: inherit;
            outline: none;
            color: #1a345b;
            background: #fff;
        }

        /* Account select on draft lines */
        .line-account-wrap { position: relative; }
        .line-account-search {
            width: 100%;
            border: 1px solid #9ec1f5;
            border-radius: 0;
            padding: 0.18rem 0.4rem;
            font-size:10.5pt;
            font-family: inherit;
            outline: none;
            color: #1a345b;
            background: #fff;
        }
        .line-account-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #9ec1f5;
            border-top: none;
            border-radius: 0;
            max-height: 160px;
            overflow-y: auto;
            z-index: 200;
            box-shadow: 0 4px 12px rgba(26, 52, 91,0.08);
        }
        .line-account-option {
            padding: 0.28rem 0.5rem;
            font-size:10.5pt;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #1a345b;
        }
        .line-account-option:hover,
        .line-account-option.focused { background: #f4fafc; }

        /* Generic account search field (new transaction lines, add-line form) */
        .acct-search {
            position: relative;
        }

        .acct-search-input {
            width: 100%;
            border: 1px solid #9ec1f5;
            border-radius: 0;
            padding: 0.4rem 0.6rem;
            font-size:10.5pt;
            font-family: inherit;
            outline: none;
            color: #1a345b;
            background: #fff;
            box-sizing: border-box;
            transition: border-color 0.15s;
        }

        .acct-search-input:focus {
            border-color: #1a345b;
        }

        .acct-search-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #9ec1f5;
            border-top: none;
            border-radius:0;
            max-height: 180px;
            overflow-y: auto;
            z-index: 1100;
            box-shadow: 0 4px 12px rgba(26, 52, 91,0.08);
        }

        .acct-search-option {
            padding: 0.35rem 0.6rem;
            font-size:8pt;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #1a345b;
        }

        .acct-search-option:hover {
            background: #f4fafc;
        }

        .acct-search-option.acct-search-empty {
            color: #6f869b;
            font-style: italic;
            cursor: default;
        }

        .acct-search-option.acct-search-empty:hover {
            background: transparent;
        }

        /* ── Filter card ── */
        .tx-filter-form {
            background:#fff;
            border:1px solid #9ec1f5;
            border-top:0.5pt solid #000000;
            color:#191919;
            font-size:10.5pt;
            line-height:1.45;
            margin-bottom:1rem;
            position:sticky;
            top:0;
            z-index:20;
            box-shadow:0 1px 2px rgba(16,24,40,0.04), 0 1px 3px rgba(16,24,40,0.03);
        }
        .tx-filter-head {
            display:flex; align-items:center; justify-content:space-between; gap:0.75rem; flex-wrap:wrap;
            cursor:pointer; user-select:none; padding:0.75rem 1.35rem;
        }
        .tx-filter-title {
            font-weight:700; font-size:10pt; text-transform:none; letter-spacing:0; color:#1a345b;
            display:flex; align-items:center; gap:0.5rem;
        }
        .tx-filter-chevron { font-size:7pt; color:#6f869b; transition:transform 0.15s; display:inline-block; }
        .tx-filter-form.open .tx-filter-chevron { transform:rotate(180deg); }
        .tx-filter-collapsible { display:none; padding:0 1.35rem 1.1rem; }
        .tx-filter-form.open .tx-filter-collapsible { display:block; }
        .tx-filter-divider { border:none; border-top:0.5pt solid #d3e2f5; margin:0 0 0.85rem; }

        .tx-filter-form .af-row {
            display:grid; grid-template-columns:repeat(4,1fr); gap:0.55rem 0.85rem;
            align-items:end; margin-bottom:0.65rem;
        }
        .tx-filter-form .af-field.wide { grid-column:span 2; }
        .tx-filter-form .af-field.full { grid-column:span 4; }
        .tx-filter-form .af-field label {
            display:block; font-size:8pt; font-weight:700; letter-spacing:0;
            text-transform:none; color:#5a7186; margin-bottom:0.22rem;
        }
        .tx-filter-form .af-field input,
        .tx-filter-form .af-field select {
            width:100%; border:1px solid #9ec1f5; padding:0.35rem 0.5rem;
            font-size:10.5pt; font-family:inherit; color:#1a345b;
            box-sizing:border-box; background:#fff; height:2rem;
        }
        .tx-filter-form .af-field input:focus,
        .tx-filter-form .af-field select:focus { outline:none; border-color:#1a345b; }
        .tx-filter-form .af-field input::placeholder { color:#6f869b; }
        .tx-filter-form .acct-search .acct-search-input {
            border:1px solid #9ec1f5; padding:0.35rem 0.5rem; font-size:10.5pt; height:2rem; border-radius:0; color:#1a345b;
        }
        .tx-filter-form .acct-search .acct-search-input::placeholder { color:#6f869b; }
        .tx-filter-form .acct-search .acct-search-input:focus { border-color:#1a345b; }
        .tx-filter-form .acct-search-dropdown { border-color:#9ec1f5; }

        .tx-filter-actions {
            display:flex; align-items:center; justify-content:space-between; gap:0.75rem;
            flex-wrap:wrap; padding-top:0.85rem; margin-top:0.35rem; border-top:0.5pt solid #d3e2f5;
        }
        .tx-filter-actions-left { display:flex; align-items:center; gap:0.85rem; }

        .mgmt-btn {
            display:inline-flex; align-items:center; gap:0.4rem;
            background:#fff; border:1px solid #1a345b; color:#1a345b;
            font-size:8pt; font-weight:700; text-transform:none; letter-spacing:0;
            padding:0.4rem 0.95rem; text-decoration:none; cursor:pointer;
            font-family:inherit; transition:background 0.15s, color 0.15s;
            height:2rem; box-sizing:border-box;
        }
        .mgmt-btn:hover { background:#1a345b; color:#fff; }
        .mgmt-btn.primary { background:#1a345b; color:#fff; }
        .mgmt-btn.primary:hover { background:#005bf0; }
        .mgmt-btn.success { border-color:#15803d; color:#15803d; }
        .mgmt-btn.success:hover { background:#15803d; color:#fff; }
        .mgmt-btn.warn { border-color:#b45309; color:#b45309; }
        .mgmt-btn.warn:hover { background:#b45309; color:#fff; }
        .mgmt-btn.danger { border-color:#b91c1c; color:#b91c1c; }
        .mgmt-btn.danger:hover { background:#b91c1c; color:#fff; }
        .mgmt-btn.sm {
            font-size:8pt; padding:0.28rem 0.65rem; height:1.7rem; gap:0.3rem;
        }
        .mgmt-btn.ghost {
            border-style:dashed; color:#6f869b; border-color:#9ec1f5;
        }
        .mgmt-btn.ghost:hover { background:#f4fafc; color:#1a345b; border-color:#1a345b; }

        /* Shared form field used inside expanded rows and the new-transaction modal */
        .tx-af-label {
            display:block; font-size:8pt; font-weight:700; letter-spacing:0;
            text-transform:none; color:#5a7186; margin-bottom:0.22rem;
        }
        .tx-af-input {
            width:100%; border:1px solid #9ec1f5; padding:0.35rem 0.55rem;
            font-size:10.5pt; font-family:inherit; color:#1a345b;
            box-sizing:border-box; background:#fff; height:2rem; border-radius:0;
        }
        .tx-af-input:focus { outline:none; border-color:#1a345b; }
        .tx-af-input::placeholder { color:#6f869b; }
        textarea.tx-af-input { height:auto; padding:0.4rem 0.55rem; line-height:1.45; }

        /* Bulk action bar */
        .tx-bulk-bar {
            display:none; padding:0.85rem 1.1rem;
            background:#f4fafc; border-top:1px solid #9ec1f5; border-bottom:1px solid #9ec1f5;
            align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        }
        .tx-bulk-count {
            font-size:10pt; font-weight:700; letter-spacing:0;
            text-transform:none; color:#1a345b;
        }
        .tx-bulk-actions { display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap; }

        /* Expanded-row mini panel (notes, source document) */
        .tx-mini-panel { display:flex; gap:1rem; flex-wrap:wrap; margin-top:0.7rem; padding-top:0.7rem; border-top:1px dashed #9ec1f5; }
        .tx-mini-col { display:flex; flex-direction:column; gap:0.3rem; }
        .tx-mini-head { display:flex; align-items:center; gap:0.5rem; }
        .tx-saved-flag { display:none; font-size:7pt; color:#15803d; font-weight:700; letter-spacing:0; text-transform:none; }

        /* Add-line inline form inside the expanded row */
        .tx-addline-form {
            display:none; margin-top:0.55rem; padding:0.65rem 0.85rem;
            background:#fff; border:1px solid #9ec1f5;
            gap:0.55rem; flex-wrap:wrap; align-items:end;
        }
        .tx-addline-form.open { display:flex; }
        .tx-addline-field { display:flex; flex-direction:column; gap:0.2rem; }

        .tx-filter-reset {
            font-size:8pt; font-weight:700; text-transform:none; letter-spacing:0;
            color:#5a7186; text-decoration:none; border-bottom:1px solid #5a7186;
        }
        .tx-filter-reset:hover { color:#1a345b; border-bottom-color:#1a345b; }


        @media (max-width:760px) {
            .tx-filter-form .af-row { grid-template-columns:repeat(2,1fr); }
            .tx-filter-form .af-field.wide { grid-column:span 2; }
        }

        /* Live Meilisearch results panel */
        .tx-live-panel {
            position:absolute; top:100%; left:0; right:0; z-index:200;
            background:#fff; border:1px solid #9ec1f5; border-top:none;
            box-shadow:0 6px 24px rgba(26, 52, 91,0.10);
            max-height:420px; overflow-y:auto;
        }
        .tx-live-hit {
            padding:0.6rem 0.85rem; border-bottom:1px solid #eaf8fb;
            cursor:pointer; transition:background 0.1s;
        }
        .tx-live-hit:last-child { border-bottom:none; }
        .tx-live-hit:hover, .tx-live-hit.focused { background:#f4fafc; }
        .tx-live-hit-top {
            display:flex; align-items:baseline; justify-content:space-between; gap:0.5rem;
            margin-bottom:0.2rem;
        }
        .tx-live-hit-desc {
            font-size:10.5pt; font-weight:700; color:#1a345b; flex:1; min-width:0;
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        }
        .tx-live-hit-desc mark { background:#fef08a; color:#1a345b; font-weight:900; padding:0 2px; }
        .tx-live-hit-ref { font-size:10pt; color:#6f869b; white-space:nowrap; font-variant-numeric:tabular-nums; }
        .tx-live-hit-ref mark { background:#fef08a; color:#1a345b; font-weight:700; padding:0 2px; }
        .tx-live-hit-snippet {
            font-size:8pt; color:#5a7186; line-height:1.45; margin-top:0.1rem;
            display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
        }
        .tx-live-hit-snippet mark { background:#fef08a; color:#1a345b; font-weight:700; padding:0 2px; }
        .tx-live-hit-meta {
            display:flex; gap:0.65rem; align-items:center; margin-top:0.25rem;
        }
        .tx-live-hit-date { font-size:8pt; color:#6f869b; }
        .tx-live-hit-amount { font-size:10pt; color:#1a345b; font-weight:700; font-variant-numeric:tabular-nums; }
        .tx-live-hit-status {
            font-size:7pt; font-weight:700; letter-spacing:0; text-transform: capitalize;
            padding:0.1rem 0.4rem;
        }
        .tx-live-hit-status.posted   { background:#dcfce7; color:#15803d; }
        .tx-live-hit-status.draft    { background:#f4fafc; color:#5a7186; }
        .tx-live-hit-status.reversed { background:#fee2e2; color:#b91c1c; }
        .tx-live-hit-status.reversal { background:#fff7ed; color:#b45309; }
        .tx-live-empty {
            padding:0.85rem 0.9rem; font-size:10pt; color:#6f869b; text-align:center;
        }
        .tx-live-loading {
            padding:0.85rem 0.9rem; font-size:8pt; color:#5a7186;
            display:flex; align-items:center; gap:0.5rem;
        }
        .tx-live-spinner {
            width:12px; height:12px; border:2px solid #eaf8fb; border-top-color:#1a345b;
            border-radius:50%; animation:tx-spin 0.6s linear infinite; flex-shrink:0;
        }
        @keyframes tx-spin { to { transform:rotate(360deg); } }
    </style>
@endpush

@section('content')
    <div class="co-wrap">

        @include('companies._topbar')

        {{-- Body --}}
        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                @if (session('success'))
                    <div style="background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;border-radius:0;padding:0.6rem 1rem;font-size:10.5pt;font-weight:600;margin-bottom:1rem;">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div style="background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;border-radius:0;padding:0.6rem 1rem;font-size:10.5pt;margin-bottom:1rem;">
                        <strong style="display:block;margin-bottom:0.25rem;">Could not save the transaction:</strong>
                        <ul style="margin:0;padding-left:1.1rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- ── Filters + Export ── --}}
                @php
                    $hasFilters = $startDate || $endDate || $status || $accountId || $description !== '' || $amount !== '';
                    $selectedFilterAccount = $accountId ? $accounts->firstWhere('id', (int) $accountId) : null;
                @endphp
                <form method="GET" action="{{ route('companies.transactions', $company) }}" class="tx-filter-form {{ $hasFilters ? 'open' : '' }}" id="tx-filter-form"
                    data-has-filters="{{ $hasFilters ? '1' : '0' }}">
                    <div class="tx-filter-head" onclick="(function(){var f=document.getElementById('tx-filter-form');f.classList.toggle('open');localStorage.setItem('tx-filter-open',f.classList.contains('open')?'1':'0');})()">
                        <div class="tx-filter-title">
                            <span class="tx-filter-chevron">▼</span>
                            Filter Transactions
                            @if ($hasFilters)
                                <span style="font-size:8pt;font-weight:700;letter-spacing:0;text-transform:none;color:#1a345b;background:#eaf8fb;padding:0.1rem 0.45rem;">Active</span>
                            @endif
                        </div>
                    </div>

                    <div class="tx-filter-collapsible">
                        <hr class="tx-filter-divider">

                        {{-- Row 1: dates, status, amount --}}
                        <div class="af-row">
                            <div class="af-field">
                                <label>From</label>
                                <input type="date" name="start_date" value="{{ $startDate }}">
                            </div>
                            <div class="af-field">
                                <label>To</label>
                                <input type="date" name="end_date" value="{{ $endDate }}">
                            </div>
                            <div class="af-field">
                                <label>Status</label>
                                <select name="status">
                                    <option value="">All statuses</option>
                                    <option value="draft" @selected($status === 'draft')>Draft</option>
                                    <option value="posted" @selected($status === 'posted')>Posted</option>
                                    <option value="reversed" @selected($status === 'reversed')>Reversed</option>
                                    <option value="reversal" @selected($status === 'reversal')>Reversal entries</option>
                                    <option value="correction" @selected($status === 'correction')>Corrections</option>
                                </select>
                            </div>
                            <div class="af-field" style="position:relative;">
                                <label>Amount (R)</label>
                                <input type="number" step="0.01" min="0" name="amount" id="tx-amount-input" value="{{ $amount }}" placeholder="e.g. 1500.00" autocomplete="off">
                                <div id="tx-amount-panel" class="tx-live-panel" style="display:none;"></div>
                            </div>
                        </div>

                        {{-- Row 2: account + description --}}
                        <div class="af-row">
                            <div class="af-field wide">
                                <label>Account</label>
                                <div class="acct-search">
                                    <input type="text" class="acct-search-input" placeholder="All accounts" autocomplete="off"
                                        value="{{ $selectedFilterAccount ? $selectedFilterAccount->account_code . ' — ' . $selectedFilterAccount->account_name : '' }}">
                                    <input type="hidden" name="account_id" class="acct-search-value" value="{{ $accountId }}">
                                    <div class="acct-search-dropdown" style="display:none;">
                                        <div class="acct-search-option" data-id="" data-label="">All accounts</div>
                                        @foreach ($accounts as $account)
                                            <div class="acct-search-option" data-id="{{ $account->id }}"
                                                data-label="{{ $account->account_code }} — {{ $account->account_name }}">
                                                {{ $account->account_code }} — {{ $account->account_name }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="af-field wide" style="position:relative;">
                                <label>Description</label>
                                <input type="search" name="description" id="tx-desc-input" value="{{ $description }}" placeholder="Description, reference, or line note" autocomplete="off">
                                <div id="tx-live-panel" class="tx-live-panel" style="display:none;"></div>
                            </div>
                        </div>

                        <div class="tx-filter-actions">
                            <div class="tx-filter-actions-left">
                                <button type="submit" class="mgmt-btn primary">Apply</button>
                                @if ($hasFilters)
                                    <a href="{{ route('companies.transactions', $company) }}?reset=1" class="tx-filter-reset">Reset</a>
                                @endif
                            </div>

                            {{-- Export dropdown --}}
                            <div style="position:relative;display:inline-block;" id="tx-export-wrapper">
                                <button type="button" class="reg-btn"
                                    onclick="(function(){var m=document.getElementById('tx-export-menu');m.style.display=m.style.display==='block'?'none':'block';})()">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    Export
                                    <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                                </button>
                                <div id="tx-export-menu" style="display:none;position:absolute;right:0;top:calc(100% + 4px);background:#fff;border:1px solid #9ec1f5;box-shadow:0 4px 16px rgba(26, 52, 91,0.12);min-width:175px;z-index:50;overflow:hidden;">
                                    @php
                                        $txExportParams = array_filter([
                                            'start_date'  => $startDate,
                                            'end_date'    => $endDate,
                                            'status'      => $status,
                                            'account_id'  => $accountId,
                                            'description' => $description,
                                            'amount'      => $amount,
                                        ], fn($v) => $v !== null && $v !== '');
                                    @endphp
                                    @foreach ([['csv', 'CSV (.csv)'], ['xlsx', 'Excel (.xlsx)'], ['ods', 'Spreadsheet (.ods)'], ['pdf', 'PDF (.pdf)']] as [$fmt, $label])
                                        <a href="{{ route('companies.transactions.export', $company) . '?' . http_build_query(array_merge($txExportParams, ['format' => $fmt])) }}"
                                            style="display:flex;align-items:center;gap:0.55rem;padding:0.55rem 0.9rem;font-size:10pt;font-weight:600;color:#1a345b;text-decoration:none;border-bottom:1px solid #eaf8fb;">
                                            <svg width="12" height="12" fill="none" stroke="#005bf0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                            {{ $label }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        (function () {
                            var f = document.getElementById('tx-filter-form');
                            var hasFilters = f.dataset.hasFilters === '1';
                            var saved = localStorage.getItem('tx-filter-open');
                            // Filters active → always open; otherwise restore saved preference
                            if (!hasFilters && saved === '0') f.classList.remove('open');
                            if (!hasFilters && saved === '1') f.classList.add('open');
                        })();

                        document.addEventListener('click', function(e) {
                            var wrapper = document.getElementById('tx-export-wrapper');
                            var menu = document.getElementById('tx-export-menu');
                            if (wrapper && menu && !wrapper.contains(e.target)) {
                                menu.style.display = 'none';
                            }
                        });
                    </script>
                </form>

                <div class="co-card">
                    <div class="co-card-head">
                        <div>
                            <div class="co-section-heading"><p class="co-section-label">Transactions</p><h2>Journal &amp; Ledger
                                Entries</h2></div>
                        </div>
                        <div style="display:flex;align-items:center;gap:1rem;">
                            <span style="font-size:10.5pt;color:#888;">{{ number_format($transactions->total()) }}
                                {{ Str::plural('entry', $transactions->total()) }}</span>
                            <button type="button" onclick="openNewTxModal()"
                                style="display:inline-flex;align-items:center;gap:0.4rem;background:#1a345b;color:#fff;border:none;border-radius:0;padding:0.45rem 0.95rem;font-size:10.5pt;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap;">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"
                                    stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <line x1="12" y1="5" x2="12" y2="19" />
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                </svg>
                                New transaction
                            </button>
                        </div>
                    </div>

                    @if ($transactions->isEmpty())
                        <div class="empty-state">
                            <svg width="36" height="36" fill="none" stroke="#9ec1f5" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"
                                style="margin:0 auto 0.75rem;display:block;">
                                <polyline points="17 1 21 5 17 9" />
                                <path d="M3 11V9a4 4 0 0 1 4-4h14" />
                                <polyline points="7 23 3 19 7 15" />
                                <path d="M21 13v2a4 4 0 0 1-4 4H3" />
                            </svg>
                            <p style="font-weight:700;color:#5a7186;margin:0 0 0.3rem;">No transactions yet</p>
                            <p style="font-size:10.5pt;margin:0;">Transactions and journal entries will appear here once
                                added via the API.</p>
                        </div>
                    @else
                        {{-- Bulk action bar --}}
                        <div id="bulk-delete-container" class="tx-bulk-bar">
                            <div>
                                <span id="selected-count" class="tx-bulk-count">0 selected</span>
                            </div>
                            <div class="tx-bulk-actions">
                                <form method="POST"
                                    action="{{ route('companies.transactions.bulk-post', $company) }}"
                                    style="margin:0;"
                                    onsubmit="return prepareBulkSubmit(this, 'Post the selected DRAFT transactions? Posted entries cannot be edited; they can only be reversed.');">
                                    @csrf
                                    <button type="submit" class="mgmt-btn success">Post Drafts</button>
                                </form>
                                <form method="POST"
                                    action="{{ route('companies.transactions.bulk-reverse', $company) }}"
                                    style="margin:0;"
                                    onsubmit="return prepareBulkSubmit(this, 'Reverse the selected POSTED transactions? A counter-entry will be created for each.');">
                                    @csrf
                                    <button type="submit" class="mgmt-btn warn">Reverse Posted</button>
                                </form>
                                @php
                                    $exportParams = array_filter([
                                        'start_date'  => $startDate,
                                        'end_date'    => $endDate,
                                        'status'      => $status,
                                        'account_id'  => $accountId,
                                        'description' => $description,
                                        'amount'      => $amount,
                                    ], fn($v) => $v !== null && $v !== '');
                                @endphp
                                <button type="button" class="mgmt-btn" onclick="bulkExport()">Export Selected</button>
                                <span id="bulk-export-base"
                                    data-url="{{ route('companies.transactions.export', $company) }}"
                                    data-params="{{ http_build_query($exportParams) }}"
                                    style="display:none;"></span>
                                <form id="bulk-delete-form" method="POST"
                                    action="{{ route('companies.transactions.bulk-delete', $company) }}"
                                    style="margin:0;"
                                    onsubmit="return prepareBulkSubmit(this, 'Delete the selected transactions? This cannot be undone.');">
                                    @csrf
                                    <button type="submit" class="mgmt-btn danger">Delete Selected</button>
                                </form>
                                <button type="button" class="tx-del-link" style="color:#5a7186;" onclick="clearAllSelections()">Cancel</button>
                            </div>
                        </div>

                        <div style="overflow-x:auto;">
                            <table class="tx-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px;text-align:center;padding:0.55rem 0.5rem;">
                                            <input type="checkbox" id="select-all-checkbox"
                                                onchange="toggleSelectAll(this)" style="cursor:pointer;">
                                        </th>
                                        <th>Date</th>
                                        <th>Reference</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th class="num">Debits</th>
                                        <th class="num">Credits</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($transactions as $tx)
                                        @php
                                            $debits = $tx->journalLines->where('type', 'debit')->sum('amount');
                                            $credits = $tx->journalLines->where('type', 'credit')->sum('amount');
                                        @endphp
                                        <tr class="tx-row" id="tx-{{ $tx->id }}">
                                            <td style="width:40px;text-align:center;padding:0.6rem 0.5rem;">
                                                <input type="checkbox" class="transaction-checkbox"
                                                    value="{{ $tx->id }}" onchange="updateBulkDeleteUI()"
                                                    style="cursor:pointer;">
                                            </td>
                                            <td style="white-space:nowrap;">
                                                @if ($tx->isImmutable())
                                                    {{ $tx->transaction_date->format('d M Y') }}
                                                @else
                                                    <span class="tx-date-text" title="Click to edit date"
                                                        data-url="{{ route('companies.transactions.date', [$company, $tx]) }}"
                                                        data-iso="{{ $tx->transaction_date->format('Y-m-d') }}"
                                                        onclick="editTxDate(this)"
                                                        style="cursor:text;border-bottom:1px dashed #d3e2f5;">{{ $tx->transaction_date->format('d M Y') }}</span>
                                                @endif
                                            </td>
                                            <td class="muted">{{ $tx->reference ?? '—' }}</td>
                                            <td>
                                                <span class="tx-desc-text" title="Click to edit description"
                                                    data-url="{{ route('companies.transactions.description', [$company, $tx]) }}"
                                                    onclick="editTxDescription(this)"
                                                    style="cursor:text;border-bottom:1px dashed #d3e2f5;">{{ $tx->description }}</span>
                                                {{-- Audit trail links --}}
                                                @if ($tx->status === 'reversed')
                                                    <div class="tx-trail">
                                                        Reversal: <a href="#tx-{{ $tx->reversal?->id }}" onclick="highlightTx({{ $tx->reversal?->id }})">#{{ $tx->reversal?->id }}</a>
                                                        @if ($tx->correction)
                                                            &middot; Correction: <a href="#tx-{{ $tx->correction->id }}" onclick="highlightTx({{ $tx->correction->id }})">#{{ $tx->correction->id }}</a>
                                                        @endif
                                                    </div>
                                                @elseif ($tx->isReversal())
                                                    <div class="tx-trail">Reverses: <a href="#tx-{{ $tx->reversal_of_id }}" onclick="highlightTx({{ $tx->reversal_of_id }})">#{{ $tx->reversal_of_id }}</a></div>
                                                @elseif ($tx->isCorrection())
                                                    <div class="tx-trail">Corrects: <a href="#tx-{{ $tx->corrects_id }}" onclick="highlightTx({{ $tx->corrects_id }})">#{{ $tx->corrects_id }}</a></div>
                                                @endif
                                            </td>
                                            <td style="white-space:nowrap;">
                                                @if ($tx->status === 'draft')
                                                    {{-- Draft: show posting dropdown regardless of entry type --}}
                                                    <select class="status-select"
                                                        data-tx-id="{{ $tx->id }}"
                                                        data-url="{{ route('companies.transactions.status', [$company, $tx]) }}"
                                                        data-reverse-url="{{ route('companies.transactions.reverse', [$company, $tx]) }}"
                                                        data-csrf="{{ csrf_token() }}"
                                                        onchange="updateTxStatus(this)">
                                                        <option value="draft" selected>Draft</option>
                                                        <option value="posted">Post</option>
                                                    </select>
                                                    @if ($tx->isCorrection())
                                                        <span class="badge-correction" style="margin-left:0.3rem;">Correction</span>
                                                    @endif
                                                @elseif ($tx->status === 'reversed')
                                                    <span class="badge-reversed">Reversed</span>
                                                    @if (!$tx->correction)
                                                        <form method="POST"
                                                            action="{{ route('companies.transactions.correction', [$company, $tx]) }}"
                                                            style="display:inline;margin-left:0.35rem;"
                                                            onsubmit="return false"
                                                            data-confirm-label="Transactions"
                                                            data-confirm-title="Create Correction Draft"
                                                            data-confirm-body="A copy of this transaction will be created that you can edit and then post."
                                                            data-confirm-text="Create Draft">
                                                            @csrf
                                                            <button type="submit" class="btn-correct">Correct</button>
                                                        </form>
                                                    @else
                                                        <a href="#tx-{{ $tx->correction->id }}"
                                                            onclick="highlightTx({{ $tx->correction->id }})"
                                                            class="btn-correct"
                                                            style="margin-left:0.35rem;text-decoration:none;">View Correction</a>
                                                    @endif
                                                @elseif ($tx->isReversal())
                                                    <span class="badge-reversal">Reversal</span>
                                                @elseif ($tx->isCorrection())
                                                    {{-- Posted correction --}}
                                                    <span class="badge-correction">Correction</span>
                                                    <form method="POST"
                                                        action="{{ route('companies.transactions.reverse', [$company, $tx]) }}"
                                                        style="display:inline;margin-left:0.35rem;"
                                                        onsubmit="return false"
                                                        data-confirm-label="Transactions"
                                                        data-confirm-title="Reverse Correction"
                                                        data-confirm-body="A counter-entry will be posted."
                                                        data-confirm-text="Reverse"
                                                        data-confirm-danger="1">
                                                        @csrf
                                                        <button type="submit" class="btn-reverse">Reverse</button>
                                                    </form>
                                                @else
                                                    {{-- Regular posted transaction --}}
                                                    <span class="badge-posted">Posted</span>
                                                    <form method="POST"
                                                        action="{{ route('companies.transactions.reverse', [$company, $tx]) }}"
                                                        style="display:inline;margin-left:0.35rem;"
                                                        onsubmit="return false"
                                                        data-confirm-label="Transactions"
                                                        data-confirm-title="Reverse Transaction"
                                                        data-confirm-body="A counter-entry will be posted and this transaction will be marked as reversed."
                                                        data-confirm-text="Reverse"
                                                        data-confirm-danger="1">
                                                        @csrf
                                                        <button type="submit" class="btn-reverse">Reverse</button>
                                                    </form>
                                                @endif
                                            </td>
                                            <td class="num" id="tx-debits-{{ $tx->id }}">{{ number_format($debits, 2) }}</td>
                                            <td class="num" id="tx-credits-{{ $tx->id }}">{{ number_format($credits, 2) }}</td>
                                            <td style="text-align:right;display:flex;gap:0.65rem;justify-content:flex-end;align-items:center;">
                                                <span class="toggle-lines"
                                                    onclick="toggleLines({{ $tx->id }})">Lines</span>
                                                <form method="POST"
                                                    action="{{ route('companies.transactions.destroy', [$company, $tx]) }}"
                                                    style="display:inline;"
                                                    onsubmit="return false"
                                                    data-confirm-label="Transactions"
                                                    data-confirm-title="Delete Transaction"
                                                    data-confirm-body="This transaction will be permanently deleted."
                                                    data-confirm-text="Delete"
                                                    data-confirm-danger="1">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="tx-del-link"
                                                        onclick="event.stopPropagation();">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                        @if ($tx->source_document || $tx->notes)
                                            <tr class="tx-source-row">
                                                <td colspan="8" style="padding-top:0;">
                                                    <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-start;">
                                                        <div style="flex:1 1 220px;min-width:200px;">
                                                            <div class="tx-source-label">Source Document</div>
                                                            <div class="tx-source-text" id="src-preview-{{ $tx->id }}">{{ $tx->source_document }}</div>
                                                        </div>
                                                        <div style="flex:2 1 320px;min-width:240px;">
                                                            <div class="tx-source-label">Journal Notes</div>
                                                            <div class="tx-source-text" id="notes-preview-{{ $tx->id }}" style="font-family:inherit;">{{ $tx->notes }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                        <tr class="tx-lines-row" id="lines-{{ $tx->id }}" style="display:none;">
                                            <td colspan="8">
                                                <table class="tx-lines-table">
                                                    <thead>
                                                        <tr>
                                                            <td
                                                                style="color:#6f869b;font-size:7pt;font-weight:700;text-transform:none;letter-spacing:0;">
                                                                Code</td>
                                                            <td
                                                                style="color:#6f869b;font-size:7pt;font-weight:700;text-transform:none;letter-spacing:0;">
                                                                Account</td>
                                                            <td
                                                                style="color:#6f869b;font-size:7pt;font-weight:700;text-transform:none;letter-spacing:0;">
                                                                Note</td>
                                                            <td class="num"
                                                                style="color:#6f869b;font-size:7pt;font-weight:700;text-transform:none;letter-spacing:0;">
                                                                Debit</td>
                                                            <td class="num"
                                                                style="color:#6f869b;font-size:7pt;font-weight:700;text-transform:none;letter-spacing:0;">
                                                                Credit</td>
                                                            <td></td>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($tx->journalLines as $line)
                                                            <tr>
                                                                <td style="color:#888;white-space:nowrap;" id="line-code-{{ $line->id }}">{{ $line->account->account_code }}</td>
                                                                <td id="line-account-{{ $line->id }}">
                                                                @if ($tx->status === 'draft')
                                                                    <div class="line-account-wrap">
                                                                        <input class="line-account-search"
                                                                            type="text"
                                                                            value="{{ $line->account->account_code }} — {{ $line->account->account_name }}"
                                                                            placeholder="Search account…"
                                                                            autocomplete="off"
                                                                            data-line-id="{{ $line->id }}"
                                                                            data-url="{{ route('companies.transactions.line-account', [$company, $tx, $line]) }}"
                                                                            oninput="filterAccounts(this)"
                                                                            onfocus="showAccountDropdown(this)"
                                                                            onblur="hideAccountDropdown(this)"
                                                                        />
                                                                        <div class="line-account-dropdown" style="display:none;">
                                                                            @foreach ($postableAccounts as $acct)
                                                                                <div class="line-account-option"
                                                                                    data-id="{{ $acct->id }}"
                                                                                    data-code="{{ $acct->account_code }}"
                                                                                    data-name="{{ $acct->account_name }}"
                                                                                    data-label="{{ $acct->account_code }} — {{ $acct->account_name }}"
                                                                                    onmousedown="selectAccount(event, this)">
                                                                                    {{ $acct->account_code }} — {{ $acct->account_name }}
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    {{ $line->account->account_name }}
                                                                @endif
                                                                </td>
                                                                <td class="line-note-cell"
                                                                    id="note-cell-{{ $line->id }}"
                                                                    data-line-id="{{ $line->id }}"
                                                                    data-url="{{ route('companies.transactions.line-note', [$company, $tx, $line]) }}"
                                                                    onclick="editLineNote(this)"
                                                                    title="Click to edit note">
                                                                    <span class="line-note-text" style="color:#6f869b;">{{ $line->description ?? '' }}</span>
                                                                    @if (!$line->description)
                                                                        <span style="color:#6f869b;font-style:italic;font-size:7pt;">add note…</span>
                                                                    @endif
                                                                </td>
                                                                <td class="num">
                                                                    @if ($line->type === 'debit')
                                                                        <span class="line-amt-text"
                                                                            data-url="{{ route('companies.transactions.line-amount', [$company, $tx, $line]) }}"
                                                                            data-tx-id="{{ $tx->id }}"
                                                                            @if (!$tx->isImmutable()) onclick="editLineAmount(this)" title="Click to edit amount" style="cursor:text;border-bottom:1px dashed #d3e2f5;" @endif>{{ number_format($line->amount, 2) }}</span>
                                                                    @endif
                                                                </td>
                                                                <td class="num">
                                                                    @if ($line->type === 'credit')
                                                                        <span class="line-amt-text"
                                                                            data-url="{{ route('companies.transactions.line-amount', [$company, $tx, $line]) }}"
                                                                            data-tx-id="{{ $tx->id }}"
                                                                            @if (!$tx->isImmutable()) onclick="editLineAmount(this)" title="Click to edit amount" style="cursor:text;border-bottom:1px dashed #d3e2f5;" @endif>{{ number_format($line->amount, 2) }}</span>
                                                                    @endif
                                                                </td>
                                                                <td style="text-align:right;width:24px;">
                                                                    @if (!$tx->isImmutable())
                                                                        <span title="Delete line"
                                                                            onclick="deleteLine('{{ route('companies.transactions.lines.destroy', [$company, $tx, $line]) }}')"
                                                                            style="cursor:pointer;color:#dc2626;font-weight:700;font-size:11.5pt;">&times;</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>

                                                @if (!$tx->isImmutable())
                                                    <div style="margin-top:0.55rem;">
                                                        <button type="button" class="mgmt-btn ghost sm"
                                                            onclick="toggleAddLine({{ $tx->id }})">+ Add line</button>
                                                        <div id="add-line-form-{{ $tx->id }}" class="tx-addline-form">
                                                            <div class="tx-addline-field" style="min-width:240px;flex:1 1 240px;">
                                                                <label class="tx-af-label">Account</label>
                                                                <div class="acct-search">
                                                                    <input type="text" class="acct-search-input" placeholder="Search account…" autocomplete="off">
                                                                    <input type="hidden" id="add-line-account-{{ $tx->id }}" class="acct-search-value">
                                                                    <div class="acct-search-dropdown" style="display:none;">
                                                                        @foreach ($postableAccounts as $acct)
                                                                            <div class="acct-search-option" data-id="{{ $acct->id }}" data-label="{{ $acct->account_code }} — {{ $acct->account_name }}">{{ $acct->account_code }} — {{ $acct->account_name }}</div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="tx-addline-field" style="width:110px;">
                                                                <label class="tx-af-label">Type</label>
                                                                <select id="add-line-type-{{ $tx->id }}" class="tx-af-input">
                                                                    <option value="debit">Debit</option>
                                                                    <option value="credit">Credit</option>
                                                                </select>
                                                            </div>
                                                            <div class="tx-addline-field" style="width:130px;">
                                                                <label class="tx-af-label">Amount</label>
                                                                <input id="add-line-amount-{{ $tx->id }}" type="number" step="0.01" min="0" placeholder="0.00"
                                                                    class="tx-af-input" style="text-align:right;">
                                                            </div>
                                                            <div class="tx-addline-field">
                                                                <button type="button" class="mgmt-btn primary sm"
                                                                    onclick="addLine({{ $tx->id }}, '{{ route('companies.transactions.lines.store', [$company, $tx]) }}')">Add line</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                {{-- Source document + journal notes (notes wider, on the right) --}}
                                                <div class="tx-mini-panel">
                                                    <div class="tx-mini-col" style="flex:1 1 220px;min-width:200px;">
                                                        <div class="tx-mini-head">
                                                            <label class="tx-af-label" style="margin:0;">Source Document</label>
                                                            <span id="src-saved-{{ $tx->id }}" class="tx-saved-flag">&#10003; Saved</span>
                                                        </div>
                                                        <textarea id="src-doc-{{ $tx->id }}" rows="3"
                                                            class="tx-af-input" style="font-variant-numeric:tabular-nums;font-size:10pt;"
                                                            placeholder="Paste or type the source document text (invoice, receipt, statement)...">{{ $tx->source_document }}</textarea>
                                                        <div style="display:flex;gap:0.5rem;">
                                                            <button type="button" class="mgmt-btn primary sm"
                                                                onclick="saveSourceDoc({{ $tx->id }}, '{{ route('companies.transactions.source-document', [$company, $tx]) }}')">Save</button>
                                                            <button type="button" class="tx-del-link"
                                                                onclick="clearSourceDoc({{ $tx->id }}, '{{ route('companies.transactions.source-document', [$company, $tx]) }}')">Clear</button>
                                                        </div>
                                                    </div>

                                                    <div class="tx-mini-col" style="flex:2 1 320px;min-width:240px;">
                                                        <div class="tx-mini-head">
                                                            <label class="tx-af-label" style="margin:0;">Journal Notes</label>
                                                            <span id="notes-saved-{{ $tx->id }}" class="tx-saved-flag">&#10003; Saved</span>
                                                        </div>
                                                        <textarea id="tx-notes-{{ $tx->id }}" rows="3"
                                                            class="tx-af-input"
                                                            placeholder="Add notes about this journal entry...">{{ $tx->notes }}</textarea>
                                                        <div>
                                                            <button type="button" class="mgmt-btn primary sm"
                                                                onclick="saveNotes({{ $tx->id }}, '{{ route('companies.transactions.notes', [$company, $tx]) }}')">Save Notes</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($transactions->hasPages())
                            @php
                                $pagerStyle = 'display:inline-flex;align-items:center;justify-content:center;min-width:2rem;height:2rem;padding:0 0.55rem;border:1px solid #d3e2f5;border-radius:0;font-size:10.5pt;font-weight:700;text-decoration:none;color:#5a7186;background:#fff;';
                                $pagerActive = 'display:inline-flex;align-items:center;justify-content:center;min-width:2rem;height:2rem;padding:0 0.55rem;border:1px solid #005bf0;border-radius:0;font-size:10.5pt;font-weight:700;color:#fff;background:#005bf0;';
                                $pagerDisabled = 'display:inline-flex;align-items:center;justify-content:center;min-width:2rem;height:2rem;padding:0 0.55rem;border:1px solid #f4fafc;border-radius:0;font-size:10.5pt;font-weight:700;color:#d3e2f5;background:#f7fbfd;cursor:default;';
                            @endphp
                            <nav style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:0.9rem 0.25rem 0.25rem;">
                                <span style="font-size:10pt;color:#6f869b;">
                                    Showing {{ number_format($transactions->firstItem()) }}–{{ number_format($transactions->lastItem()) }}
                                    of {{ number_format($transactions->total()) }}
                                </span>
                                <div style="display:flex;gap:0.35rem;align-items:center;flex-wrap:wrap;">
                                    @if ($transactions->onFirstPage())
                                        <span style="{{ $pagerDisabled }}">&larr; Prev</span>
                                    @else
                                        <a href="{{ $transactions->previousPageUrl() }}" style="{{ $pagerStyle }}" rel="prev">&larr; Prev</a>
                                    @endif

                                    @foreach ($transactions->getUrlRange(max(1, $transactions->currentPage() - 2), min($transactions->lastPage(), $transactions->currentPage() + 2)) as $page => $url)
                                        @if ($page === $transactions->currentPage())
                                            <span style="{{ $pagerActive }}">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}" style="{{ $pagerStyle }}">{{ $page }}</a>
                                        @endif
                                    @endforeach

                                    @if ($transactions->hasMorePages())
                                        <a href="{{ $transactions->nextPageUrl() }}" style="{{ $pagerStyle }}" rel="next">Next &rarr;</a>
                                    @else
                                        <span style="{{ $pagerDisabled }}">Next &rarr;</span>
                                    @endif
                                </div>
                            </nav>
                        @endif
                    @endif
                </div>

            </main>
        </div>
    </div>

    {{-- ── New transaction modal ── --}}
    @php
        $oldLines = old('lines');
        if (! is_array($oldLines) || count($oldLines) < 2) {
            $oldLines = [[], []];
        }
        $renderAccountSearch = function ($name, $selected) use ($postableAccounts) {
            $selectedAccount = $selected ? $postableAccounts->firstWhere('id', (int) $selected) : null;
            $label = $selectedAccount ? e($selectedAccount->account_code . ' — ' . $selectedAccount->account_name) : '';

            $html = '<div class="acct-search">';
            $html .= '<input type="text" class="acct-search-input" placeholder="Search account…" autocomplete="off" value="' . $label . '">';
            $html .= '<input type="hidden" class="acct-search-value" name="' . e($name) . '" value="' . e($selected ?? '') . '">';
            $html .= '<div class="acct-search-dropdown" style="display:none;">';
            foreach ($postableAccounts as $a) {
                $html .= '<div class="acct-search-option" data-id="' . $a->id . '" data-label="' . e($a->account_code . ' — ' . $a->account_name) . '">' . e($a->account_code . ' — ' . $a->account_name) . '</div>';
            }
            $html .= '</div></div>';

            return $html;
        };
    @endphp
    <div id="tx-modal-overlay"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:1000;padding:2rem 1rem;overflow-y:auto;">
        <div role="dialog" aria-modal="true" aria-label="New transaction"
            style="max-width:860px;margin:0 auto;background:#fff;border:1px solid #9ec1f5;border-top:2pt solid #1a345b;box-shadow:0 20px 60px rgba(0,0,0,0.25);overflow:hidden;color:#191919;font-size:10.5pt;line-height:1.45;">

            <div style="display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.5rem;border-bottom:1pt solid #9ec1f5;">
                <p style="font-size:7pt;font-weight:800;letter-spacing:0.1em;margin:0;text-transform:none;color:#1a345b;">New Transaction</p>
                <button type="button" onclick="closeNewTxModal()" aria-label="Close"
                    style="background:none;border:none;font-size:13pt;line-height:1;color:#000;cursor:pointer;">&times;</button>
            </div>

            <form method="POST" action="{{ route('companies.transactions.store', $company) }}" id="tx-create-form">
                @csrf

                <div style="padding:1.25rem 1.5rem;">

                    {{-- Header fields --}}
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.55rem 0.85rem;margin-bottom:0.65rem;">
                        <div>
                            <label class="tx-af-label">Date</label>
                            <input type="date" name="transaction_date" required
                                value="{{ old('transaction_date', now()->format('Y-m-d')) }}" class="tx-af-input">
                        </div>
                        <div>
                            <label class="tx-af-label">Reference <span style="color:#6f869b;font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                            <input type="text" name="reference" maxlength="255" value="{{ old('reference') }}"
                                placeholder="e.g. INV-001" class="tx-af-input">
                        </div>
                        <div>
                            <label class="tx-af-label">Status</label>
                            <select name="status" class="tx-af-input">
                                <option value="draft" @selected(old('status', 'draft') === 'draft')>Draft</option>
                                <option value="posted" @selected(old('status') === 'posted')>Posted</option>
                            </select>
                        </div>
                        <div></div>
                        <div style="grid-column:span 4;">
                            <label class="tx-af-label">Description</label>
                            <input type="text" name="description" required maxlength="255" value="{{ old('description') }}"
                                placeholder="What is this transaction for?" class="tx-af-input">
                        </div>
                    </div>

                    {{-- Source document + journal notes (notes wider, on the right) --}}
                    <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-start;margin-bottom:0.65rem;">
                        <div style="flex:1 1 220px;min-width:200px;">
                            <label class="tx-af-label">Source Document <span style="color:#6f869b;font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                            <textarea name="source_document" rows="3" maxlength="10000"
                                class="tx-af-input" style="font-variant-numeric:tabular-nums;font-size:10pt;"
                                placeholder="Paste invoice / receipt / statement text…">{{ old('source_document') }}</textarea>
                        </div>
                        <div style="flex:2 1 320px;min-width:240px;">
                            <label class="tx-af-label">Journal Notes <span style="color:#6f869b;font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                            <textarea name="notes" rows="3" maxlength="5000"
                                class="tx-af-input"
                                placeholder="Anything you want to remember about this entry…">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    {{-- Journal lines --}}
                    <div style="margin-top:1.25rem;margin-bottom:0.5rem;">
                        <p class="tx-af-label" style="margin:0 0 0.15rem;font-size:7pt;letter-spacing:0.1em;color:#1a345b;">Journal Lines</p>
                        <span style="font-size:7pt;color:#6f869b;">Double-entry: total debits must equal total credits.</span>
                    </div>
                    <table style="width:100%;border-collapse:collapse;font-size:10.5pt;">
                        <thead>
                            <tr>
                                <th style="text-align:left;font-size:7pt;font-weight:700;color:#5a7186;text-transform:none;letter-spacing:0;padding:0.35rem 0.4rem;border-bottom:1pt solid #9ec1f5;">Account</th>
                                <th style="text-align:left;font-size:7pt;font-weight:700;color:#5a7186;text-transform:none;letter-spacing:0;padding:0.35rem 0.4rem;width:110px;border-bottom:1px solid #d3e2f5;">Type</th>
                                <th style="text-align:right;font-size:7pt;font-weight:700;color:#5a7186;text-transform:none;letter-spacing:0;padding:0.35rem 0.4rem;width:130px;border-bottom:1px solid #d3e2f5;">Amount</th>
                                <th style="text-align:left;font-size:7pt;font-weight:700;color:#5a7186;text-transform:none;letter-spacing:0;padding:0.35rem 0.4rem;border-bottom:1pt solid #9ec1f5;">Line note</th>
                                <th style="width:32px;border-bottom:1pt solid #9ec1f5;"></th>
                            </tr>
                        </thead>
                        <tbody id="tx-lines-body">
                            @foreach ($oldLines as $i => $line)
                                <tr class="tx-line-row">
                                    <td style="padding:0.3rem 0.4rem;">
                                        {!! $renderAccountSearch('lines[' . $i . '][chart_of_account_id]', $line['chart_of_account_id'] ?? null) !!}
                                    </td>
                                    <td style="padding:0.3rem 0.4rem;">
                                        <select name="lines[{{ $i }}][type]" class="tx-line-type tx-af-input" onchange="recalcTxBalance()">
                                            <option value="debit" @selected(($line['type'] ?? 'debit') === 'debit')>Debit</option>
                                            <option value="credit" @selected(($line['type'] ?? '') === 'credit')>Credit</option>
                                        </select>
                                    </td>
                                    <td style="padding:0.3rem 0.4rem;">
                                        <input type="number" step="0.01" min="0.01" name="lines[{{ $i }}][amount]"
                                            class="tx-line-amount tx-af-input" oninput="recalcTxBalance()" value="{{ $line['amount'] ?? '' }}"
                                            placeholder="0.00" style="text-align:right;font-variant-numeric:tabular-nums;">
                                    </td>
                                    <td style="padding:0.3rem 0.4rem;">
                                        <input type="text" name="lines[{{ $i }}][description]" maxlength="255"
                                            value="{{ $line['description'] ?? '' }}" class="tx-af-input">
                                    </td>
                                    <td style="padding:0.3rem 0.4rem;text-align:center;">
                                        <button type="button" onclick="removeTxLine(this)" title="Remove line"
                                            style="background:none;border:none;color:#dc2626;font-size:13pt;font-weight:700;cursor:pointer;line-height:1;">&times;</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" style="padding:0.55rem 0.4rem;">
                                    <button type="button" class="mgmt-btn ghost sm" onclick="addTxLine()">+ Add line</button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                    {{-- Balance indicator --}}
                    <div style="display:flex;gap:1.25rem;justify-content:flex-end;align-items:center;margin-top:0.6rem;font-size:10.5pt;">
                        <span style="color:#5a7186;">Debits: <strong id="tx-total-debits" style="font-variant-numeric:tabular-nums;color:#1a345b;">0.00</strong></span>
                        <span style="color:#5a7186;">Credits: <strong id="tx-total-credits" style="font-variant-numeric:tabular-nums;color:#1a345b;">0.00</strong></span>
                        <span id="tx-balance-badge"
                            style="display:inline-block;font-weight:700;padding:0.18rem 0.55rem;border-radius:0;background:#fff;color:#92400e;border:1px solid #92400e;font-size:7pt;letter-spacing:0;text-transform:none;">Unbalanced</span>
                    </div>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-top:0.5pt solid #d3e2f5;">
                    <a href="javascript:void(0)" onclick="closeNewTxModal()" style="font-size:10.5pt;color:#5a7186;text-decoration:none;">Cancel</a>
                    <button type="submit" id="tx-save-btn" style="background:#1a345b;color:#fff;border:none;padding:0.55rem 1.5rem;font-size:10.5pt;font-weight:700;cursor:pointer;font-family:inherit;">Save Transaction</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Template for a new (blank) journal line --}}
    <template id="tx-line-template">
        <tr class="tx-line-row">
            <td style="padding:0.3rem 0.4rem;">
                {!! $renderAccountSearch('lines[__INDEX__][chart_of_account_id]', null) !!}
            </td>
            <td style="padding:0.3rem 0.4rem;">
                <select name="lines[__INDEX__][type]" class="tx-line-type tx-af-input" onchange="recalcTxBalance()">
                    <option value="debit">Debit</option>
                    <option value="credit">Credit</option>
                </select>
            </td>
            <td style="padding:0.3rem 0.4rem;">
                <input type="number" step="0.01" min="0.01" name="lines[__INDEX__][amount]"
                    class="tx-line-amount tx-af-input" oninput="recalcTxBalance()" placeholder="0.00"
                    style="text-align:right;font-variant-numeric:tabular-nums;">
            </td>
            <td style="padding:0.3rem 0.4rem;">
                <input type="text" name="lines[__INDEX__][description]" maxlength="255" class="tx-af-input">
            </td>
            <td style="padding:0.3rem 0.4rem;text-align:center;">
                <button type="button" onclick="removeTxLine(this)" title="Remove line"
                    style="background:none;border:none;color:#dc2626;font-size:13pt;font-weight:700;cursor:pointer;line-height:1;">&times;</button>
            </td>
        </tr>
    </template>

    <script>
        let txLineIndex = {{ count($oldLines) }};

        function openNewTxModal() {
            document.getElementById('tx-modal-overlay').style.display = 'block';
            document.body.style.overflow = 'hidden';
            recalcTxBalance();
        }

        function closeNewTxModal() {
            document.getElementById('tx-modal-overlay').style.display = 'none';
            document.body.style.overflow = '';
        }

        function addTxLine() {
            const tpl = document.getElementById('tx-line-template').innerHTML.replace(/__INDEX__/g, txLineIndex++);
            document.getElementById('tx-lines-body').insertAdjacentHTML('beforeend', tpl);
            recalcTxBalance();
        }

        function removeTxLine(btn) {
            const rows = document.querySelectorAll('#tx-lines-body .tx-line-row');
            if (rows.length <= 2) {
                alert('A transaction needs at least two journal lines.');
                return;
            }
            btn.closest('tr').remove();
            recalcTxBalance();
        }

        function recalcTxBalance() {
            let debits = 0, credits = 0;
            document.querySelectorAll('#tx-lines-body .tx-line-row').forEach(row => {
                const amount = parseFloat(row.querySelector('.tx-line-amount').value) || 0;
                const type = row.querySelector('.tx-line-type').value;
                if (type === 'debit') debits += amount; else credits += amount;
            });
            document.getElementById('tx-total-debits').textContent = debits.toFixed(2);
            document.getElementById('tx-total-credits').textContent = credits.toFixed(2);

            const badge = document.getElementById('tx-balance-badge');
            const balanced = Math.abs(debits - credits) < 0.005 && debits > 0;
            if (balanced) {
                badge.textContent = 'Balanced';
                badge.style.background = '#fff';
                badge.style.color = '#15803d';
                badge.style.borderColor = '#15803d';
            } else {
                const diff = (debits - credits).toFixed(2);
                badge.textContent = debits === 0 && credits === 0 ? 'Unbalanced' : 'Off by ' + Math.abs(diff);
                badge.style.background = '#fff';
                badge.style.color = '#92400e';
                badge.style.borderColor = '#92400e';
            }
        }

        document.getElementById('tx-create-form').addEventListener('submit', function (e) {
            const debits = parseFloat(document.getElementById('tx-total-debits').textContent) || 0;
            const credits = parseFloat(document.getElementById('tx-total-credits').textContent) || 0;
            if (Math.abs(debits - credits) >= 0.005 || debits === 0) {
                e.preventDefault();
                alert('Debits and credits must balance and be greater than zero before saving.');
            }
        });

        // Close on overlay click / Escape
        document.getElementById('tx-modal-overlay').addEventListener('click', function (e) {
            if (e.target === this) closeNewTxModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeNewTxModal();
        });

        // Re-open automatically if the server rejected the submission.
        @if ($errors->any())
            openNewTxModal();
        @endif
    </script>

    <script>
        /* ── Generic chart-of-accounts search field (.acct-search) ── */
        function acctSearchFilter(input) {
            const wrap = input.closest('.acct-search');
            const dd = wrap.querySelector('.acct-search-dropdown');
            const q = input.value.toLowerCase();
            let anyVisible = false;
            dd.querySelectorAll('.acct-search-option:not(.acct-search-empty)').forEach(opt => {
                const match = opt.dataset.label.toLowerCase().includes(q);
                opt.style.display = match ? '' : 'none';
                if (match) anyVisible = true;
            });
            let empty = dd.querySelector('.acct-search-empty');
            if (!anyVisible) {
                if (!empty) {
                    empty = document.createElement('div');
                    empty.className = 'acct-search-option acct-search-empty';
                    empty.textContent = 'No matching accounts';
                    dd.appendChild(empty);
                }
            } else if (empty) {
                empty.remove();
            }
            dd.style.display = 'block';
        }

        function acctSearchSelect(option) {
            const wrap = option.closest('.acct-search');
            const input = wrap.querySelector('.acct-search-input');
            const hidden = wrap.querySelector('.acct-search-value');
            input.value = option.dataset.label;
            hidden.value = option.dataset.id;
            wrap.querySelector('.acct-search-dropdown').style.display = 'none';
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }

        document.addEventListener('focus', e => {
            if (e.target.classList && e.target.classList.contains('acct-search-input')) {
                acctSearchFilter(e.target);
            }
        }, true);

        document.addEventListener('input', e => {
            if (e.target.classList && e.target.classList.contains('acct-search-input')) {
                const wrap = e.target.closest('.acct-search');
                wrap.querySelector('.acct-search-value').value = '';
                acctSearchFilter(e.target);
            }
        });

        document.addEventListener('blur', e => {
            if (e.target.classList && e.target.classList.contains('acct-search-input')) {
                const input = e.target;
                setTimeout(() => {
                    const wrap = input.closest('.acct-search');
                    const dd = wrap.querySelector('.acct-search-dropdown');
                    dd.style.display = 'none';
                    // Revert to the last confirmed selection if the field was left without picking an option
                    const hidden = wrap.querySelector('.acct-search-value');
                    const selected = dd.querySelector('.acct-search-option[data-id="' + hidden.value + '"]');
                    input.value = hidden.value && selected ? selected.dataset.label : '';
                }, 150);
            }
        }, true);

        document.addEventListener('mousedown', e => {
            const option = e.target.closest('.acct-search-option:not(.acct-search-empty)');
            if (option) acctSearchSelect(option);
        });

        function toggleLines(id) {
            const row = document.getElementById('lines-' + id);
            row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
        }

        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.transaction-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
            updateBulkDeleteUI();
        }

        function getSelectedIds() {
            return Array.from(document.querySelectorAll('.transaction-checkbox'))
                .filter(cb => cb.checked)
                .map(cb => cb.value);
        }

        function updateBulkDeleteUI() {
            const checkboxes = document.querySelectorAll('.transaction-checkbox');
            const selected = Array.from(checkboxes).filter(cb => cb.checked);
            const container = document.getElementById('bulk-delete-container');
            const countSpan = document.getElementById('selected-count');

            if (selected.length > 0) {
                container.style.display = 'flex';
                countSpan.textContent = `${selected.length} selected`;

                const selectAllCheckbox = document.getElementById('select-all-checkbox');
                selectAllCheckbox.checked = selected.length === checkboxes.length;
                selectAllCheckbox.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
            } else {
                container.style.display = 'none';
                document.getElementById('select-all-checkbox').checked = false;
                document.getElementById('select-all-checkbox').indeterminate = false;
            }
        }

        // Inject a hidden input per selected id into the submitting form so the
        // server receives transaction_ids as an array.
        function prepareBulkSubmit(form, confirmMessage) {
            const ids = getSelectedIds();
            if (ids.length === 0) {
                alert('No transactions selected.');
                return false;
            }
            if (confirmMessage) {
                window.showConfirmModal({
                    label: 'Transactions',
                    title: confirmMessage,
                    body: ids.length + ' transaction' + (ids.length > 1 ? 's' : '') + ' selected.',
                    confirmText: 'Proceed',
                    danger: confirmMessage.toLowerCase().includes('delet') || confirmMessage.toLowerCase().includes('revers'),
                    onConfirm: () => {
                        form.querySelectorAll('input[name="transaction_ids[]"]').forEach(el => el.remove());
                        ids.forEach(id => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'transaction_ids[]';
                            input.value = id;
                            form.appendChild(input);
                        });
                        form.submit();
                    },
                });
                return false;
            }

            form.querySelectorAll('input[name="transaction_ids[]"]').forEach(el => el.remove());
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'transaction_ids[]';
                input.value = id;
                form.appendChild(input);
            });
            return true;
        }

        function bulkExport() {
            const ids = getSelectedIds();
            if (ids.length === 0) { alert('No transactions selected.'); return; }
            const base = document.getElementById('bulk-export-base');
            const fmt = prompt('Export format: csv, xlsx, ods, or pdf', 'xlsx');
            if (!fmt) return;
            const params = new URLSearchParams(base.dataset.params || '');
            ids.forEach(id => params.append('transaction_ids[]', id));
            params.set('format', fmt);
            window.location.href = base.dataset.url + '?' + params.toString();
        }

        function clearAllSelections() {
            document.querySelectorAll('.transaction-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('select-all-checkbox').checked = false;
            updateBulkDeleteUI();
        }

        const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function updateTxStatus(select) {
            const newStatus = select.value;
            if (newStatus === 'draft') return;
            window.showConfirmModal({
                label: 'Transactions',
                title: 'Post Transaction',
                body: 'Once posted this transaction cannot be changed back to draft without a reversal.',
                confirmText: 'Post',
                danger: false,
                onConfirm: () => doPostTx(select),
            });
            select.value = 'draft';
        }
        function doPostTx(select) {
            const reverseUrl  = select.dataset.reverseUrl;
            const csrfToken   = select.dataset.csrf;
            const statusCell  = select.closest('td');
            fetch(select.dataset.url, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ status: 'posted' }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Swap dropdown for Posted badge + Reverse button in-place
                    statusCell.innerHTML =
                        '<span class="badge-posted">Posted</span>' +
                        '<form method="POST" action="' + reverseUrl + '" style="display:inline;margin-left:0.35rem;"' +
                        ' onsubmit="return false" data-confirm-label="Transactions" data-confirm-title="Reverse Transaction" data-confirm-body="A counter-entry will be posted and this transaction will be marked as reversed." data-confirm-text="Reverse" data-confirm-danger="1">' +
                        '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                        '<input type="hidden" name="_method" value="POST">' +
                        '<button type="submit" class="btn-reverse">Reverse</button>' +
                        '</form>';
                    // Show toast
                    const t = document.createElement('div');
                    t.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;padding:0.75rem 1.25rem;border-radius:6px;font-size:10.5pt;font-weight:600;color:#fff;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:opacity 0.4s;background:#065f46';
                    t.textContent = 'Transaction posted';
                    document.body.appendChild(t);
                    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 4000);
                } else {
                    alert(data.message ?? 'Could not update status.');
                    select.value = 'draft';
                }
            })
            .catch(() => { alert('Request failed.'); select.value = 'draft'; });
        }

        function patchField(url, body, badgeId) {
            fetch(url, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify(body),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && badgeId) {
                    const badge = document.getElementById(badgeId);
                    if (badge) { badge.style.display = 'inline'; setTimeout(() => badge.style.display = 'none', 2000); }
                }
            });
        }

        function saveSourceDoc(txId, url) {
            const text = document.getElementById('src-doc-' + txId).value;
            patchField(url, { source_document: text }, 'src-saved-' + txId);
            // Update the always-visible preview row
            const preview = document.getElementById('src-preview-' + txId);
            if (preview) preview.textContent = text;
        }

        function clearSourceDoc(txId, url) {
            document.getElementById('src-doc-' + txId).value = '';
            saveSourceDoc(txId, url);
        }

        function saveNotes(txId, url) {
            const text = document.getElementById('tx-notes-' + txId).value;
            patchField(url, { notes: text }, 'notes-saved-' + txId);
            const preview = document.getElementById('notes-preview-' + txId);
            if (preview) preview.textContent = text;
        }

        function showAccountDropdown(input) {
            input.parentElement.querySelector('.line-account-dropdown').style.display = 'block';
            filterAccounts(input);
        }

        function hideAccountDropdown(input) {
            // Delay so mousedown on option fires first
            setTimeout(() => {
                const dd = input.parentElement.querySelector('.line-account-dropdown');
                if (dd) dd.style.display = 'none';
            }, 150);
        }

        function filterAccounts(input) {
            const q = input.value.toLowerCase();
            const dd = input.parentElement.querySelector('.line-account-dropdown');
            if (!dd) return;
            dd.style.display = 'block';
            let anyVisible = false;
            dd.querySelectorAll('.line-account-option').forEach(opt => {
                const match = opt.dataset.label.toLowerCase().includes(q);
                opt.style.display = match ? '' : 'none';
                if (match) anyVisible = true;
            });
        }

        function selectAccount(event, option) {
            event.preventDefault();
            const wrap = option.closest('.line-account-wrap');
            const input = wrap.querySelector('.line-account-search');
            const lineId = input.dataset.lineId;
            const url = input.dataset.url;
            const accountId = option.dataset.id;
            const label = option.dataset.label;
            const code = option.dataset.code;

            input.value = label;
            wrap.querySelector('.line-account-dropdown').style.display = 'none';

            fetch(url, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ chart_of_account_id: accountId }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const codeCell = document.getElementById('line-code-' + lineId);
                    if (codeCell) codeCell.textContent = data.account_code;
                } else {
                    alert(data.message ?? 'Could not update account.');
                }
            });
        }

        function highlightTx(id) {
            const row = document.getElementById('tx-' + id);
            if (!row) return;
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.style.transition = 'background 0.2s';
            row.style.background = '#eaf8fb';
            setTimeout(() => { row.style.background = ''; }, 1800);
        }

        function editLineNote(cell) {
            if (cell.querySelector('.line-note-input')) return; // already editing
            const url = cell.dataset.url;
            const currentText = cell.querySelector('.line-note-text')?.textContent.trim() ?? '';
            cell.innerHTML = '<input class="line-note-input" type="text" value="' + currentText.replace(/"/g, '&quot;') + '" placeholder="Add note…" maxlength="500" />';
            const input = cell.querySelector('.line-note-input');
            input.focus();
            input.select();

            function commit() {
                const newVal = input.value.trim();
                patchField(url, { description: newVal || null }, null);
                cell.innerHTML = newVal
                    ? '<span class="line-note-text" style="color:#6f869b;">' + newVal + '</span>'
                    : '<span class="line-note-text" style="color:#6f869b;"></span><span style="color:#6f869b;font-style:italic;font-size:7pt;">add note…</span>';
            }

            input.addEventListener('blur', commit);
            input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); input.blur(); } if (e.key === 'Escape') { input.blur(); } });
        }

        function editTxDescription(span) {
            const url = span.dataset.url;
            const original = span.textContent.trim();

            const input = document.createElement('input');
            input.type = 'text';
            input.value = original;
            input.maxLength = 255;
            input.style.cssText = 'font:inherit;width:100%;min-width:180px;border:1.5px solid #005bf0;border-radius:0;padding:0.2rem 0.4rem;box-sizing:border-box;';
            span.replaceWith(input);
            input.focus();
            input.select();

            let done = false;
            function rebuild(text) {
                const newSpan = document.createElement('span');
                newSpan.className = 'tx-desc-text';
                newSpan.title = 'Click to edit description';
                newSpan.dataset.url = url;
                newSpan.setAttribute('onclick', 'editTxDescription(this)');
                newSpan.style.cssText = 'cursor:text;border-bottom:1px dashed #d3e2f5;';
                newSpan.textContent = text;
                input.replaceWith(newSpan);
                return newSpan;
            }

            function commit(save) {
                if (done) return;
                done = true;
                const newVal = input.value.trim();

                // Description is required: empty or unchanged values just revert without saving.
                if (!save || newVal === '' || newVal === original) {
                    rebuild(original);
                    return;
                }

                const newSpan = rebuild(newVal);
                fetch(url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ description: newVal }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        newSpan.style.transition = 'background 0.2s';
                        newSpan.style.background = '#dcfce7';
                        setTimeout(() => { newSpan.style.background = ''; }, 1200);
                    } else {
                        alert(data.message ?? 'Could not update description.');
                        newSpan.textContent = original;
                    }
                })
                .catch(() => {
                    alert('Could not update description.');
                    newSpan.textContent = original;
                });
            }

            input.addEventListener('blur', () => commit(true));
            input.addEventListener('keydown', e => {
                if (e.key === 'Enter') { e.preventDefault(); commit(true); }
                if (e.key === 'Escape') { e.preventDefault(); commit(false); }
            });
        }

        function flashGreen(el) {
            el.style.transition = 'background 0.2s';
            el.style.background = '#dcfce7';
            setTimeout(() => { el.style.background = ''; }, 1200);
        }

        function editTxDate(span) {
            const url = span.dataset.url;
            const iso = span.dataset.iso;
            const original = span.textContent.trim();

            const input = document.createElement('input');
            input.type = 'date';
            input.value = iso;
            input.style.cssText = 'font:inherit;border:1.5px solid #005bf0;border-radius:0;padding:0.15rem 0.3rem;';
            span.replaceWith(input);
            input.focus();

            let done = false;
            function rebuild(text, isoVal) {
                const s = document.createElement('span');
                s.className = 'tx-date-text';
                s.title = 'Click to edit date';
                s.dataset.url = url;
                s.dataset.iso = isoVal;
                s.setAttribute('onclick', 'editTxDate(this)');
                s.style.cssText = 'cursor:text;border-bottom:1px dashed #d3e2f5;';
                s.textContent = text;
                input.replaceWith(s);
                return s;
            }
            function commit(save) {
                if (done) return;
                done = true;
                const val = input.value;
                if (!save || !val || val === iso) { rebuild(original, iso); return; }
                fetch(url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ transaction_date: val }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { flashGreen(rebuild(data.date, val)); }
                    else { alert(data.message ?? 'Could not update date.'); rebuild(original, iso); }
                })
                .catch(() => { alert('Could not update date.'); rebuild(original, iso); });
            }
            input.addEventListener('blur', () => commit(true));
            input.addEventListener('keydown', e => {
                if (e.key === 'Enter') { e.preventDefault(); commit(true); }
                if (e.key === 'Escape') { e.preventDefault(); commit(false); }
            });
        }

        function updateTxTotals(txId, data) {
            const dc = document.getElementById('tx-debits-' + txId);
            const cc = document.getElementById('tx-credits-' + txId);
            if (dc) dc.textContent = data.debits;
            if (cc) cc.textContent = data.credits;
            const color = data.balanced ? '' : '#dc2626';
            if (dc) dc.style.color = color;
            if (cc) cc.style.color = color;
        }

        function editLineAmount(span) {
            const url = span.dataset.url;
            const txId = span.dataset.txId;
            const original = span.textContent.trim();

            const input = document.createElement('input');
            input.type = 'number';
            input.step = '0.01';
            input.min = '0';
            input.value = original.replace(/,/g, '');
            input.style.cssText = 'font:inherit;width:90px;text-align:right;border:1.5px solid #005bf0;border-radius:0;padding:0.1rem 0.3rem;';
            span.replaceWith(input);
            input.focus();
            input.select();

            let done = false;
            function rebuild(text) {
                const s = document.createElement('span');
                s.className = 'line-amt-text';
                s.dataset.url = url;
                s.dataset.txId = txId;
                s.title = 'Click to edit amount';
                s.setAttribute('onclick', 'editLineAmount(this)');
                s.style.cssText = 'cursor:text;border-bottom:1px dashed #d3e2f5;';
                s.textContent = text;
                input.replaceWith(s);
                return s;
            }
            function commit(save) {
                if (done) return;
                done = true;
                const val = input.value.trim();
                if (!save || val === '' || parseFloat(val) <= 0 || val === original.replace(/,/g, '')) {
                    rebuild(original);
                    return;
                }
                fetch(url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ amount: val }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { flashGreen(rebuild(data.amount)); updateTxTotals(txId, data); }
                    else { alert(data.message ?? 'Could not update amount.'); rebuild(original); }
                })
                .catch(() => { alert('Could not update amount.'); rebuild(original); });
            }
            input.addEventListener('blur', () => commit(true));
            input.addEventListener('keydown', e => {
                if (e.key === 'Enter') { e.preventDefault(); commit(true); }
                if (e.key === 'Escape') { e.preventDefault(); commit(false); }
            });
        }

        function toggleAddLine(txId) {
            const form = document.getElementById('add-line-form-' + txId);
            if (form) form.style.display = form.style.display === 'flex' ? 'none' : 'flex';
        }

        function addLine(txId, url) {
            const acc = document.getElementById('add-line-account-' + txId).value;
            const type = document.getElementById('add-line-type-' + txId).value;
            const amt = document.getElementById('add-line-amount-' + txId).value;
            if (!acc || !amt || parseFloat(amt) <= 0) {
                alert('Choose an account and enter an amount greater than zero.');
                return;
            }
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ chart_of_account_id: acc, type: type, amount: amt }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { location.reload(); }
                else { alert(data.message ?? 'Could not add line.'); }
            })
            .catch(() => alert('Could not add line.'));
        }

        function deleteLine(url) {
            window.showConfirmModal({
                label: 'Transactions',
                title: 'Delete Journal Line',
                body: 'This journal line will be permanently removed.',
                confirmText: 'Delete',
                danger: true,
                onConfirm: () => {
                    fetch(url, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) { location.reload(); }
                        else { alert(data.message ?? 'Could not delete line.'); }
                    })
                    .catch(() => alert('Could not delete line.'));
                },
            });
        }

        // ── Amount live panel ─────────────────────────────────────────
        (function () {
            const input  = document.getElementById('tx-amount-input');
            const panel  = document.getElementById('tx-amount-panel');
            const SEARCH_URL = @json(route('companies.transactions.search', $company));
            let debounce = null;

            function fmt(n) {
                return 'R ' + parseFloat(n || 0).toLocaleString('en-ZA', {minimumFractionDigits:2, maximumFractionDigits:2});
            }

            function renderHits(hits, typed) {
                if (!hits.length) {
                    panel.innerHTML = '<div class="tx-live-empty">No transactions matching R ' + parseFloat(typed).toFixed(2) + '</div>';
                    return;
                }
                panel.innerHTML = hits.map(h => {
                    const statusCls = ['posted','draft','reversed','reversal'].includes(h.status) ? h.status : 'draft';
                    const amtFormatted = fmt(h.total_debit);
                    return `<div class="tx-live-hit" onclick="jumpToTx(${h.id})">
                        <div class="tx-live-hit-top">
                            <span class="tx-live-hit-desc">${h.description || '<em>No description</em>'}</span>
                            <span class="tx-live-hit-ref">${h.reference ? '#' + h.reference : ''}</span>
                        </div>
                        <div class="tx-live-hit-meta">
                            <span class="tx-live-hit-date">${h.transaction_date || ''}</span>
                            <span class="tx-live-hit-amount" style="color:#1d4ed8;font-size:10pt;">${amtFormatted}</span>
                            <span class="tx-live-hit-status ${statusCls}">${h.status || ''}</span>
                        </div>
                    </div>`;
                }).join('');
            }

            function doSearch(val) {
                if (!val || isNaN(parseFloat(val))) { panel.style.display = 'none'; return; }
                panel.innerHTML = '<div class="tx-live-loading"><div class="tx-live-spinner"></div>Searching…</div>';
                panel.style.display = 'block';
                fetch(SEARCH_URL + '?amount=' + encodeURIComponent(val))
                    .then(r => r.json())
                    .then(data => renderHits(data.hits || [], val))
                    .catch(() => { panel.innerHTML = '<div class="tx-live-empty">Search unavailable</div>'; });
            }

            input.addEventListener('input', () => {
                clearTimeout(debounce);
                const v = input.value.trim();
                if (!v) { panel.style.display = 'none'; return; }
                debounce = setTimeout(() => doSearch(v), 300);
            });

            document.addEventListener('click', e => {
                if (!panel.contains(e.target) && e.target !== input) panel.style.display = 'none';
            });
        })();

        // ── Live Meilisearch search panel ──────────────────────────────
        (function () {
            const input   = document.getElementById('tx-desc-input');
            const panel   = document.getElementById('tx-live-panel');
            const SEARCH_URL = @json(route('companies.transactions.search', $company));
            let debounce  = null;
            let focusIdx  = -1;
            let lastQuery = '';

            function fmt(n) {
                return 'R ' + parseFloat(n || 0).toLocaleString('en-ZA', {minimumFractionDigits:2, maximumFractionDigits:2});
            }

            function renderHits(hits) {
                if (!hits.length) {
                    panel.innerHTML = '<div class="tx-live-empty">No matching transactions</div>';
                    return;
                }
                panel.innerHTML = hits.map((h, i) => {
                    const snippet = [h.line_text, h.notes].filter(Boolean).find(s => s.includes('<mark>')) || h.line_text || '';
                    const statusCls = ['posted','draft','reversed','reversal'].includes(h.status) ? h.status : 'draft';
                    return `<div class="tx-live-hit" data-idx="${i}" data-id="${h.id}" onclick="jumpToTx(${h.id})">
                        <div class="tx-live-hit-top">
                            <span class="tx-live-hit-desc">${h.description || '<em>No description</em>'}</span>
                            <span class="tx-live-hit-ref">${h.reference ? '#' + h.reference : ''}</span>
                        </div>
                        ${snippet ? `<div class="tx-live-hit-snippet">${snippet}</div>` : ''}
                        <div class="tx-live-hit-meta">
                            <span class="tx-live-hit-date">${h.transaction_date || ''}</span>
                            <span class="tx-live-hit-amount">${fmt(h.total_debit)}</span>
                            <span class="tx-live-hit-status ${statusCls}">${h.status || ''}</span>
                        </div>
                    </div>`;
                }).join('');
                focusIdx = -1;
            }

            function doSearch(q) {
                if (q.length < 2) { closePanel(); return; }
                lastQuery = q;
                panel.innerHTML = '<div class="tx-live-loading"><div class="tx-live-spinner"></div>Searching…</div>';
                panel.style.display = 'block';
                fetch(SEARCH_URL + '?q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(data => { if (lastQuery === q) renderHits(data.hits || []); })
                    .catch(() => { panel.innerHTML = '<div class="tx-live-empty">Search unavailable</div>'; });
            }

            function closePanel() {
                panel.style.display = 'none';
                panel.innerHTML = '';
                focusIdx = -1;
            }

            input.addEventListener('input', () => {
                clearTimeout(debounce);
                const q = input.value.trim();
                if (!q) { closePanel(); return; }
                debounce = setTimeout(() => doSearch(q), 220);
            });

            input.addEventListener('keydown', e => {
                const hits = panel.querySelectorAll('.tx-live-hit');
                if (!hits.length) return;
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    hits[focusIdx]?.classList.remove('focused');
                    focusIdx = Math.min(focusIdx + 1, hits.length - 1);
                    hits[focusIdx].classList.add('focused');
                    hits[focusIdx].scrollIntoView({block:'nearest'});
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    hits[focusIdx]?.classList.remove('focused');
                    focusIdx = Math.max(focusIdx - 1, 0);
                    hits[focusIdx].classList.add('focused');
                    hits[focusIdx].scrollIntoView({block:'nearest'});
                } else if (e.key === 'Enter' && focusIdx >= 0) {
                    e.preventDefault();
                    const id = parseInt(hits[focusIdx].dataset.id, 10);
                    jumpToTx(id);
                } else if (e.key === 'Escape') {
                    closePanel();
                }
            });

            document.addEventListener('click', e => {
                if (!panel.contains(e.target) && e.target !== input) closePanel();
            });
        })();

        window.jumpToTx = function(id) {
            document.getElementById('tx-live-panel').style.display = 'none';
            const row = document.getElementById('tx-' + id);
            if (row) {
                row.scrollIntoView({behavior:'smooth', block:'center'});
                row.style.transition = 'background 0.1s';
                row.style.background = '#fef9c3';
                setTimeout(() => { row.style.background = ''; }, 1800);
            } else {
                // Transaction not in current page view — apply as filter
                document.getElementById('tx-desc-input').form.submit();
            }
        };
    </script>
@endsection
