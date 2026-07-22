@extends('layouts.public')

@section('title', $subsidiary->registered_name . ' — Subsidiary')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .inv-mgmt-bar {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;
        }
        .inv-mgmt-bar a, .inv-mgmt-bar .mgmt-back {
            font-size: 0.78rem; color: #6b7280; text-decoration: none;
            display: inline-flex; align-items: center; gap: 0.3rem; transition: color 0.15s;
            background: none; border: none; cursor: pointer; font-family: inherit;
        }
        .inv-mgmt-bar a:hover, .inv-mgmt-bar .mgmt-back:hover { color: #000; }
        .mgmt-btn {
            display: inline-flex; align-items: center; gap: 0.4rem;
            background: #fff; border: 1px solid #000; color: #000;
            font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.06em; padding: 0.4rem 0.85rem; text-decoration: none;
            cursor: pointer; font-family: inherit; transition: background 0.15s, color 0.15s;
        }
        .mgmt-btn:hover { background: #000; color: #fff; }
        .mgmt-btn.primary { background: #000; color: #fff; }
        .mgmt-btn.primary:hover { background: #333; }
        .mgmt-btn.danger { border-color: #dc2626; color: #dc2626; }
        .mgmt-btn.danger:hover { background: #dc2626; color: #fff; }
        .cust-doc {
            background: #fff; border: 1px solid #ddd;
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            color: #000; font-size: 0.78rem; line-height: 1.45; margin-bottom: 1.5rem;
        }
        .cust-doc-body { padding: 2rem 2.25rem; }
        .cust-header-table { width: 100%; border-collapse: collapse; margin-bottom: 0.75rem; }
        .doc-title { font-size: 1.3rem; font-weight: 800; text-align: right; margin-bottom: 0.25rem; letter-spacing: 0.04em; }
        .doc-meta-line { text-align: right; font-size: 0.78rem; }
        .status-box {
            display: inline-block; font-weight: 700; text-transform: uppercase;
            border: 1px solid #000; padding: 0.08rem 0.5rem; font-size: 0.62rem;
            letter-spacing: 0.08em; margin-top: 0.35rem;
        }
        .status-box.disposed { color: #dc2626; border-color: #dc2626; }
        .divider { border: none; border-top: 2px solid #000; margin: 1rem 0 1.25rem; }
        .divider.light { border-top: 1px solid #ddd; margin: 1.25rem 0; }
        .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 0.25rem; }
        .summary-table td { padding: 0 1.25rem 0 0; font-size: 0.78rem; vertical-align: top; }
        .summary-table .lbl { display: block; font-weight: 700; font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.15rem; }
        .summary-table .amt { font-size: 0.95rem; font-weight: 800; }
        .section-header {
            font-weight: 700; font-size: 0.85rem; text-transform: uppercase;
            letter-spacing: 0.06em; border-bottom: 1.5px solid #000;
            padding-bottom: 0.25rem; margin-bottom: 0.5rem;
        }
        .info-section { margin-top: 1.5rem; }
        table.cust-items-table { width: 100%; border-collapse: collapse; }
        table.cust-items-table thead td {
            font-weight: 700; font-size: 0.7rem; text-transform: uppercase;
            letter-spacing: 0.04em; border-bottom: 1.5px solid #000; padding-bottom: 0.45rem;
        }
        table.cust-items-table thead td.amt { text-align: right; }
        table.cust-items-table tbody td {
            padding: 0.55rem 0; font-size: 0.78rem; border-bottom: 1px solid #ddd; vertical-align: middle;
        }
        table.cust-items-table tbody td.amt { text-align: right; font-family: 'Courier New', monospace; white-space: nowrap; }
        table.cust-items-table tbody tr:last-child td { border-bottom: none; }
        table.cust-items-table tbody tr:hover td { background: #fafafa; }
        .ev-chip {
            display: inline-block; font-size: 0.58rem; font-weight: 800; letter-spacing: 0.05em;
            text-transform: uppercase; padding: 0.1rem 0.45rem; border-radius: 2px; white-space: nowrap;
        }
        .ev-chip.acquisition { background: #dcfce7; color: #15803d; }
        .ev-chip.increase { background: #dbeafe; color: #1d4ed8; }
        .ev-chip.decrease { background: #fef3c7; color: #92400e; }
        .ev-chip.disposal { background: #fee2e2; color: #dc2626; }
        .action-section { margin-top: 1.5rem; }
        .action-panel { border-bottom: 1px solid #eee; }
        .action-panel-head {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.65rem 0; cursor: pointer; user-select: none;
        }
        .action-panel-title { font-size: 0.82rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; }
        .action-panel-body { display: none; padding-bottom: 1rem; }
        .action-panel.open .action-panel-body { display: block; }
        .action-panel-chevron { font-size: 0.65rem; color: #9ca3af; transition: transform 0.15s; }
        .action-panel.open .action-panel-chevron { transform: rotate(180deg); }
        .af-row { display: flex; flex-wrap: wrap; gap: 0.5rem 0.75rem; align-items: flex-end; margin-bottom: 0.65rem; }
        .af-field { flex: 1; min-width: 130px; }
        .af-field.wide { flex: 2; min-width: 200px; }
        .af-field.full { flex-basis: 100%; }
        .af-field label { display: block; font-size: 0.58rem; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #555; margin-bottom: 0.2rem; }
        .af-field input, .af-field select, .af-field textarea { width: 100%; border: 1px solid #ccc; padding: 0.32rem 0.5rem; font-size: 0.78rem; font-family: inherit; color: #000; box-sizing: border-box; background: #fff; }
        .af-field input:focus, .af-field select:focus, .af-field textarea:focus { outline: none; border-color: #000; }
        .af-hint { font-size: 0.65rem; color: #888; margin-bottom: 0.5rem; line-height: 1.4; }
        .del-btn {
            background: none; border: none; font-size: 0.72rem; color: #dc2626;
            text-decoration: underline; cursor: pointer; font-family: inherit; padding: 0;
        }
        .del-btn:hover { color: #991b1c; }
        @media (max-width: 640px) {
            .cust-doc-body { padding: 1.25rem 1rem; }
            .cust-header-table, .cust-header-table tr, .cust-header-table td { display: block; width: 100%!important; text-align: left!important; }
            .doc-title, .doc-meta-line { text-align: left!important; }
            .summary-table, .summary-table tr, .summary-table td { display: block; width: 100%!important; padding: 0 0 0.75rem; }
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
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:0.75rem 1.25rem;font-size:0.855rem;font-weight:600;margin-bottom:1.25rem;">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:0.75rem 1.25rem;font-size:0.855rem;font-weight:600;margin-bottom:1.25rem;">
                        {{ session('error') }}
                    </div>
                @endif

                @php
                    $inv = (float) ($subsidiary->investment_cost ?? 0);
                    $eqa = (float) ($subsidiary->equity_at_acquisition ?? 0);
                    $own = (float) ($subsidiary->group_ownership_percentage ?? 0);
                    $gw  = $inv - ($eqa * $own / 100);
                    $nci = 100 - $own;
                    $relInfo = $subsidiary->relationship_info;
                    $relType = $subsidiary->effective_relationship_type;
                    $relColors = [
                        'subsidiary'   => ['bg' => '#dcfce7', 'fg' => '#15803d'],
                        'associate'    => ['bg' => '#dbeafe', 'fg' => '#1d4ed8'],
                        'joint_venture'=> ['bg' => '#fef3c7', 'fg' => '#92400e'],
                        'investment'   => ['bg' => '#f3f4f6', 'fg' => '#374151'],
                    ];
                    $rc = $relColors[$relType] ?? $relColors['investment'];
                @endphp

                {{-- Management bar --}}
                <div class="inv-mgmt-bar">
<div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                        @unless ($isDisposed)
                            <button type="button" class="mgmt-btn" onclick="togglePanel('increase')">Increase Holding</button>
                            <button type="button" class="mgmt-btn" onclick="togglePanel('decrease')">Decrease Holding</button>
                            <button type="button" class="mgmt-btn danger" onclick="togglePanel('dispose')">Dispose</button>
                        @endunless
                        <button type="button" class="mgmt-btn" onclick="togglePanel('eliminate')">+ Elimination</button>
                        <button type="button" class="mgmt-btn primary" onclick="togglePanel('edit')">Edit</button>
                    </div>
                </div>

                {{-- Document --}}
                <div class="cust-doc">
                    <div class="cust-doc-body">

                        {{-- Header --}}
                        <table class="cust-header-table">
                            <tr>
                                <td style="vertical-align:top;width:55%;">
                                    <div style="font-size:1.3rem;font-weight:800;letter-spacing:-0.01em;">{{ $subsidiary->registered_name }}</div>
                                    @if ($subsidiary->trading_name && $subsidiary->trading_name !== $subsidiary->registered_name)
                                        <div style="font-size:0.78rem;color:#555;margin-top:0.2rem;">t/a {{ $subsidiary->trading_name }}</div>
                                    @endif
                                </td>
                                <td style="vertical-align:top;width:45%;">
                                    <div class="doc-title">{{ strtoupper($relInfo['label']) }} PROFILE</div>
                                    <div class="doc-meta-line">
                                        <span style="font-size:0.62rem;font-weight:700;padding:0.1rem 0.4rem;background:{{ $rc['bg'] }};color:{{ $rc['fg'] }};letter-spacing:0.04em;">{{ $relInfo['label'] }}</span>
                                        <span style="color:#555;margin-left:0.3rem;">{{ $relInfo['standard'] }} — {{ $relInfo['method'] }}</span>
                                    </div>
                                    <div class="doc-meta-line">Type: <strong>{{ $subsidiary->company_type_label }}</strong></div>
                                    <div class="doc-meta-line">Holding: <strong>{{ $own ? rtrim(rtrim(number_format($own, 2), '0'), '.') . '%' : '—' }}</strong></div>
                                    @if ($relType === 'subsidiary')
                                        <div class="doc-meta-line">NCI: <strong>{{ $nci > 0 ? rtrim(rtrim(number_format($nci, 2), '0'), '.') . '%' : '—' }}</strong></div>
                                    @endif
                                    <div style="text-align:right;">
                                        <span class="status-box {{ $isDisposed ? 'disposed' : '' }}">
                                            {{ $isDisposed ? 'Disposed' : ($relType === 'subsidiary' ? 'Controlled' : 'Active') }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        {{-- Meta --}}
                        <table style="width:100%;border-collapse:collapse;font-size:0.78rem;margin-bottom:0.5rem;">
                            <tr>
                                <td style="vertical-align:top;width:50%;">
                                    <div><span style="font-weight:700;display:inline-block;width:150px;">Acquisition date</span>{{ $subsidiary->acquisition_date ? \Carbon\Carbon::parse($subsidiary->acquisition_date)->format('d M Y') : '—' }}</div>
                                    <div><span style="font-weight:700;display:inline-block;width:150px;">Control acquired</span>{{ $subsidiary->control_acquired_date ? \Carbon\Carbon::parse($subsidiary->control_acquired_date)->format('d M Y') : '—' }}</div>
                                    @if ($isDisposed)
                                        <div><span style="font-weight:700;display:inline-block;width:150px;">Control lost</span>{{ \Carbon\Carbon::parse($subsidiary->control_lost_date)->format('d M Y') }}</div>
                                    @endif
                                </td>
                                <td style="vertical-align:top;width:50%;text-align:right;">
                                    <div>Registration: <strong>{{ $subsidiary->registration_number ?? '—' }}</strong></div>
                                    <div>Tax number: <strong>{{ $subsidiary->tax_number ?? '—' }}</strong></div>
                                </td>
                            </tr>
                        </table>

                        <hr class="divider">

                        {{-- Summary cards --}}
                        <table class="summary-table">
                            <tr>
                                <td>
                                    <span class="lbl">Investment cost</span>
                                    <span class="amt">R {{ number_format($inv, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Accounting standard</span>
                                    <span class="amt" style="font-size:0.82rem;">{{ $relInfo['standard'] }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Method</span>
                                    <span class="amt" style="font-size:0.82rem;">{{ $relInfo['method'] }}</span>
                                </td>
                                @if (in_array($relType, ['subsidiary', 'associate', 'joint_venture']))
                                    <td>
                                        <span class="lbl">Equity at acquisition</span>
                                        <span class="amt">R {{ number_format($eqa, 2) }}</span>
                                    </td>
                                @endif
                                @if ($relType === 'subsidiary' && $gw >= 0)
                                    <td>
                                        <span class="lbl">Goodwill</span>
                                        <span class="amt">R {{ number_format($gw, 2) }}</span>
                                    </td>
                                @endif
                                @if ($relType === 'subsidiary' && $gw < 0)
                                    <td>
                                        <span class="lbl">Bargain purchase</span>
                                        <span class="amt">R {{ number_format(abs($gw), 2) }}</span>
                                    </td>
                                @endif
                            </tr>
                        </table>

                        {{-- Ownership Event History --}}
                        <div class="info-section">
                            <div class="section-header">Ownership Event History</div>
                            @if ($events->isEmpty())
                                <p style="color:#999;font-size:0.78rem;font-style:italic;padding:0.75rem 0;">No ownership events recorded yet.</p>
                            @else
                                <table class="cust-items-table">
                                    <thead>
                                        <tr>
                                            <td style="width:12%;">Date</td>
                                            <td style="width:15%;">Type</td>
                                            <td style="width:10%;">Before</td>
                                            <td style="width:10%;">After</td>
                                            <td class="amt" style="width:15%;">Consideration</td>
                                            <td class="amt" style="width:15%;">Equity at event</td>
                                            <td style="width:16%;">Notes</td>
                                            <td style="width:7%;"></td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($events as $event)
                                            <tr>
                                                <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($event->event_date)->format('d M Y') }}</td>
                                                <td>
                                                    <span class="ev-chip {{ $event->type }}">{{ $event->type }}</span>
                                                </td>
                                                <td>{{ rtrim(rtrim(number_format((float) $event->ownership_before, 2), '0'), '.') }}%</td>
                                                <td>{{ rtrim(rtrim(number_format((float) $event->ownership_after, 2), '0'), '.') }}%</td>
                                                <td class="amt">{{ number_format((float) ($event->consideration ?? 0), 2) }}</td>
                                                <td class="amt">{{ $event->equity_at_event ? number_format((float) $event->equity_at_event, 2) : '—' }}</td>
                                                <td style="font-size:0.72rem;color:#555;">{{ $event->notes ?? '—' }}</td>
                                                <td style="text-align:right;">
                                                    <form method="POST"
                                                        action="{{ route('companies.group.ownership-events.destroy', [$company, $event]) }}"
                                                        onsubmit="return false" data-confirm-label="Group Accounting" data-confirm-title="Remove Ownership Event" data-confirm-body="This ownership event will be removed." data-confirm-text="Remove" data-confirm-danger="1">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="del-btn">Remove</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>

                        {{-- Eliminations --}}
                        <div class="info-section">
                            <div class="section-header">Intragroup Eliminations</div>
                            @if ($eliminations->isEmpty())
                                <p style="color:#999;font-size:0.78rem;font-style:italic;padding:0.75rem 0;">No elimination journals recorded.</p>
                            @else
                                <table class="cust-items-table">
                                    <thead>
                                        <tr>
                                            <td style="width:12%;">Date</td>
                                            <td style="width:14%;">Type</td>
                                            <td style="width:30%;">Description</td>
                                            <td class="amt" style="width:14%;">Debit total</td>
                                            <td class="amt" style="width:14%;">Credit total</td>
                                            <td style="width:8%;">Lines</td>
                                            <td style="width:8%;"></td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($eliminations as $elim)
                                            @php
                                                $drTotal = $elim->lines->sum('debit');
                                                $crTotal = $elim->lines->sum('credit');
                                            @endphp
                                            <tr>
                                                <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($elim->elimination_date)->format('d M Y') }}</td>
                                                <td style="font-size:0.72rem;">{{ \App\Models\GroupElimination::TYPES[$elim->type] ?? $elim->type }}</td>
                                                <td>{{ $elim->description }}</td>
                                                <td class="amt">{{ number_format($drTotal, 2) }}</td>
                                                <td class="amt">{{ number_format($crTotal, 2) }}</td>
                                                <td style="text-align:center;">{{ $elim->lines->count() }}</td>
                                                <td style="text-align:right;">
                                                    <form method="POST"
                                                        action="{{ route('companies.group.eliminations.destroy', [$company, $elim]) }}"
                                                        onsubmit="return false" data-confirm-label="Group Accounting" data-confirm-title="Remove Elimination Journal" data-confirm-body="This elimination journal will be removed." data-confirm-text="Remove" data-confirm-danger="1">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="del-btn">Remove</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>

                        <hr class="divider light">

                        {{-- ══ Action Panels ══ --}}
                        <div class="action-section">

                            {{-- Increase Ownership --}}
                            <div class="action-panel" id="panel-increase">
                                <div class="action-panel-head" onclick="togglePanel('increase')">
                                    <div class="action-panel-title">
                                        <span style="color:#1d4ed8;">▲</span> Increase Ownership
                                    </div>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Record a step acquisition or additional share purchase that increases your holding but does not change control (IFRS 10.23).</p>
                                    <form method="POST" action="{{ route('companies.group.ownership-events.store', $company) }}">
                                        @csrf
                                        <input type="hidden" name="subsidiary_company_id" value="{{ $subsidiary->id }}">
                                        <input type="hidden" name="type" value="increase">
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Event date</label>
                                                <input type="date" name="event_date" value="{{ now()->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Ownership before (%)</label>
                                                <input type="number" name="ownership_before" step="0.01" min="0" max="100" value="{{ $own }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Ownership after (%)</label>
                                                <input type="number" name="ownership_after" step="0.01" min="0" max="100" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Consideration (R)</label>
                                                <input type="number" name="consideration" step="0.01" min="0" placeholder="0.00">
                                            </div>
                                        </div>
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Equity at event (R)</label>
                                                <input type="number" name="equity_at_event" step="0.01" placeholder="0.00">
                                            </div>
                                            <div class="af-field wide">
                                                <label>Notes</label>
                                                <input type="text" name="notes" placeholder="Optional description" maxlength="255">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;margin-top:0.5rem;">
                                            <button type="submit" class="mgmt-btn primary">Record Increase</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Decrease Ownership --}}
                            <div class="action-panel" id="panel-decrease">
                                <div class="action-panel-head" onclick="togglePanel('decrease')">
                                    <div class="action-panel-title">
                                        <span style="color:#92400e;">▼</span> Decrease Ownership
                                    </div>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Record a partial disposal that reduces your holding but retains control. The difference between consideration and carrying amount adjusts equity (IFRS 10.23).</p>
                                    <form method="POST" action="{{ route('companies.group.ownership-events.store', $company) }}">
                                        @csrf
                                        <input type="hidden" name="subsidiary_company_id" value="{{ $subsidiary->id }}">
                                        <input type="hidden" name="type" value="decrease">
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Event date</label>
                                                <input type="date" name="event_date" value="{{ now()->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Ownership before (%)</label>
                                                <input type="number" name="ownership_before" step="0.01" min="0" max="100" value="{{ $own }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Ownership after (%)</label>
                                                <input type="number" name="ownership_after" step="0.01" min="0" max="100" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Consideration (R)</label>
                                                <input type="number" name="consideration" step="0.01" min="0" placeholder="0.00">
                                            </div>
                                        </div>
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Equity at event (R)</label>
                                                <input type="number" name="equity_at_event" step="0.01" placeholder="0.00">
                                            </div>
                                            <div class="af-field wide">
                                                <label>Notes</label>
                                                <input type="text" name="notes" placeholder="Optional description" maxlength="255">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;margin-top:0.5rem;">
                                            <button type="submit" class="mgmt-btn primary">Record Decrease</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Dispose (loss of control) --}}
                            <div class="action-panel" id="panel-dispose">
                                <div class="action-panel-head" onclick="togglePanel('dispose')">
                                    <div class="action-panel-title">
                                        <span style="color:#dc2626;">✕</span> Dispose — Loss of Control
                                    </div>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Record the disposal of this subsidiary where control is lost. Any retained interest is remeasured to fair value at date of loss of control (IFRS 10.25).</p>
                                    <form method="POST" action="{{ route('companies.group.ownership-events.store', $company) }}">
                                        @csrf
                                        <input type="hidden" name="subsidiary_company_id" value="{{ $subsidiary->id }}">
                                        <input type="hidden" name="type" value="disposal">
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Disposal date</label>
                                                <input type="date" name="event_date" value="{{ now()->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Ownership before (%)</label>
                                                <input type="number" name="ownership_before" step="0.01" min="0" max="100" value="{{ $own }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Ownership after (%)</label>
                                                <input type="number" name="ownership_after" step="0.01" min="0" max="100" value="0" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Consideration (R)</label>
                                                <input type="number" name="consideration" step="0.01" min="0" placeholder="0.00" required>
                                            </div>
                                        </div>
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Equity at event (R)</label>
                                                <input type="number" name="equity_at_event" step="0.01" placeholder="0.00">
                                            </div>
                                            <div class="af-field">
                                                <label>Fair value of retained interest (R)</label>
                                                <input type="number" name="fair_value_retained" step="0.01" min="0" placeholder="0.00">
                                            </div>
                                            <div class="af-field">
                                                <label>Goodwill derecognised (R)</label>
                                                <input type="number" name="goodwill_derecognised" step="0.01" placeholder="0.00">
                                            </div>
                                        </div>
                                        <div class="af-row">
                                            <div class="af-field full">
                                                <label>Notes</label>
                                                <input type="text" name="notes" placeholder="Optional description" maxlength="255">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;margin-top:0.5rem;">
                                            <button type="submit" class="mgmt-btn danger">Record Disposal</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Record Elimination --}}
                            <div class="action-panel" id="panel-eliminate">
                                <div class="action-panel-head" onclick="togglePanel('eliminate')">
                                    <div class="action-panel-title">
                                        <span style="color:#7c3aed;">⇄</span> Record Elimination Journal
                                    </div>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Record a manual intragroup elimination journal entry for consolidation (intercompany sales, dividends, loans, etc.).</p>
                                    <form method="POST" action="{{ route('companies.group.eliminations.store', $company) }}" x-data="elimForm()">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Elimination date</label>
                                                <input type="date" name="elimination_date" value="{{ now()->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Type</label>
                                                <select name="type" required>
                                                    @foreach (\App\Models\GroupElimination::TYPES as $key => $label)
                                                        <option value="{{ $key }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="af-field wide">
                                                <label>Description</label>
                                                <input type="text" name="description" placeholder="e.g. Eliminate intercompany revenue/COGS" required maxlength="255">
                                            </div>
                                        </div>

                                        <div style="margin-top:0.75rem;">
                                            <div style="font-size:0.62rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#555;margin-bottom:0.4rem;">Journal Lines</div>
                                            <template x-for="(line, idx) in lines" :key="idx">
                                                <div class="af-row">
                                                    <div class="af-field">
                                                        <label>Bucket</label>
                                                        <select :name="'lines['+idx+'][bucket]'" required>
                                                            @foreach (\App\Models\GroupEliminationLine::BUCKETS as $bk => $bl)
                                                                <option value="{{ $bk }}">{{ $bl['label'] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="af-field wide">
                                                        <label>Label</label>
                                                        <input type="text" :name="'lines['+idx+'][label]'" placeholder="Optional line label" maxlength="255">
                                                    </div>
                                                    <div class="af-field">
                                                        <label>Debit (R)</label>
                                                        <input type="number" :name="'lines['+idx+'][debit]'" step="0.01" min="0" placeholder="0.00">
                                                    </div>
                                                    <div class="af-field">
                                                        <label>Credit (R)</label>
                                                        <input type="number" :name="'lines['+idx+'][credit]'" step="0.01" min="0" placeholder="0.00">
                                                    </div>
                                                </div>
                                            </template>
                                            <button type="button" @click="lines.push({})" style="font-size:0.72rem;font-weight:700;border:1px dashed #ccc;background:none;padding:0.35rem 0.75rem;cursor:pointer;font-family:inherit;margin-top:0.25rem;">+ Add Line</button>
                                        </div>

                                        <div style="display:flex;justify-content:flex-end;margin-top:0.75rem;">
                                            <button type="submit" class="mgmt-btn primary">Record Elimination</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Edit Subsidiary --}}
                            <div class="action-panel" id="panel-edit">
                                <div class="action-panel-head" onclick="togglePanel('edit')">
                                    <div class="action-panel-title">
                                        <span style="color:#555;">✎</span> Edit Subsidiary Details
                                    </div>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <form method="POST" action="{{ route('companies.group.subsidiaries.update', [$company, $subsidiary]) }}">
                                        @csrf @method('PATCH')
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Holding %</label>
                                                <input type="number" name="group_ownership_percentage" step="0.01" min="0.01" max="100" value="{{ $own }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Acquisition date</label>
                                                <input type="date" name="acquisition_date" value="{{ $subsidiary->acquisition_date ? \Carbon\Carbon::parse($subsidiary->acquisition_date)->format('Y-m-d') : '' }}">
                                            </div>
                                            <div class="af-field">
                                                <label>Investment cost (R)</label>
                                                <input type="number" name="investment_cost" step="0.01" min="0" value="{{ $inv }}">
                                            </div>
                                            <div class="af-field">
                                                <label>Equity at acquisition (R)</label>
                                                <input type="number" name="equity_at_acquisition" step="0.01" value="{{ $eqa }}">
                                            </div>
                                        </div>
                                        <div class="af-row">
                                            <div class="af-field" style="display:flex;align-items:center;gap:0.5rem;">
                                                <input type="hidden" name="is_joint_venture" value="0">
                                                <input type="checkbox" name="is_joint_venture" value="1" id="edit-jv-check"
                                                    {{ $subsidiary->is_joint_venture ? 'checked' : '' }}
                                                    style="width:auto;margin:0;">
                                                <label for="edit-jv-check" style="margin:0;font-size:0.72rem;font-weight:600;text-transform:none;letter-spacing:0;color:#000;cursor:pointer;">Joint venture (shared control)</label>
                                            </div>
                                        </div>
                                        <p class="af-hint">Relationship type will be auto-classified based on ownership % and joint venture flag.</p>
                                        <div style="display:flex;justify-content:flex-end;margin-top:0.5rem;">
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

        function elimForm() {
            return { lines: [{}] };
        }
    </script>
@endsection
