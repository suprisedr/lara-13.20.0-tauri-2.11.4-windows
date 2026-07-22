@extends('layouts.public')

@section('title', $lease->name . ' — Lease')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .inv-mgmt-bar { display: flex; align-items: center; justify-content: space-between; gap: 8pt; flex-wrap: wrap; margin-bottom: 12pt; }
        .inv-mgmt-bar a, .inv-mgmt-bar .mgmt-back { font-size: 7pt; color: #7a90a5; text-decoration: none; display: inline-flex; align-items: center; gap: 3pt; transition: color 0.15s; background: none; border: none; cursor: pointer; font-family: inherit; }
        .inv-mgmt-bar a:hover, .inv-mgmt-bar .mgmt-back:hover { color: #16355c; }
        .mgmt-btn { display: inline-flex; align-items: center; gap: 3pt; background: #fff; border: 1px solid #c9dff0; color: #16355c; font-size: 6.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 4pt 7pt; text-decoration: none; cursor: pointer; font-family: inherit; transition: background 0.15s, color 0.15s; }
        .mgmt-btn:hover { background: #eef6fc; color: #16355c; }
        .mgmt-btn.primary { background: #0079c8; color: #fff; }
        .mgmt-btn.primary:hover { background: #005f9e; }
        .mgmt-btn.danger { border-color: #dc2626; color: #dc2626; }
        .mgmt-btn.danger:hover { background: #dc2626; color: #fff; }
        .cust-doc { background: #fff; border: 1px solid #c9dff0; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif; color: #16355c; font-size: 7pt; line-height: 1.45; margin-bottom: 12pt; }
        .cust-doc-body { padding: 16pt 18pt; }
        .cust-header-table { width: 100%; border-collapse: collapse; margin-bottom: 6pt; }
        .doc-title { font-size: 11pt; font-weight: 800; text-align: right; margin-bottom: 2pt; letter-spacing: 0.04em; }
        .doc-meta-line { text-align: right; font-size: 7pt; }
        .status-box { display: inline-block; font-weight: 700; text-transform: uppercase; border: 1px solid #16355c; padding: 0.08rem 4pt; font-size: 5pt; letter-spacing: 0.08em; margin-top: 3pt; }
        .status-box.terminated { color: #dc2626; border-color: #dc2626; }
        .status-box.expired { color: #92400e; border-color: #92400e; }
        .status-box.exempt { color: #0079c8; border-color: #0079c8; }
        .divider { border: none; border-top: 1.5pt solid #16355c; margin: 8pt 0 10pt; }
        .divider.light { border-top: 1px solid #c9dff0; margin: 10pt 0; }
        .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 2pt; }
        .summary-table td { padding: 0 10pt 0 0; font-size: 7pt; vertical-align: top; }
        .summary-table .lbl { display: block; font-weight: 700; font-size: 5pt; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 1.5pt; }
        .summary-table .amt { font-size: 8pt; font-weight: 800; }
        .section-header { font-weight: 700; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1.5px solid #16355c; padding-bottom: 2pt; margin-bottom: 4pt; }
        .info-section { margin-top: 12pt; }
        table.cust-items-table { width: 100%; border-collapse: collapse; }
        table.cust-items-table thead td { font-weight: 700; font-size: 6.5pt; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 1.5px solid #16355c; padding-bottom: 4pt; }
        table.cust-items-table thead td.amt { text-align: right; }
        table.cust-items-table tbody td { padding: 5pt 0; font-size: 7pt; border-bottom: 1px solid #ddebf5; vertical-align: middle; }
        table.cust-items-table tbody td.amt { text-align: right; font-family: "DejaVu Sans Mono", monospace; white-space: nowrap; }
        table.cust-items-table tbody tr:last-child td { border-bottom: none; }
        table.cust-items-table tbody tr:hover td { background: #eef6fc; }
        .ev-chip { display: inline-block; font-size: 5pt; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; padding: 0.1rem 4pt; border-radius: 0; white-space: nowrap; }
        .ev-chip.commencement { background: #dcfce7; color: #15803d; }
        .ev-chip.payment { background: #dbeafe; color: #1d4ed8; }
        .ev-chip.modification { background: #fef3c7; color: #92400e; }
        .ev-chip.impairment { background: #fee2e2; color: #dc2626; }
        .ev-chip.reverse_impairment { background: #eef6fc; color: #0079c8; }
        .ev-chip.reassessment { background: #e0e7ff; color: #4338ca; }
        .ev-chip.termination { background: #fecaca; color: #991b1b; }
        .ev-chip.rou_depreciation { background: #f1f5f9; color: #475569; }
        .action-section { margin-top: 12pt; }
        .action-panel { border-bottom: 1px solid #ddebf5; }
        .action-panel-head { display: flex; align-items: center; justify-content: space-between; padding: 5pt 0; cursor: pointer; user-select: none; }
        .action-panel-title { font-size: 7pt; font-weight: 700; display: flex; align-items: center; gap: 4pt; }
        .action-panel-body { display: none; padding-bottom: 8pt; }
        .action-panel.open .action-panel-body { display: block; }
        .action-panel-chevron { font-size: 6pt; color: #7a90a5; transition: transform 0.15s; }
        .action-panel.open .action-panel-chevron { transform: rotate(180deg); }
        .af-row { display: flex; flex-wrap: wrap; gap: 4pt 6pt; align-items: flex-end; margin-bottom: 5pt; }
        .af-field { flex: 1; min-width: 100pt; }
        .af-field.wide { flex: 2; min-width: 150pt; }
        .af-field.full { flex-basis: 100%; }
        .af-field label { display: block; font-size: 5pt; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #7a90a5; margin-bottom: 0.2rem; }
        .af-field input, .af-field select, .af-field textarea { width: 100%; border: 1px solid #c9dff0; padding: 0.32rem 4pt; font-size: 7pt; font-family: inherit; color: #16355c; box-sizing: border-box; background: #fff; }
        .af-field input:focus, .af-field select:focus { outline: none; border-color: #0079c8; }
        .af-hint { font-size: 6pt; color: #7a90a5; margin-bottom: 4pt; line-height: 1.4; }
        .del-btn { background: none; border: none; font-size: 6.5pt; color: #dc2626; text-decoration: underline; cursor: pointer; font-family: inherit; padding: 0; }
        .del-btn:hover { color: #991b1c; }
        #schedule-table { display: none; margin-top: 6pt; }
        #schedule-table.loaded { display: block; }
        @media (max-width: 640px) { .cust-doc-body { padding: 10pt 8pt; } .cust-header-table, .cust-header-table tr, .cust-header-table td { display: block; width: 100%!important; text-align: left!important; } .doc-title, .doc-meta-line { text-align: left!important; } .summary-table, .summary-table tr, .summary-table td { display: block; width: 100%!important; padding: 0 0 6pt; } }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                @if (session('success'))
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:6pt 10pt;font-size:7.5pt;font-weight:600;margin-bottom:10pt;">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:6pt 10pt;font-size:7.5pt;font-weight:600;margin-bottom:10pt;">
                        {{ session('error') }}
                    </div>
                @endif

                @php
                    $isLessee = $lease->isLessee();
                    $isFinance = $lease->isFinanceLease();
                    $isOperating = $lease->isOperatingLease();
                    $accDep = $isLessee ? $lease->accumulatedDepreciation($asOf) : 0;
                    $accImp = (float)($lease->accumulated_impairment ?? 0);
                    $nbv = $isLessee ? $lease->rouNetBookValue($asOf) : 0;
                    $liab = $isLessee ? $lease->leaseLiabilityBalance($asOf) : 0;
                    $totalInterest = $isLessee ? $lease->totalInterestExpense($asOf) : 0;
                    $netInvBal = $isFinance ? $lease->netInvestmentBalance($asOf) : 0;
                    $financeIncome = $isFinance ? $lease->financeIncomeToDate($asOf) : 0;
                    $opIncome = $isOperating ? $lease->operatingLeaseIncomeToDate($asOf) : 0;
                    $terminated = $lease->isTerminated();
                    $expired = $lease->isExpired($asOf);
                    $exempt = $lease->isExempt();
                    $statusClass = $terminated ? 'terminated' : ($expired ? 'expired' : ($exempt ? 'exempt' : ''));
                    $statusLabel = $terminated ? 'Terminated' : ($expired ? 'Expired' : ($exempt ? 'Exempt' : 'Active'));
                    $roleLabel = $isLessee ? 'Lessee' : ($isFinance ? 'Lessor — Finance' : 'Lessor — Operating');
                @endphp

                <div class="inv-mgmt-bar">
<div style="display:flex;align-items:center;gap:4pt;flex-wrap:wrap;">
                        @unless ($terminated || $expired)
                            @if ($isLessee)
                                <button type="button" class="mgmt-btn" onclick="togglePanel('modify')">Modify</button>
                                <button type="button" class="mgmt-btn" onclick="togglePanel('impair')">Impair</button>
                                @if ($accImp > 0)
                                    <button type="button" class="mgmt-btn" onclick="togglePanel('reverse')">Reverse Imp.</button>
                                @endif
                            @endif
                            <button type="button" class="mgmt-btn danger" onclick="togglePanel('terminate')">Terminate</button>
                        @endunless
                        <button type="button" class="mgmt-btn primary" onclick="togglePanel('edit')">Edit</button>
                    </div>
                </div>

                <div class="cust-doc">
                    <div class="cust-doc-body">
                        <table class="cust-header-table">
                            <tr>
                                <td style="vertical-align:top;width:55%;">
                                    <div style="font-size:11pt;font-weight:800;letter-spacing:-0.01em;">{{ $lease->name }}</div>
                                    @if ($lease->counterparty)
                                        <div style="font-size:7pt;color:#7a90a5;margin-top:0.2rem;">{{ $isLessee ? 'Lessor' : 'Lessee' }}: {{ $lease->counterparty }}</div>
                                    @endif
                                    @if ($lease->asset_tag)
                                        <div style="font-size:7pt;color:#7a90a5;">{{ $lease->asset_tag }}</div>
                                    @endif
                                </td>
                                <td style="vertical-align:top;width:45%;">
                                    <div class="doc-title">LEASE PROFILE</div>
                                    <div class="doc-meta-line">Role: <strong>{{ $roleLabel }}</strong></div>
                                    <div class="doc-meta-line">Category: <strong>{{ \App\Models\Lease::CATEGORIES[$lease->category] ?? $lease->category }}</strong></div>
                                    <div class="doc-meta-line">Term: <strong>{{ $lease->lease_term_months }} months</strong></div>
                                    <div class="doc-meta-line">IBR: <strong>{{ number_format((float)$lease->incremental_borrowing_rate * 100, 2) }}%</strong></div>
                                    @if ($lease->location)
                                        <div class="doc-meta-line">Location: <strong>{{ $lease->location }}</strong></div>
                                    @endif
                                    <div style="text-align:right;">
                                        <span class="status-box {{ $statusClass }}">{{ $statusLabel }}</span>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%;border-collapse:collapse;font-size:7pt;margin-bottom:4pt;">
                            <tr>
                                <td style="vertical-align:top;width:50%;">
                                    <div><span style="font-weight:700;display:inline-block;width:150px;">Commencement</span>{{ $lease->commencement_date->format('d M Y') }}</div>
                                    <div><span style="font-weight:700;display:inline-block;width:150px;">End date</span>{{ $lease->end_date->format('d M Y') }}</div>
                                    <div><span style="font-weight:700;display:inline-block;width:150px;">Monthly payment</span>R {{ number_format((float)$lease->monthly_payment, 2) }}</div>
                                    <div><span style="font-weight:700;display:inline-block;width:150px;">Remaining</span>{{ $lease->remainingMonths($asOf) }} months</div>
                                    @if ($lease->notes)
                                        <div style="margin-top:3pt;color:#7a90a5;font-style:italic;">{{ $lease->notes }}</div>
                                    @endif
                                </td>
                                <td style="vertical-align:top;width:50%;text-align:right;">
                                    @if ($terminated)
                                        <div>Terminated: <strong>{{ $lease->termination_date->format('d M Y') }}</strong></div>
                                        <div>Gain/(Loss): <strong>R {{ number_format((float)($lease->termination_gain_loss ?? 0), 2) }}</strong></div>
                                    @endif
                                    @if ((float)($lease->initial_direct_costs ?? 0) > 0)
                                        <div>Initial direct costs: <strong>R {{ number_format((float)$lease->initial_direct_costs, 2) }}</strong></div>
                                    @endif
                                    @if ((float)($lease->residual_value_guarantee ?? 0) > 0)
                                        <div>Residual guarantee: <strong>R {{ number_format((float)$lease->residual_value_guarantee, 2) }}</strong></div>
                                    @endif
                                    @if ($exempt)
                                        <div style="margin-top:3pt;color:#0079c8;font-weight:700;">
                                            {{ $lease->is_short_term ? 'Short-term lease exempt' : '' }}
                                            {{ $lease->is_short_term && $lease->is_low_value ? ' · ' : '' }}
                                            {{ $lease->is_low_value ? 'Low-value asset exempt' : '' }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        <hr class="divider">

                        <table class="summary-table">
                            @if ($isLessee)
                            <tr>
                                <td>
                                    <span class="lbl">ROU Asset Cost</span>
                                    <span class="amt">R {{ number_format((float)$lease->rou_asset_cost, 2) }}</span>
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
                                <td>
                                    <span class="lbl">ROU NBV</span>
                                    <span class="amt">R {{ number_format($nbv, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Lease Liability</span>
                                    <span class="amt" style="color:#1d4ed8;">R {{ number_format($liab, 2) }}</span>
                                </td>
                            </tr>
                            @elseif ($isFinance)
                            <tr>
                                <td>
                                    <span class="lbl">Net Investment (Opening)</span>
                                    <span class="amt">R {{ number_format((float)$lease->net_investment, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Net Investment Balance</span>
                                    <span class="amt" style="color:#15803d;">R {{ number_format($netInvBal, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Unearned Finance Income</span>
                                    <span class="amt">R {{ number_format($lease->unearnedIncomeBalance($asOf), 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Finance Income Earned</span>
                                    <span class="amt" style="color:#15803d;">R {{ number_format($financeIncome, 2) }}</span>
                                </td>
                            </tr>
                            @else
                            <tr>
                                <td>
                                    <span class="lbl">Monthly Rental</span>
                                    <span class="amt">R {{ number_format((float)$lease->monthly_payment, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Total Rental Income (to date)</span>
                                    <span class="amt" style="color:#15803d;">R {{ number_format($opIncome, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Total Lease Income</span>
                                    <span class="amt">R {{ number_format((float)$lease->monthly_payment * $lease->lease_term_months, 2) }}</span>
                                </td>
                            </tr>
                            @endif
                        </table>

                        {{-- Event History --}}
                        <div class="info-section">
                            <div class="section-header">IFRS 16 Event History</div>
                            @if ($events->isEmpty())
                                <p style="color:#7a90a5;font-size:7pt;font-style:italic;padding:6pt 0;">No events recorded.</p>
                            @else
                                <table class="cust-items-table">
                                    <thead>
                                        <tr>
                                            <td style="width:13%;">Date</td>
                                            <td style="width:14%;">Type</td>
                                            <td class="amt" style="width:16%;">Amount</td>
                                            <td style="width:40%;">Description</td>
                                            <td style="width:17%;">Journal</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $jsColors = ['pending'=>'#92400e','posted'=>'#065f46','failed'=>'#b91c1c'];
                                            $eventTxnIds = $events->pluck('transaction_id')->filter()->unique()->all();
                                            $eventTxnMap = $eventTxnIds ? \App\Models\Transaction::whereIn('id', $eventTxnIds)->get()->keyBy('id') : collect();
                                        @endphp
                                        @foreach ($events as $event)
                                            @php $jsStatus = $event->journal_status ?? 'pending'; $jsLabel = ['pending'=>'Journal pending','posted'=>'Journal posted','failed'=>'Journal failed'][$jsStatus] ?? 'Journal pending'; @endphp
                                            <tr data-event-id="{{ $event->id }}"
                                                @if($jsStatus === 'failed')
                                                    onmouseenter="this.querySelector('.retry-posting-btn')?.style.setProperty('opacity','1')"
                                                    onmouseleave="this.querySelector('.retry-posting-btn')?.style.setProperty('opacity','0')"
                                                @endif>
                                                <td style="white-space:nowrap;">{{ $event->event_date->format('d M Y') }}</td>
                                                <td><span class="ev-chip {{ $event->type }}">{{ $event->type_label }}</span></td>
                                                <td class="amt">{{ number_format((float)$event->amount, 2) }}</td>
                                                <td style="color:#7a90a5;">{{ $event->description ?? '—' }}</td>
                                                <td data-journal-status-cell style="white-space:nowrap;">
                                                    @if($event->transaction_id && ($evTxn = $eventTxnMap->get($event->transaction_id)))
                                                        <a href="{{ route('companies.transactions', $company) }}?{{ http_build_query(['description' => $evTxn->reference, 'start_date' => substr($evTxn->transaction_date, 0, 10), 'end_date' => substr($evTxn->transaction_date, 0, 10), 'highlight' => $event->transaction_id]) }}" data-journal-status style="font-size:5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:{{ $jsColors[$jsStatus] ?? '#92400e' }};text-decoration:underline;">{{ $jsLabel }}</a>
                                                    @else
                                                        <span data-journal-status style="font-size:5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:{{ $jsColors[$jsStatus] ?? '#92400e' }};">{{ $jsLabel }}</span>
                                                    @endif
                                                    @if($jsStatus === 'failed')
                                                        <button onclick="retryPosting(this,'{{ route('companies.leases.events.retry-posting', [$company, $lease, $event]) }}')"
                                                            class="retry-posting-btn"
                                                            style="margin-left:4pt;font-size:5pt;font-weight:700;padding:0.15rem 4pt;border-radius:4px;border:1px solid #b91c1c;color:#b91c1c;background:transparent;cursor:pointer;opacity:0;transition:opacity 0.15s;">Retry</button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>

                        {{-- Amortisation Schedule --}}
                        <div class="info-section">
                            <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;">
                                Amortisation Schedule
                                <button type="button" class="mgmt-btn" onclick="loadSchedule()" id="load-schedule-btn" style="font-size:5pt;padding:2pt 5pt;">Load Schedule</button>
                            </div>
                            <div id="schedule-table">
                                <div id="schedule-loading" style="color:#7a90a5;font-size:7pt;font-style:italic;padding:6pt 0;">Loading…</div>
                                <div id="schedule-content" style="overflow-x:auto;"></div>
                            </div>
                        </div>

                        <hr class="divider light">

                        {{-- Action Panels --}}
                        <div class="action-section">

                            {{-- Modify Lease (lessee only) --}}
                            @if ($isLessee)
                            <div class="action-panel" id="panel-modify">
                                <div class="action-panel-head" onclick="togglePanel('modify')">
                                    <div class="action-panel-title"><span style="color:#92400e;">↻</span> Modify Lease</div>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Record a lease modification (change in scope, consideration, or term). The ROU asset and liability are remeasured at the date of modification (IFRS 16.44–46).</p>
                                    <form method="POST" action="{{ route('companies.leases.modify', [$company, $lease]) }}">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Modification date</label>
                                                <input type="date" name="event_date" value="{{ now()->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>New end date</label>
                                                <input type="date" name="new_end_date" value="{{ $lease->end_date->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>New monthly payment (R)</label>
                                                <input type="number" name="new_monthly_payment" step="0.01" min="0.01" value="{{ $lease->monthly_payment }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Description</label>
                                                <input type="text" name="description" placeholder="e.g. Lease extended by 24 months" maxlength="255">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;margin-top:4pt;">
                                            <button type="submit" class="mgmt-btn primary">Record Modification</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Impair (lessee only) --}}
                            <div class="action-panel" id="panel-impair">
                                <div class="action-panel-head" onclick="togglePanel('impair')">
                                    <div class="action-panel-title"><span style="color:#dc2626;">▼</span> Impair ROU Asset</div>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Record an impairment loss on the right-of-use asset per IAS 36.</p>
                                    <form method="POST" action="{{ route('companies.leases.impair', [$company, $lease]) }}">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Impairment date</label>
                                                <input type="date" name="event_date" value="{{ now()->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Impairment amount (R)</label>
                                                <input type="number" name="amount" step="0.01" min="0.01" required>
                                            </div>
                                            <div class="af-field wide">
                                                <label>Description</label>
                                                <input type="text" name="description" placeholder="Reason for impairment" maxlength="255">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;margin-top:4pt;">
                                            <button type="submit" class="mgmt-btn primary">Record Impairment</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Reverse Impairment --}}
                            @if ($accImp > 0)
                            <div class="action-panel" id="panel-reverse">
                                <div class="action-panel-head" onclick="togglePanel('reverse')">
                                    <div class="action-panel-title"><span style="color:#0079c8;">▲</span> Reverse Impairment</div>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Reverse a previously recognised impairment loss (up to R {{ number_format($accImp, 2) }} accumulated).</p>
                                    <form method="POST" action="{{ route('companies.leases.reverse-impairment', [$company, $lease]) }}">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Reversal date</label>
                                                <input type="date" name="event_date" value="{{ now()->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Reversal amount (R)</label>
                                                <input type="number" name="amount" step="0.01" min="0.01" max="{{ $accImp }}" required>
                                            </div>
                                            <div class="af-field wide">
                                                <label>Description</label>
                                                <input type="text" name="description" placeholder="Reason for reversal" maxlength="255">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;margin-top:4pt;">
                                            <button type="submit" class="mgmt-btn primary">Reverse Impairment</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endif
                            @endif {{-- end isLessee --}}

                            {{-- Terminate --}}
                            <div class="action-panel" id="panel-terminate">
                                <div class="action-panel-head" onclick="togglePanel('terminate')">
                                    <div class="action-panel-title"><span style="color:#dc2626;">✕</span> Early Termination</div>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">
                                        @if ($isLessee)
                                            Record early termination. The ROU asset and lease liability are derecognised, with any difference recognised as a gain or loss in profit or loss (IFRS 16.B98).
                                        @elseif ($isFinance)
                                            Record early termination. The net investment in the lease is derecognised.
                                        @else
                                            Record early termination of this operating lease.
                                        @endif
                                    </p>
                                    <form method="POST" action="{{ route('companies.leases.terminate', [$company, $lease]) }}">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Termination date</label>
                                                <input type="date" name="termination_date" value="{{ now()->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="af-field wide">
                                                <label>Description</label>
                                                <input type="text" name="description" placeholder="e.g. Early break clause exercised" maxlength="255">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;margin-top:4pt;">
                                            <button type="submit" class="mgmt-btn danger">Terminate Lease</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Edit --}}
                            <div class="action-panel" id="panel-edit">
                                <div class="action-panel-head" onclick="togglePanel('edit')">
                                    <div class="action-panel-title"><span style="color:#7a90a5;">✎</span> Edit Lease Details</div>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <form method="POST" action="{{ route('companies.leases.update', [$company, $lease]) }}">
                                        @csrf @method('PATCH')
                                        <div class="af-row">
                                            <div class="af-field wide">
                                                <label>Lease description</label>
                                                <input type="text" name="name" value="{{ $lease->name }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Asset tag</label>
                                                <input type="text" name="asset_tag" value="{{ $lease->asset_tag }}">
                                            </div>
                                            <div class="af-field">
                                                <label>Category</label>
                                                <select name="category">
                                                    @foreach (\App\Models\Lease::CATEGORIES as $key => $label)
                                                        <option value="{{ $key }}" @selected($lease->category === $key)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Lessor / Counterparty</label>
                                                <input type="text" name="counterparty" value="{{ $lease->counterparty }}">
                                            </div>
                                            <div class="af-field">
                                                <label>Location</label>
                                                <input type="text" name="location" value="{{ $lease->location }}">
                                            </div>
                                            <div class="af-field wide">
                                                <label>Notes</label>
                                                <input type="text" name="notes" value="{{ $lease->notes }}">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;margin-top:4pt;">
                                            <button type="submit" class="mgmt-btn primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function togglePanel(id) {
            var el = document.getElementById('panel-' + id);
            if (!el) return;
            var wasOpen = el.classList.contains('open');
            document.querySelectorAll('.action-panel.open').forEach(function(p) { p.classList.remove('open'); });
            if (!wasOpen) {
                el.classList.add('open');
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function showToast(msg, color) {
            const t = document.createElement('div');
            t.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;padding:6pt 10pt;border-radius:6px;font-size:0.8rem;font-weight:600;color:#fff;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:opacity 0.4s;background:' + color;
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 4000);
        }

        document.querySelectorAll('.action-panel form').forEach(form => {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
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
            });
        });

        function loadSchedule() {
            var container = document.getElementById('schedule-table');
            var content = document.getElementById('schedule-content');
            var loading = document.getElementById('schedule-loading');
            var btn = document.getElementById('load-schedule-btn');
            container.classList.add('loaded');
            btn.style.display = 'none';

            var role = '{{ $lease->role ?? "lessee" }}';
            var classification = '{{ $lease->classification ?? "" }}';

            fetch('{{ route('companies.leases.schedule', [$company, $lease]) }}')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    loading.style.display = 'none';
                    if (!data.length) { content.innerHTML = '<p style="color:#7a90a5;font-size:7pt;font-style:italic;">No schedule data.</p>'; return; }
                    var html = '<table class="cust-items-table"><thead><tr>';

                    if (role === 'lessor' && classification === 'operating') {
                        html += '<td>Month</td><td>Date</td><td class="amt">Rental Income</td><td class="amt">Accumulated</td>';
                    } else if (role === 'lessor' && classification === 'finance') {
                        html += '<td>Month</td><td>Date</td><td class="amt">Payment Recv.</td><td class="amt">Finance Income</td><td class="amt">Capital</td><td class="amt">Net Investment</td>';
                    } else {
                        html += '<td>Month</td><td>Date</td><td class="amt">Payment</td><td class="amt">Interest</td><td class="amt">Capital</td><td class="amt">Liability</td><td class="amt">Dep.</td><td class="amt">ROU NBV</td>';
                    }
                    html += '</tr></thead><tbody>';

                    data.forEach(function(r) {
                        html += '<tr>';
                        html += '<td>' + r.month + '</td>';
                        html += '<td style="white-space:nowrap;">' + r.date + '</td>';

                        if (role === 'lessor' && classification === 'operating') {
                            html += '<td class="amt" style="color:#15803d;">' + num(r.rental_income) + '</td>';
                            html += '<td class="amt">' + num(r.accumulated_income) + '</td>';
                        } else if (role === 'lessor' && classification === 'finance') {
                            html += '<td class="amt">' + num(r.payment_received) + '</td>';
                            html += '<td class="amt" style="color:#15803d;">' + num(r.finance_income) + '</td>';
                            html += '<td class="amt">' + num(r.capital_repayment) + '</td>';
                            html += '<td class="amt" style="color:#15803d;">' + num(r.net_investment_balance) + '</td>';
                        } else {
                            html += '<td class="amt">' + num(r.payment) + '</td>';
                            html += '<td class="amt" style="color:#92400e;">' + num(r.interest) + '</td>';
                            html += '<td class="amt">' + num(r.capital) + '</td>';
                            html += '<td class="amt" style="color:#1d4ed8;">' + num(r.liability_balance) + '</td>';
                            html += '<td class="amt" style="color:#92400e;">' + num(r.depreciation) + '</td>';
                            html += '<td class="amt" style="font-weight:800;">' + num(r.rou_nbv) + '</td>';
                        }
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                    content.innerHTML = html;
                });
        }

        function num(v) { return parseFloat(v).toLocaleString('en-ZA', {minimumFractionDigits:2, maximumFractionDigits:2}); }

        // ── Real-time posting status via Reverb ──────────────────
        const journalStatusStyle = {
            pending: { label: 'Journal pending', color: '#92400e' },
            posted:  { label: 'Journal posted',  color: '#065f46' },
            failed:  { label: 'Journal failed',  color: '#b91c1c' },
        };

        function showToast(msg, color) {
            const t = document.createElement('div');
            t.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;padding:6pt 10pt;border-radius:6px;font-size:0.8rem;font-weight:600;color:#fff;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:opacity 0.4s;background:' + color;
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 4000);
        }

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

        window.addEventListener('echo:ready', function () {
            window.Echo.private('company.{{ $company->id }}')
                .listen('.posting.status.updated', (e) => {
                    if (e.entity_type !== 'lease_event') return;
                    const row = document.querySelector('tr[data-event-id="' + e.entity_id + '"]');
                    if (row) {
                        const cell = row.querySelector('[data-journal-status]');
                        if (cell) {
                            const js = journalStatusStyle[e.status] || journalStatusStyle.pending;
                            cell.textContent = js.label;
                            cell.style.color = js.color;
                        }
                        if (e.status !== 'failed') {
                            row.querySelector('.retry-posting-btn')?.remove();
                        }
                    }
                    if (e.status === 'posted' || e.status === 'failed') {
                        const toast = document.createElement('div');
                        toast.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;padding:6pt 10pt;border-radius:6px;font-size:0.8rem;font-weight:600;color:#fff;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:opacity 0.4s;background:' + (e.status==='posted'?'#065f46':'#b91c1c');
                        toast.textContent = e.label;
                        document.body.appendChild(toast);
                        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 400); }, 4000);
                    }
                });
        });
    </script>
@endsection
