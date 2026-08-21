@extends('layouts.public')

@section('title', $company->registered_name . ' — Investments & Subsidiaries Register')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .inv-mgmt-bar {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;
        }
        .inv-mgmt-bar a {
            font-size: 0.78rem; color: #5a7186; text-decoration: none;
            display: inline-flex; align-items: center; gap: 0.3rem; transition: color 0.15s;
        }
        .inv-mgmt-bar a:hover { color: #000; }
        .mgmt-btn {
            display: inline-flex; align-items: center; gap: 0.4rem;
            background: #fff; border: 1px solid #000; color: #000;
            font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.06em; padding: 0.4rem 0.85rem; text-decoration: none;
            cursor: pointer; font-family: inherit; transition: background 0.15s, color 0.15s;
        }
        .mgmt-btn:hover { background: #000; color: #fff; }
        .mgmt-btn.primary { background: #000; color: #fff; }
        .mgmt-btn.primary:hover { background: #1a345b; }
        .cust-doc {
            background: #fff; border: 1px solid #d3e2f5;
            font-family:'Century Gothic','URW Gothic','Avant Garde',Futura,'Avenir Next',Avenir,'Trebuchet MS',Helvetica,Arial,'DejaVu Sans',sans-serif;
            color: #000; font-size: 0.78rem; line-height: 1.45; margin-bottom: 1.5rem;
        }
        .cust-doc-body { padding: 2rem 2.25rem; }
        .doc-title { font-size: 1.3rem; font-weight: 800; letter-spacing: 0.04em; margin-bottom: 0.25rem; }
        .divider { border: none; border-top: 2px solid #000; margin: 1rem 0 1.25rem; }
        table.cust-items-table { width: 100%; border-collapse: collapse; }
        table.cust-items-table thead td {
            font-weight: 700; font-size: 0.7rem; text-transform: uppercase;
            letter-spacing: 0.04em; border-bottom: 1.5px solid #000; padding-bottom: 0.45rem;
        }
        table.cust-items-table thead td.amt { text-align: right; }
        table.cust-items-table tbody td {
            padding: 0.55rem 0; font-size: 0.78rem; border-bottom: 1px solid #d3e2f5; vertical-align: middle;
        }
        table.cust-items-table tbody td.amt {
            text-align: right; font-family: 'Courier New', monospace; white-space: nowrap;
        }
        table.cust-items-table tbody tr:last-child td { border-bottom: none; }
        table.cust-items-table tbody tr:hover td { background: #f7fbfd; }
        table.cust-items-table tfoot td {
            padding: 0.55rem 0; font-size: 0.78rem; font-weight: 800; border-top: 1.5px solid #000;
        }
        table.cust-items-table tfoot td.amt { text-align: right; font-family: 'Courier New', monospace; }
        .status-box {
            display: inline-block; font-weight: 700; text-transform: uppercase;
            border: 1px solid #000; padding: 0.08rem 0.5rem; font-size: 0.7rem; letter-spacing: 0.08em;
        }
        .status-box.disposed { color: #dc2626; border-color: #dc2626; }
        .row-link { color: #000; font-weight: 700; text-decoration: none; border-bottom: 1px solid #000; }
        .row-link:hover { border-bottom-color: transparent; }
        .del-btn {
            background: none; border: none; font-size: 0.72rem; color: #dc2626;
            text-decoration: underline; cursor: pointer; font-family: inherit; padding: 0;
        }
        .del-btn:hover { color: #991b1c; }
        .add-panel { margin-top: 1rem; }
        .add-panel-head {
            display: inline-flex; align-items: center; gap: 0.4rem;
            font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.06em; cursor: pointer; background: none;
            border: 1px solid #000; padding: 0.35rem 0.75rem; font-family: inherit;
            transition: background 0.15s, color 0.15s; color: #000;
        }
        .add-panel-head:hover { background: #000; color: #fff; }
        .add-panel-body {
            display: none; margin-top: 0.75rem; padding: 1rem 1.25rem;
            border: 1px solid #d3e2f5; background: #f7fbfd;
        }
        .add-panel.open .add-panel-body { display: block; }
        .af-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem 0.75rem; align-items: end; margin-bottom: 0.65rem; }
        .af-field.wide { grid-column: span 2; }
        .af-field label { display: block; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #5a7186; margin-bottom: 0.2rem; }
        .af-field input, .af-field select { width: 100%; border: 1px solid #d3e2f5; padding: 0.32rem 0.5rem; font-size: 0.78rem; font-family: inherit; color: #000; box-sizing: border-box; background: #fff; }
        .af-field input:focus, .af-field select:focus { outline: none; border-color: #000; }
        .af-submit { display: flex; justify-content: flex-end; margin-top: 0.5rem; }
        @media (max-width: 640px) { .cust-doc-body { padding: 1.25rem 1rem; } }
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

                <div class="inv-mgmt-bar">
                    <span style="font-size:0.78rem;color:#5a7186;">
                        {{ $subsidiaries->count() }} investment{{ $subsidiaries->count() !== 1 ? 's' : '' }}
                    </span>
                    <div style="display:flex;align-items:center;gap:0.65rem;flex-wrap:wrap;">
                        @if ($company->isGroupParent())
                            <a href="{{ route('companies.group.balance-sheet', $company) }}" class="mgmt-btn" style="text-decoration:none;">Consolidated SFP</a>
                            <a href="{{ route('companies.group.income-statement', $company) }}" class="mgmt-btn" style="text-decoration:none;">Consolidated P&L</a>
                        @endif
                        <button type="button" class="mgmt-btn primary" onclick="document.getElementById('panel-add-sub').classList.toggle('open');document.getElementById('panel-add-sub').scrollIntoView({behavior:'smooth',block:'nearest'})">
                            + Add Investment
                        </button>
                    </div>
                </div>

                <div class="cust-doc">
                    <div class="cust-doc-body">
                        <div class="doc-title">Investments & Subsidiaries</div>
                        <p style="font-size:0.78rem;color:#5a7186;margin:0.2rem 0 0.75rem;">
                            Entities in which {{ $company->registered_name }} holds an interest. The relationship type and
                            accounting treatment are auto-classified based on ownership percentage.
                        </p>

                        <hr class="divider">

                        @if ($subsidiaries->isEmpty())
                            <p style="color:#6f869b;font-style:italic;font-size:0.78rem;">No subsidiaries yet. Add your first subsidiary below.</p>
                        @else
                            @php
                                $totalInvestment = 0.0;
                                $totalEquityAcq  = 0.0;
                                $totalGoodwill   = 0.0;
                            @endphp
                            <div style="overflow-x:auto;">
                                <table class="cust-items-table">
                                    <thead>
                                        <tr>
                                            <td style="width:20%;">Entity</td>
                                            <td style="width:8%;">Holding %</td>
                                            <td style="width:12%;">Relationship</td>
                                            <td style="width:9%;">Standard</td>
                                            <td style="width:10%;">Acquired</td>
                                            <td class="amt" style="width:13%;">Investment</td>
                                            <td class="amt" style="width:13%;">Goodwill</td>
                                            <td style="width:7%;text-align:center;">Status</td>
                                            <td style="width:4%;"></td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($subsidiaries as $sub)
                                            @php
                                                $inv = (float) ($sub->investment_cost ?? 0);
                                                $eqa = (float) ($sub->equity_at_acquisition ?? 0);
                                                $own = (float) ($sub->group_ownership_percentage ?? 0);
                                                $gw  = $inv - ($eqa * $own / 100);
                                                $disposed = $sub->control_lost_date !== null;
                                                $relInfo = $sub->relationship_info;
                                                $relType = $sub->effective_relationship_type;
                                                if (! $disposed) {
                                                    $totalInvestment += $inv;
                                                    $totalGoodwill   += max(0, $gw);
                                                }
                                                $relColors = [
                                                    'subsidiary'   => ['bg' => '#dcfce7', 'fg' => '#15803d'],
                                                    'associate'    => ['bg' => '#dbeafe', 'fg' => '#1d4ed8'],
                                                    'joint_venture'=> ['bg' => '#fef3c7', 'fg' => '#92400e'],
                                                    'investment'   => ['bg' => '#f4fafc', 'fg' => '#191919'],
                                                ];
                                                $rc = $relColors[$relType] ?? $relColors['investment'];
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a class="row-link" href="{{ route('companies.group.subsidiaries.show', [$company, $sub]) }}">
                                                        {{ $sub->registered_name }}
                                                    </a>
                                                    <div style="font-size:0.72rem;color:#6f869b;">{{ $sub->company_type_label }}</div>
                                                </td>
                                                <td>{{ $own ? rtrim(rtrim(number_format($own, 2), '0'), '.') . '%' : '—' }}</td>
                                                <td>
                                                    <span style="font-size:0.7rem;font-weight:700;padding:0.12rem 0.45rem;background:{{ $rc['bg'] }};color:{{ $rc['fg'] }};letter-spacing:0.04em;white-space:nowrap;">
                                                        {{ $relInfo['label'] }}
                                                    </span>
                                                </td>
                                                <td style="font-size:0.72rem;color:#5a7186;">{{ $relInfo['standard'] }}</td>
                                                <td style="font-size:0.72rem;white-space:nowrap;">
                                                    {{ $sub->acquisition_date ? \Carbon\Carbon::parse($sub->acquisition_date)->format('d M Y') : '—' }}
                                                </td>
                                                <td class="amt">{{ $inv ? number_format($inv, 2) : '—' }}</td>
                                                <td class="amt" style="font-weight:800;">{{ ($relType === 'subsidiary' && $gw != 0) ? number_format(max(0, $gw), 2) : '—' }}</td>
                                                <td style="text-align:center;">
                                                    <span class="status-box {{ $disposed ? 'disposed' : '' }}">
                                                        {{ $disposed ? 'Disposed' : ($relType === 'subsidiary' ? 'Controlled' : 'Active') }}
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <form method="POST"
                                                        action="{{ route('companies.group.subsidiaries.remove', [$company, $sub]) }}"
                                                        onsubmit="return false" data-confirm-label="Group Structure" data-confirm-title="Remove Subsidiary" data-confirm-body="Remove {{ addslashes($sub->registered_name) }} from the group?" data-confirm-text="Remove" data-confirm-danger="1">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="del-btn">Remove</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    @if ($subsidiaries->where('control_lost_date', null)->count() > 0)
                                        <tfoot>
                                            <tr>
                                                <td colspan="5">Total (active investments)</td>
                                                <td class="amt">{{ number_format($totalInvestment, 2) }}</td>
                                                <td class="amt">{{ number_format($totalGoodwill, 2) }}</td>
                                                <td colspan="2"></td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        @endif

                        <div class="add-panel" id="panel-add-sub" style="margin-top:1.25rem;">
                            <button type="button" class="add-panel-head" onclick="this.closest('.add-panel').classList.toggle('open')">
                                + Add Investment
                            </button>
                            <div class="add-panel-body">
                                @if ($available->isEmpty())
                                    <p style="font-size:0.82rem;color:#888;margin:0;">
                                        No eligible companies available. A subsidiary must be one of your companies
                                        that is not already part of a group and is not itself a group parent.
                                    </p>
                                @else
                                    <form method="POST" action="{{ route('companies.group.subsidiaries.add', $company) }}" x-data="addInvestmentForm()">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field wide">
                                                <label>Company</label>
                                                <select name="subsidiary_id" required>
                                                    <option value="">Select company...</option>
                                                    @foreach ($available as $opt)
                                                        <option value="{{ $opt->id }}">{{ $opt->registered_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="af-field">
                                                <label>Holding %</label>
                                                <input type="number" step="0.01" min="0.01" max="100" name="group_ownership_percentage"
                                                    x-model="ownership" @input="updateClassification()" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Acquisition date</label>
                                                <input type="date" name="acquisition_date" value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                        </div>
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Investment cost (R)</label>
                                                <input type="number" step="0.01" min="0" name="investment_cost" placeholder="0.00">
                                            </div>
                                            <div class="af-field">
                                                <label>Equity at acquisition (R)</label>
                                                <input type="number" step="0.01" name="equity_at_acquisition" placeholder="0.00">
                                            </div>
                                            <div class="af-field" style="display:flex;align-items:center;gap:0.5rem;padding-top:1.1rem;">
                                                <input type="checkbox" name="is_joint_venture" value="1" id="jv-check"
                                                    x-model="isJv" @change="updateClassification()"
                                                    style="width:auto;margin:0;">
                                                <label for="jv-check" style="margin:0;font-size:0.72rem;font-weight:600;text-transform:none;letter-spacing:0;color:#000;cursor:pointer;">Joint venture (shared control)</label>
                                            </div>
                                        </div>
                                        <div style="margin:0.5rem 0 0.75rem;padding:0.5rem 0.75rem;border-left:3px solid;font-size:0.72rem;"
                                            :style="'border-color:' + classColor + ';background:' + classBg">
                                            <strong x-text="classLabel"></strong>
                                            <span style="color:#5a7186;"> — <span x-text="classStandard"></span>, <span x-text="classMethod"></span></span>
                                        </div>
                                        <div class="af-submit">
                                            <button type="submit" class="mgmt-btn primary">Add Investment</button>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        @if ($errors->any())
            document.getElementById('panel-add-sub').classList.add('open');
        @endif

        function addInvestmentForm() {
            return {
                ownership: 100,
                isJv: false,
                classLabel: 'Subsidiary',
                classStandard: 'IFRS 10',
                classMethod: 'Full Consolidation',
                classColor: '#15803d',
                classBg: '#f0fdf4',
                updateClassification() {
                    var o = parseFloat(this.ownership) || 0;
                    if (this.isJv) {
                        this.classLabel = 'Joint Venture'; this.classStandard = 'IAS 28'; this.classMethod = 'Equity Method';
                        this.classColor = '#92400e'; this.classBg = '#fffbeb';
                    } else if (o > 50) {
                        this.classLabel = 'Subsidiary'; this.classStandard = 'IFRS 10'; this.classMethod = 'Full Consolidation';
                        this.classColor = '#15803d'; this.classBg = '#f0fdf4';
                    } else if (o >= 20) {
                        this.classLabel = 'Associate'; this.classStandard = 'IAS 28'; this.classMethod = 'Equity Method';
                        this.classColor = '#1d4ed8'; this.classBg = '#eff6ff';
                    } else {
                        this.classLabel = 'Financial Investment'; this.classStandard = 'IFRS 9'; this.classMethod = 'Fair Value';
                        this.classColor = '#191919'; this.classBg = '#f7fbfd';
                    }
                }
            };
        }
    </script>
@endsection
