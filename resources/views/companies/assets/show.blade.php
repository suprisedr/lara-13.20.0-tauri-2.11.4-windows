@extends('layouts.public')

@section('title', $asset->name . ' — Asset')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        /* ── Management bar ─────────────────────────────────────── */
        .inv-mgmt-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8pt;
            flex-wrap: wrap;
            margin-bottom: 12pt;
        }

        .inv-mgmt-bar a, .inv-mgmt-bar .mgmt-back {
            font-size: 7pt;
            color: #7a90a5;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 2pt;
            transition: color 0.15s;
            background: none;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }

        .inv-mgmt-bar a:hover, .inv-mgmt-bar .mgmt-back:hover { color: #16355c; }

        .mgmt-btn {
            display: inline-flex;
            align-items: center;
            gap: 3pt;
            background: #fff;
            border: 1px solid #c9dff0;
            color: #16355c;
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 3pt 7pt;
            text-decoration: none;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s, color 0.15s;
        }

        .mgmt-btn:hover { background: #eef6fc; color: #16355c; }

        .mgmt-btn.primary { background: #0079c8; color: #fff; border-color: #0079c8; }
        .mgmt-btn.primary:hover { background: #005f9e; }

        .mgmt-btn.danger { border-color: #dc2626; color: #dc2626; }
        .mgmt-btn.danger:hover { background: #dc2626; color: #fff; }

        /* ── Asset document ─────────────────────────────────────── */
        .cust-doc {
            background: #fff;
            border: 1px solid #c9dff0;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            color: #16355c;
            font-size: 7pt;
            line-height: 1.45;
        }

        .cust-doc-body { padding: 16pt 18pt; }

        .cust-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6pt;
        }

        .doc-title {
            font-size: 11pt;
            font-weight: 800;
            text-align: right;
            margin-bottom: 2pt;
            letter-spacing: 0.04em;
        }

        .doc-meta-line { text-align: right; font-size: 7pt; }

        .status-box {
            display: inline-block;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #16355c;
            padding: 1pt 4pt;
            font-size: 5.5pt;
            letter-spacing: 0.08em;
            margin-top: 3pt;
        }

        .status-box.disposed { color: #dc2626; border-color: #dc2626; }

        .divider {
            border: none;
            border-top: 1.5pt solid #16355c;
            margin: 8pt 0 10pt;
        }

        .divider.light {
            border-top: 1px solid #ddebf5;
            margin: 10pt 0;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2pt;
        }

        .summary-table td {
            padding: 0 10pt 0 0;
            font-size: 7pt;
            vertical-align: top;
        }

        .summary-table .lbl {
            display: block;
            font-weight: 700;
            font-size: 5.5pt;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 1pt;
        }

        .summary-table .amt {
            font-size: 8pt;
            font-weight: 800;
        }

        .section-header {
            font-weight: 700;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1.5px solid #16355c;
            padding-bottom: 2pt;
            margin-bottom: 4pt;
        }

        .info-section { margin-top: 12pt; }

        table.cust-items-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.cust-items-table thead td {
            font-weight: 700;
            font-size: 6.5pt;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1.5px solid #16355c;
            padding-bottom: 4pt;
        }

        table.cust-items-table thead td.amt { text-align: right; }

        table.cust-items-table tbody td {
            padding: 4pt 0;
            font-size: 7pt;
            border-bottom: 1px solid #ddebf5;
            vertical-align: middle;
        }

        table.cust-items-table tbody td.amt {
            text-align: right;
            font-family: "DejaVu Sans Mono", monospace;
            white-space: nowrap;
        }

        table.cust-items-table tbody tr:last-child td { border-bottom: none; }
        table.cust-items-table tbody tr:hover td { background: #eef6fc; }

        .ev-chip {
            display: inline-block;
            font-size: 5pt;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 1pt 4pt;
            white-space: nowrap;
        }

        .empty-row {
            padding: 8pt 0;
            text-align: center;
            color: #7a90a5;
            font-size: 7pt;
        }

        /* ── Action forms (inline) ──────────────────────────────── */
        .action-section {
            margin-top: 12pt;
        }

        .action-panel {
            border-bottom: 1px solid #ddebf5;
        }

        .action-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 5pt 0;
            cursor: pointer;
            user-select: none;
        }

        .action-panel-title {
            font-size: 7pt;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 4pt;
        }

        .action-panel-body {
            display: none;
            padding-bottom: 8pt;
        }

        .action-panel.open .action-panel-body { display: block; }
        .action-panel-chevron { font-size: 6pt; color: #7a90a5; transition: transform 0.15s; }
        .action-panel.open .action-panel-chevron { transform: rotate(180deg); }

        /* form fields inside action panels */
        .af-row { display: flex; flex-wrap: wrap; gap: 4pt 6pt; align-items: flex-end; margin-bottom: 5pt; }
        .af-field { flex: 1; min-width: 100pt; }
        .af-field.wide { flex: 2; min-width: 150pt; }
        .af-field label { display: block; font-size: 5pt; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #7a90a5; margin-bottom: 2pt; }
        .af-field input, .af-field select { width: 100%; border: 1px solid #c9dff0; padding: 3pt 4pt; font-size: 7pt; font-family: inherit; color: #16355c; box-sizing: border-box; background: #fff; }
        .af-field input:disabled { background: #eef6fc; color: #7a90a5; }
        .af-field input:focus, .af-field select:focus { outline: none; border-color: #0079c8; }

        .af-hint { font-size: 6pt; color: #7a90a5; margin-bottom: 4pt; line-height: 1.4; }

        /* Edit modal fields */
        .as-field label { display:block; font-size:5pt; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:#7a90a5; margin-bottom:2pt; }
        .as-field input, .as-field select { width:100%; border:1px solid #c9dff0; padding:3pt 4pt; font-size:7pt; font-family:inherit; color:#16355c; background:#fff; outline:none; box-sizing:border-box; height:16pt; border-radius:0; }
        .as-field input:focus, .as-field select:focus { border-color:#0079c8; }
        .as-field input:disabled { background:#eef6fc; color:#7a90a5; }

        @media (max-width: 640px) {
            .cust-doc-body { padding: 10pt 8pt; }
            .cust-header-table, .cust-header-table tr, .cust-header-table td { display:block; width:100%!important; text-align:left!important; }
            .doc-title, .doc-meta-line { text-align:left!important; }
            .summary-table, .summary-table tr, .summary-table td { display:block; width:100%!important; padding:0 0 6pt; }
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
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:6pt 10pt;font-size:7pt;font-weight:600;margin-bottom:10pt;">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:6pt 10pt;font-size:7pt;font-weight:600;margin-bottom:10pt;">
                        {{ session('error') }}
                    </div>
                @endif

                @php
                    $today  = now()->format('Y-m-d');
                    $accDep = $asset->accumulatedDepreciation($today);
                    $accImp = (float)($asset->accumulated_impairment ?? 0);
                    $revSur = (float)($asset->revaluation_surplus ?? 0);
                    $nbv    = $asset->netBookValue($today);
                    $policy = $asset->ppeClass?->accounting_policy ?? 'cost';
                    $life   = $asset->useful_life_years ?? $asset->ppeClass?->useful_life_years;
                @endphp

                {{-- Management bar --}}
                <div class="inv-mgmt-bar">
<div style="display:flex;align-items:center;gap:4pt;flex-wrap:wrap;">
                        @if ($asset->status === \App\Models\Asset::STATUS_ACTIVE)
                            <button type="button" class="mgmt-btn" onclick="togglePanel('capitalise')">+ Capitalise</button>
                            @if ($policy === 'revaluation')
                                <button type="button" class="mgmt-btn" onclick="togglePanel('revalue')">Revalue</button>
                            @endif
                            <button type="button" class="mgmt-btn" onclick="togglePanel('impair')">Impair</button>
                            @if ($accImp > 0)
                                <button type="button" class="mgmt-btn" onclick="togglePanel('reverse')">Reverse Imp.</button>
                            @endif
                            <button type="button" class="mgmt-btn" onclick="togglePanel('held-for-sale')" style="border-color:#854d0e;color:#854d0e;">IFRS 5</button>
                            <button type="button" class="mgmt-btn danger" onclick="togglePanel('dispose')">Dispose</button>
                        @elseif ($asset->status === \App\Models\Asset::STATUS_HELD_FOR_SALE)
                            <a href="{{ route('companies.held-for-sale.index', $company) }}" class="mgmt-btn" style="border-color:#854d0e;color:#854d0e;">View in HFS Register</a>
                        @endif
                        <button type="button" class="mgmt-btn primary" onclick="openEditModal()">Edit Asset</button>
                    </div>
                </div>

                {{-- Document --}}
                <div class="cust-doc">
                    <div class="cust-doc-body">

                        {{-- Header --}}
                        <table class="cust-header-table">
                            <tr>
                                <td style="vertical-align:top;width:55%;">
                                    <div style="font-size:11pt;font-weight:800;letter-spacing:-0.01em;">{{ $asset->name }}</div>
                                    @if ($asset->asset_tag)
                                        <div style="font-size:7pt;color:#7a90a5;margin-top:2pt;">{{ $asset->asset_tag }}</div>
                                    @endif
                                </td>
                                <td style="vertical-align:top;width:45%;">
                                    <div class="doc-title">ASSET PROFILE</div>
                                    <div class="doc-meta-line">Class: <strong>{{ $asset->ppeClass?->name ?? 'Unclassified' }}</strong></div>
                                    <div class="doc-meta-line">Policy: <strong>{{ ucfirst($policy) }} model</strong></div>
                                    @if ($asset->location)
                                        <div class="doc-meta-line">Location: <strong>{{ $asset->location }}</strong></div>
                                    @endif
                                    <div style="text-align:right;">
                                        @if ($asset->status === \App\Models\Asset::STATUS_HELD_FOR_SALE)
                                            <span class="status-box" style="color:#854d0e;border-color:#854d0e;">Held for Sale</span>
                                        @elseif ($asset->isDisposed())
                                            <span class="status-box disposed">Disposed</span>
                                        @else
                                            <span class="status-box">Active</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </table>

                        {{-- Meta row --}}
                        <table style="width:100%;border-collapse:collapse;font-size:7pt;margin-bottom:4pt;">
                            <tr>
                                <td style="vertical-align:top;width:50%;">
                                    <div><span style="font-weight:700;display:inline-block;width:130px;">Acquired</span>{{ $asset->acquisition_date->format('d M Y') }}</div>
                                    <div><span style="font-weight:700;display:inline-block;width:130px;">Depreciating from</span>{{ $asset->depreciationStartDate()->format('d M Y') }}@if($asset->depreciation_start_date && !$asset->depreciation_start_date->eq($asset->acquisition_date)) <span style="color:#6b21a8;font-size:6pt;margin-left:2pt;">(in-service date set)</span>@endif</div>
                                    <div><span style="font-weight:700;display:inline-block;width:130px;">Useful life</span>{{ $life ? rtrim(rtrim(number_format((float)$life,2),'0'),'.').' years' : '—' }}</div>
                                    <div><span style="font-weight:700;display:inline-block;width:130px;">Method</span>{{ \App\Models\Asset::METHODS[$asset->depreciation_method ?? $asset->ppeClass?->depreciation_method] ?? 'Straight-line' }}</div>
                                    @if ($asset->sars_wear_tear_years)
                                        <div><span style="font-weight:700;display:inline-block;width:130px;">SARS wear &amp; tear</span>{{ $asset->sars_wear_tear_years }} years</div>
                                    @endif
                                    @if ($asset->notes)
                                        <div style="margin-top:3pt;color:#7a90a5;font-style:italic;">{{ $asset->notes }}</div>
                                    @endif
                                </td>
                                <td style="vertical-align:top;width:50%;text-align:right;">
                                    @if ($asset->isDisposed())
                                        <div>Disposed: <strong>{{ $asset->disposal_date?->format('d M Y') }}</strong></div>
                                        <div>Proceeds: <strong>R {{ number_format((float)($asset->disposal_proceeds??0),2) }}</strong></div>
                                    @endif
                                    <div>Residual value: <strong>R {{ number_format((float)($asset->residual_value??0),2) }}</strong></div>
                                    <div>Original cost: <strong>R {{ number_format((float)$asset->cost,2) }}</strong></div>
                                </td>
                            </tr>
                        </table>

                        <hr class="divider">

                        {{-- Summary --}}
                        <table class="summary-table">
                            <tr>
                                <td>
                                    <span class="lbl">Cost</span>
                                    <span class="amt">R {{ number_format((float)$asset->cost, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Acc. Depreciation</span>
                                    <span class="amt">R {{ number_format($accDep, 2) }}</span>
                                </td>
                                @if ($accImp != 0)
                                <td>
                                    <span class="lbl">Acc. Impairment</span>
                                    <span class="amt">R {{ number_format($accImp, 2) }}</span>
                                </td>
                                @endif
                                @if ($revSur != 0)
                                <td>
                                    <span class="lbl">Reval. Surplus (OCI)</span>
                                    <span class="amt">R {{ number_format($revSur, 2) }}</span>
                                </td>
                                @endif
                                <td>
                                    <span class="lbl">Net Book Value</span>
                                    <span class="amt">R {{ number_format($nbv, 2) }}</span>
                                </td>
                            </tr>
                        </table>

                        {{-- IAS 16 Movement History --}}
                        <div class="info-section">
                            <div class="section-header">IAS 16 Movement History</div>
                            <div id="history-loading" style="color:#7a90a5;font-size:7pt;font-style:italic;padding:6pt 0;">Loading history…</div>
                            <div id="history-empty"   style="display:none;color:#7a90a5;font-size:7pt;font-style:italic;padding:6pt 0;">No journal events recorded yet for this asset.</div>
                            <table class="cust-items-table" id="history-table" style="display:none;">
                                <thead>
                                    <tr>
                                        <td style="width:18%;">Event</td>
                                        <td style="width:13%;">Date</td>
                                        <td style="width:37%;">Description</td>
                                        <td style="width:18%;">Journal</td>
                                        <td class="amt" style="width:14%;">Amount</td>
                                    </tr>
                                </thead>
                                <tbody id="history-tbody"></tbody>
                            </table>
                        </div>

                        {{-- IAS 16 Actions --}}
                        @if ($asset->status === \App\Models\Asset::STATUS_ACTIVE)
                        <div class="action-section">
                            <div class="section-header">Record a Movement</div>
                            <p style="font-size:6.5pt;color:#7a90a5;margin:3pt 0 6pt;">Select an action above or click a heading below. AI posts the journal automatically after you save.</p>

                            {{-- Capitalise --}}
                            <div class="action-panel" id="panel-capitalise">
                                <div class="action-panel-head" onclick="togglePanel('capitalise')">
                                    <span class="action-panel-title">Capitalise subsequent cost <small style="font-weight:400;color:#7a90a5;">(IAS 16.7)</small></span>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Only for costs that extend useful life, add capacity, or replace a component. Routine maintenance must be expensed.</p>
                                    <form method="POST" action="{{ route('companies.assets.capitalise', [$company, $asset]) }}">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Amount (R)</label>
                                                <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0.00">
                                            </div>
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                            <div class="af-field wide">
                                                <label>Description</label>
                                                <input type="text" name="description" maxlength="255" placeholder="e.g. Engine overhaul extending life by 3 years">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            @if ($policy === 'revaluation')
                            {{-- Revalue --}}
                            <div class="action-panel" id="panel-revalue">
                                <div class="action-panel-head" onclick="togglePanel('revalue')">
                                    <span class="action-panel-title">Revalue asset <small style="font-weight:400;color:#7a90a5;">(IAS 16.31)</small></span>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Enter the IFRS fair-value carrying amount. Gross-up, OCI surplus and IAS 12 deferred tax are posted automatically.</p>
                                    <form method="POST" action="{{ route('companies.assets.revalue', [$company, $asset]) }}">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Current carrying amount</label>
                                                <input type="text" disabled value="R {{ number_format($nbv, 2) }}">
                                            </div>
                                            <div class="af-field">
                                                <label>New carrying amount (R)</label>
                                                <input type="number" name="new_carrying_amount" step="0.01" min="0.01" required placeholder="0.00">
                                            </div>
                                            <div class="af-field">
                                                <label>Effective date</label>
                                                <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endif

                            {{-- Impair --}}
                            <div class="action-panel" id="panel-impair">
                                <div class="action-panel-head" onclick="togglePanel('impair')">
                                    <span class="action-panel-title">Record impairment <small style="font-weight:400;color:#7a90a5;">(IAS 36)</small></span>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Write down to recoverable amount (higher of FVLCTS and VIU). For revaluation-model assets the OCI surplus is exhausted first.</p>
                                    <form method="POST" action="{{ route('companies.assets.impair', [$company, $asset]) }}">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Current carrying amount</label>
                                                <input type="text" disabled value="R {{ number_format($nbv, 2) }}">
                                            </div>
                                            <div class="af-field">
                                                <label>Impairment amount (R)</label>
                                                <input type="number" name="impairment_amount" step="0.01" min="0.01" max="{{ $nbv }}" required placeholder="0.00">
                                            </div>
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                            <div class="af-field wide">
                                                <label>Reason (optional)</label>
                                                <input type="text" name="reason" maxlength="500" placeholder="e.g. physical damage, obsolescence">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            @if ($accImp > 0)
                            {{-- Reverse impairment --}}
                            <div class="action-panel" id="panel-reverse">
                                <div class="action-panel-head" onclick="togglePanel('reverse')">
                                    <span class="action-panel-title">Reverse impairment <small style="font-weight:400;color:#7a90a5;">(IAS 36.114)</small></span>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Capped at accumulated impairment. Asset cannot exceed depreciated cost as if the impairment never occurred.</p>
                                    <form method="POST" action="{{ route('companies.assets.reverse-impairment', [$company, $asset]) }}">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Accumulated impairment</label>
                                                <input type="text" disabled value="R {{ number_format($accImp, 2) }}">
                                            </div>
                                            <div class="af-field">
                                                <label>Reversal amount (R)</label>
                                                <input type="number" name="reversal_amount" step="0.01" min="0.01" max="{{ $accImp }}" required placeholder="0.00">
                                            </div>
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endif

                            {{-- Dispose --}}
                            <div class="action-panel" id="panel-dispose">
                                <div class="action-panel-head" onclick="togglePanel('dispose')">
                                    <span class="action-panel-title">Dispose asset <small style="font-weight:400;color:#7a90a5;">(IAS 16.67)</small></span>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Derecognise the asset. Gain/loss = proceeds − carrying amount, posted to P&L. Use R 0 for scrapped assets.</p>
                                    <form method="POST" action="{{ route('companies.assets.dispose', [$company, $asset]) }}"
                                        onsubmit="return false" data-confirm-label="Asset Register" data-ajax-confirm data-confirm-title="Dispose Asset" data-confirm-body="Dispose {{ addslashes($asset->name) }}? This will derecognise the asset and post the disposal journal." data-confirm-text="Dispose" data-confirm-danger="1">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Carrying amount</label>
                                                <input type="text" disabled value="R {{ number_format($nbv, 2) }}">
                                            </div>
                                            <div class="af-field">
                                                <label>Disposal proceeds (R)</label>
                                                <input type="number" name="disposal_proceeds" step="0.01" min="0" value="0" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Disposal date</label>
                                                <input type="date" name="disposal_date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn danger">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Held for Sale (IFRS 5) --}}
                            <div class="action-panel" id="panel-held-for-sale">
                                <div class="action-panel-head" onclick="togglePanel('held-for-sale')">
                                    <span class="action-panel-title">Reclassify as held for sale <small style="font-weight:400;color:#7a90a5;">(IFRS 5)</small></span>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Reclassify when the asset is available for immediate sale and sale is highly probable within 12 months. Depreciation ceases. Measured at lower of carrying amount and fair value less costs to sell.</p>
                                    <form method="POST" action="{{ route('companies.assets.reclassify-held-for-sale', [$company, $asset]) }}"
                                        onsubmit="return false" data-confirm-label="Asset Register" data-ajax-confirm data-confirm-title="Reclassify as Held for Sale" data-confirm-body="Reclassify {{ addslashes($asset->name) }} as held for sale (IFRS 5)? Depreciation will cease and the asset moves to the HFS register." data-confirm-text="Reclassify" data-confirm-danger="0">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Carrying amount</label>
                                                <input type="text" disabled value="R {{ number_format($nbv, 2) }}">
                                            </div>
                                            <div class="af-field">
                                                <label>Reclassification date</label>
                                                <input type="date" name="reclassification_date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                            <div class="af-field">
                                                <label>FVLCTS (R) — optional</label>
                                                <input type="number" name="fair_value_less_costs_to_sell" step="0.01" min="0" placeholder="Fair value less costs to sell">
                                            </div>
                                        </div>
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Expected sale date</label>
                                                <input type="date" name="expected_sale_date">
                                            </div>
                                            <div class="af-field wide">
                                                <label>Buyer details (optional)</label>
                                                <input type="text" name="buyer_details" maxlength="500" placeholder="e.g. Pending offer from ABC Ltd">
                                            </div>
                                        </div>
                                        <div class="af-row">
                                            <div class="af-field" style="flex:3;min-width:250px;">
                                                <label>Notes</label>
                                                <input type="text" name="notes" maxlength="1000" placeholder="Any additional context for the reclassification">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn primary">Reclassify</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        </div>{{-- /.action-section --}}
                        @endif

                    </div>{{-- /.cust-doc-body --}}
                </div>{{-- /.cust-doc --}}

            </main>
        </div>
    </div>

    {{-- History JS --}}
    <script>
        const historyUrl = @json(route('companies.assets.history', [$company, $asset]));

        function escHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

const journalStatusStyle = {
            pending: { label: 'Journal pending', color: '#92400e' },
            posted:  { label: 'Journal posted',  color: '#065f46' },
            failed:  { label: 'Journal failed',  color: '#b91c1c' },
        };

        async function retryPosting(btn, url) {
            btn.disabled = true;
            btn.textContent = '…';
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const cell = btn.closest('[data-journal-status-cell]');
                if (cell) {
                    cell.querySelector('[data-journal-status]').textContent = journalStatusStyle.pending.label;
                    cell.querySelector('[data-journal-status]').style.color  = journalStatusStyle.pending.color;
                    btn.remove();
                }
                showToast('Retry queued — posting in progress', '#1d4ed8');
            } catch {
                showToast('Retry failed — please try again', '#b91c1c');
                btn.disabled = false;
                btn.textContent = 'Retry';
            }
        }

        async function loadHistory() {
            const tbody  = document.getElementById('history-tbody');
            const table  = document.getElementById('history-table');
            const empty  = document.getElementById('history-empty');
            const loading= document.getElementById('history-loading');

            try {
                const res  = await fetch(historyUrl, { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();

                loading.style.display = 'none';

                if (!data.events || data.events.length === 0) {
                    empty.style.display = 'block';
                    return;
                }

                tbody.innerHTML = '';
                const fmt = v => Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                data.events.forEach(ev => {
                    const js = journalStatusStyle[ev.journal_status] || journalStatusStyle.pending;
                    const retryBtn = ev.retry_url
                        ? `<button onclick="retryPosting(this,'${escHtml(ev.retry_url)}')" style="margin-left:4pt;font-size:5pt;font-weight:700;padding:1pt 4pt;border-radius:4px;border:1px solid #b91c1c;color:#b91c1c;background:transparent;cursor:pointer;opacity:0;transition:opacity 0.15s;" class="retry-posting-btn">Retry</button>`
                        : '';
                    const tr = document.createElement('tr');
                    tr.dataset.eventId = ev.id;
                    tr.innerHTML = `
                        <td><span class="ev-chip" style="background:${escHtml(ev.bg)};color:${escHtml(ev.color)};">${escHtml(ev.label)}</span></td>
                        <td style="font-family:'DejaVu Sans Mono',monospace;font-size:6.5pt;">${escHtml(ev.date || '—')}</td>
                        <td>${escHtml(ev.description || '')}</td>
                        <td data-journal-status-cell style="white-space:nowrap;">
                            ${ev.transaction_url
                                ? `<a href="${escHtml(ev.transaction_url)}" style="font-size:5.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:${js.color};text-decoration:underline;cursor:pointer;" data-journal-status>${js.label}</a>`
                                : `<span data-journal-status style="font-size:5.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:${js.color};">${js.label}</span>`
                            }${retryBtn}
                        </td>
                        <td class="amt">${ev.amount ? 'R ' + fmt(ev.amount) : '—'}</td>
                    `;
                    if (ev.retry_url) {
                        tr.addEventListener('mouseenter', () => tr.querySelector('.retry-posting-btn')?.style.setProperty('opacity','1'));
                        tr.addEventListener('mouseleave', () => tr.querySelector('.retry-posting-btn')?.style.setProperty('opacity','0'));
                    }
                    tbody.appendChild(tr);
                });

                table.style.display = 'table';
            } catch (e) {
                loading.style.display = 'none';
                empty.textContent = 'Could not load history: ' + e.message;
                empty.style.display = 'block';
            }
        }

        loadHistory();

        // ── Real-time posting status via Reverb ──────────────────
        window.addEventListener('echo:ready', function () {
            window.Echo.private('company.{{ $company->id }}')
                .listen('.posting.status.updated', (e) => {
                    if (e.entity_type !== 'asset_event') return;
                    const rows = document.querySelectorAll('#history-tbody tr[data-event-id="' + e.entity_id + '"]');
                    rows.forEach(row => {
                        const cell = row.querySelector('[data-journal-status]');
                        if (!cell) return;
                        const js = journalStatusStyle[e.status] || journalStatusStyle.pending;
                        cell.textContent = js.label;
                        cell.style.color = js.color;
                        if (e.status !== 'failed') {
                            row.querySelector('.retry-posting-btn')?.remove();
                        }
                    });
                    if (e.status === 'posted' || e.status === 'failed') {
                        const toast = document.createElement('div');
                        toast.style.cssText = 'position:fixed;bottom:12pt;right:12pt;padding:6pt 10pt;border-radius:6px;font-size:7pt;font-weight:600;color:#fff;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:opacity 0.4s;background:' + (e.status==='posted'?'#065f46':'#b91c1c');
                        toast.textContent = e.label;
                        document.body.appendChild(toast);
                        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 400); }, 4000);
                        loadHistory();
                    }
                });
        });

        // ── Action panels ────────────────────────────────────────
        function togglePanel(id) {
            const el = document.getElementById('panel-' + id);
            if (!el) return;
            const isOpen = el.classList.contains('open');
            document.querySelectorAll('.action-panel.open').forEach(p => p.classList.remove('open'));
            if (!isOpen) {
                el.classList.add('open');
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        // ── AJAX action forms (no page reload) ───────────────────
        function showToast(msg, color) {
            const t = document.createElement('div');
            t.style.cssText = 'position:fixed;bottom:12pt;right:12pt;padding:6pt 10pt;border-radius:6px;font-size:7pt;font-weight:600;color:#fff;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:opacity 0.4s;background:' + color;
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 4000);
        }

        async function submitActionForm(form) {
            const btn = form.querySelector('[type=submit]');
            if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    redirect: 'follow',
                });
                if (res.ok || res.redirected) {
                    document.querySelectorAll('.action-panel.open').forEach(p => p.classList.remove('open'));
                    showToast('Saved — journal posting in progress', '#1d4ed8');
                    form.reset();
                } else {
                    showToast('Error saving action', '#b91c1c');
                }
            } catch {
                showToast('Network error', '#b91c1c');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = 'Save'; }
            }
        }

        document.querySelectorAll('.action-panel form').forEach(form => {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                if (form.dataset.confirmTitle) {
                    window.showConfirmModal({
                        label:       form.dataset.confirmLabel ?? '',
                        title:       form.dataset.confirmTitle,
                        body:        form.dataset.confirmBody ?? '',
                        confirmText: form.dataset.confirmText ?? 'Confirm',
                        danger:      !!form.dataset.confirmDanger,
                        onConfirm:   () => submitActionForm(form),
                    });
                } else {
                    await submitActionForm(form);
                }
            });
        });
    </script>

    {{-- Edit asset modal --}}
    <div id="edit-modal"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;padding:16pt 8pt;overflow-y:auto;">
        <div style="background:#fff;width:100%;max-width:640px;border:1px solid #16355c;border-radius:0;box-shadow:0 20px 60px rgba(0,0,0,0.4);overflow:hidden;margin:0 auto;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:9pt 12pt;border-bottom:1.5pt solid #16355c;">
                <h3 style="font-size:8pt;font-weight:800;letter-spacing:0.04em;margin:0;text-transform:uppercase;">Edit Asset</h3>
                <button onclick="closeEditModal()"
                    style="background:none;border:none;font-size:11pt;line-height:1;color:#16355c;cursor:pointer;">&times;</button>
            </div>

            <form method="POST" action="{{ route('companies.assets.update', [$company, $asset]) }}">
                @csrf
                @method('PATCH')
                <div style="padding:10pt 12pt;">

                @if ($errors->any())
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:5pt 7pt;font-size:6.5pt;margin-bottom:8pt;border-radius:0;">
                        <strong>Please fix the following:</strong>
                        <ul style="margin:2pt 0 0 8pt;padding:0;">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:4pt 7pt;margin-bottom:8pt;">
                    <div class="as-field" style="grid-column:span 2;">
                        <label>Asset name</label>
                        <input type="text" name="name" value="{{ old('name', $asset->name) }}" required>
                    </div>
                    <div class="as-field">
                        <label>Asset tag</label>
                        <input type="text" name="asset_tag" value="{{ old('asset_tag', $asset->asset_tag) }}">
                    </div>
                    <div class="as-field">
                        <label>PPE class</label>
                        <select name="ppe_class_id">
                            <option value="">— Unclassified —</option>
                            @foreach ($ppeClasses as $cls)
                                <option value="{{ $cls->id }}" @selected(old('ppe_class_id', $asset->ppe_class_id) == $cls->id)>{{ $cls->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="as-field">
                        <label>Location</label>
                        <input type="text" name="location" value="{{ old('location', $asset->location) }}">
                    </div>
                    <div class="as-field">
                        <label>Acquisition date</label>
                        <input type="date" name="acquisition_date" value="{{ old('acquisition_date', $asset->acquisition_date->format('Y-m-d')) }}" required>
                    </div>
                    <div class="as-field">
                        <label>Depreciation start date</label>
                        <input type="date" name="depreciation_start_date" value="{{ old('depreciation_start_date', $asset->depreciation_start_date?->format('Y-m-d')) }}" placeholder="Defaults to acquisition date">
                        <span style="font-size:5.5pt;color:#7a90a5;">When the asset is ready for use. Leave blank to use acquisition date.</span>
                    </div>
                    <div class="as-field">
                        <label>Cost (R)</label>
                        <input type="number" name="cost" value="{{ old('cost', (float)$asset->cost) }}" min="0" step="0.01" required>
                    </div>
                    <div class="as-field">
                        <label>Residual value (R)</label>
                        <input type="number" name="residual_value" value="{{ old('residual_value', (float)($asset->residual_value ?? 0)) }}" min="0" step="0.01">
                    </div>
                    <div class="as-field">
                        <label>Useful life (years)</label>
                        <input type="number" name="useful_life_years" value="{{ old('useful_life_years', $asset->useful_life_years) }}" min="0" max="999.99" step="0.01" placeholder="from class">
                    </div>
                    <div class="as-field">
                        <label>SARS wear &amp; tear (yrs)</label>
                        <input type="number" name="sars_wear_tear_years" value="{{ old('sars_wear_tear_years', $asset->sars_wear_tear_years) }}" min="0" max="999.99" step="0.01">
                    </div>
                    <div class="as-field">
                        <label>Depreciation method</label>
                        <select name="depreciation_method">
                            @foreach (\App\Models\Asset::METHODS as $key => $label)
                                <option value="{{ $key }}" @selected(old('depreciation_method', $asset->depreciation_method) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="as-field" style="grid-column:1/-1;">
                        <label>Notes</label>
                        <input type="text" name="notes" value="{{ old('notes', $asset->notes) }}" placeholder="Any additional detail">
                    </div>
                </div>
                <div style="display:flex;gap:5pt;justify-content:flex-end;padding-top:4pt;border-top:1px solid #c9dff0;">
                    <button type="button" onclick="closeEditModal()"
                        style="padding:4pt 9pt;border:1px solid #c9dff0;font-size:6.5pt;color:#16355c;background:#fff;cursor:pointer;font-weight:600;border-radius:0;">Cancel</button>
                    <button type="submit"
                        style="padding:4pt 10pt;background:#0079c8;color:#fff;border:1px solid #0079c8;font-size:6.5pt;font-weight:700;cursor:pointer;border-radius:0;">Save changes</button>
                </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal()  { document.getElementById('edit-modal').style.display = 'flex'; }
        function closeEditModal() { document.getElementById('edit-modal').style.display = 'none'; }
        document.getElementById('edit-modal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
        @if ($errors->any()) openEditModal(); @endif
    </script>
@endsection
