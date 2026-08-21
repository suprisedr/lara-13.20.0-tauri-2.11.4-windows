@extends('layouts.public')

@section('title', $company->registered_name . ' — Trial Balance')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .co-topbar { padding: 1rem 2rem; }
        .co-main   { padding: 1.25rem 1.5rem; }
        .co-card   { margin-bottom: 0; }
        .co-card-head { padding: 0.875rem 1.25rem; }

        .tb-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5pt;
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        /* Header follows the source document: a #005BF0 band with
           white sentence-case text over a 0.5pt black hairline. */
        .tb-table thead th {
            font-size: 10.5pt;
            font-weight: bold;
            text-transform: none;
            letter-spacing: 0;
            padding: 4pt 4pt;
            color: #ffffff;
            border-top: none;
            border-bottom: 0.5pt solid #000000;
            text-align: left;
            background: #005bf0;
        }

        .tb-table thead th.right {
            text-align: right;
        }

        .tb-table tbody tr {
            border-bottom: 0.4pt solid #d3e2f5;
        }

        .tb-table tbody tr:hover {
            background: #f4fafc;
        }

        .tb-table td {
            padding: 2pt 4pt;
            font-size: 10.5pt;
            color: #191919;
            vertical-align: middle;
        }

        /* The document sets figures in Century Gothic with tabular
           figures, not a monospace face. */
        .tb-table td.right {
            text-align: right;
            font-family: inherit;
            font-variant-numeric: tabular-nums;
            font-size: 10.5pt;
            white-space: nowrap;
        }

        .tb-table td.dim {
            color: #9ec1f5;
        }

        .tb-table td.code {
            font-family: inherit;
            font-variant-numeric: tabular-nums;
            font-size: 10.5pt;
            font-weight: bold;
            white-space: nowrap;
            color: #1a345b;
        }

        .tb-table td.type-label {
            font-size: 7pt;
            color: #6f869b;
        }

        .tb-table tr.tb-section-row td {
            font-size: 10.5pt;
            font-weight: bold;
            padding: 3pt 4pt;
            color: #005bf0;
            border-top: 0.5pt solid #d3e2f5;
            background: #f4fafc;
            cursor: pointer;
            user-select: none;
        }

        .tb-table tr.tb-section-row:hover td {
            background: #eaf8fb;
        }

        .tb-table tr.tb-section-row td.right {
            font-family: inherit;
            font-variant-numeric: tabular-nums;
            font-weight: bold;
            color: #1a345b;
        }

        .tb-section-label {
            display: inline-flex;
            align-items: center;
            gap: 4pt;
        }

        .tb-chevron {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 10px;
            height: 10px;
            transition: transform 0.18s ease;
            color: #005bf0;
        }

        .tb-section-row.is-collapsed .tb-chevron {
            transform: rotate(-90deg);
        }

        .tb-acct-row.is-hidden {
            display: none;
        }

        .tb-table tfoot tr.tb-grand-total td {
            padding: 4pt 4pt;
            font-size: 10.5pt;
            font-weight: bold;
            color: #1a345b;
            background: #eaf8fb;
            border-top: 1.5px solid #1a345b;
            border-bottom: 2px solid #1a345b;
        }

        .tb-table tfoot tr.tb-grand-total td.right {
            font-family: inherit;
            font-variant-numeric: tabular-nums;
        }

        .tb-off-banner {
            display: flex;
            align-items: center;
            gap: 6pt;
            background: #fff;
            border: 1px solid #b91c1c;
            padding: 5pt 8pt;
            margin: 0 0 8pt;
            font-size: 7pt;
            font-weight: 700;
            color: #b91c1c;
            text-transform: none;
            letter-spacing: 0;
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        .tb-diff-row td {
            font-size: 10.5pt;
            font-weight: 700;
            color: #b91c1c;
            padding: 3pt 4pt;
            text-transform: none;
            letter-spacing: 0;
        }

        .tb-diff-row td.right {
            font-family: inherit;
            font-variant-numeric: tabular-nums;
            color: #b91c1c;
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
                            <div class="co-section-heading"><p class="co-section-label">Reports</p><h2>Trial Balance</h2></div>
                            <p class="is-period-label">
                                {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} &ndash;
                                {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                            </p>
                        </div>
                        @php
                            $debitNormal = ['assets', 'expenses'];
                            $typeLabels = [
                                'assets' => 'Assets',
                                'liabilities' => 'Liabilities',
                                'equity' => 'Equity',
                                'income' => 'Income',
                                'expenses' => 'Expenses',
                            ];
                            $totalDebit = 0;
                            $totalCredit = 0;
                            $sectionTotals = [];
                            foreach ($accounts as $account) {
                                $type = $account->account_type;
                                $opening = (float) $account->opening_balance;
                                $pDebits = (float) $account->posted_debits;
                                $pCredits = (float) $account->posted_credits;
                                $isDebitNormal = in_array($type, $debitNormal);
                                $balance = $isDebitNormal
                                    ? $opening + $pDebits - $pCredits
                                    : $opening + $pCredits - $pDebits;
                                if ($isDebitNormal) {
                                    $d = $balance >= 0 ? $balance : 0;
                                    $c = $balance < 0 ? abs($balance) : 0;
                                } else {
                                    $c = $balance >= 0 ? $balance : 0;
                                    $d = $balance < 0 ? abs($balance) : 0;
                                }
                                $totalDebit += $d;
                                $totalCredit += $c;
                                if (!isset($sectionTotals[$type])) {
                                    $sectionTotals[$type] = ['debit' => 0, 'credit' => 0];
                                }
                                $sectionTotals[$type]['debit']  += $d;
                                $sectionTotals[$type]['credit'] += $c;
                            }
                            $isBalanced = round($totalDebit, 2) === round($totalCredit, 2);
                            $difference = round(abs($totalDebit - $totalCredit), 2);
                            $roundingLabel = match ($rounding) {
                                1000 => "R'000",
                                1000000 => "R'm",
                                default => 'R',
                            };
                            $roundingDecimals = match ($rounding) {
                                1000 => 0,
                                1000000 => 2,
                                default => 2,
                            };
                        @endphp
                        <div style="display:flex;align-items:center;gap:0.6rem;">
                            @if ($accounts->isNotEmpty())
                                <a href="{{ route('companies.reports.trial-balance.pdf', $company) }}?start_date={{ $startDate }}&end_date={{ $endDate }}&rounding={{ $rounding }}"
                                    target="_blank" rel="noopener"
                                    style="display:inline-flex;align-items:center;gap:0.35rem;background:#1a345b;color:#fff;border-radius:0;padding:0.28rem 0.75rem;font-size:0.72rem;font-weight:700;text-decoration:none;transition:background 0.15s;white-space:nowrap;"
                                    onmouseover="this.style.background='#005bf0'" onmouseout="this.style.background='#1a345b'">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                        <polyline points="7 10 12 15 17 10" />
                                        <line x1="12" y1="15" x2="12" y2="3" />
                                    </svg>
                                    PDF
                                </a>
                                <a href="{{ route('companies.reports.trial-balance.excel', $company) }}?start_date={{ $startDate }}&end_date={{ $endDate }}&rounding={{ $rounding }}"
                                    target="_blank" rel="noopener"
                                    style="display:inline-flex;align-items:center;gap:0.35rem;background:#005bf0;color:#fff;border-radius:0;padding:0.28rem 0.75rem;font-size:0.72rem;font-weight:700;text-decoration:none;transition:background 0.15s;white-space:nowrap;"
                                    onmouseover="this.style.background='#005f9e'" onmouseout="this.style.background='#005bf0'">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                        <polyline points="7 10 12 15 17 10" />
                                        <line x1="12" y1="15" x2="12" y2="3" />
                                    </svg>
                                    Excel
                                </a>

                                <label style="display:inline-flex;align-items:center;gap:0.35rem;background:#fff;border:1px solid #1a345b;color:#1a345b;border-radius:0;padding:0.28rem 0.75rem;font-size:0.72rem;font-weight:700;text-decoration:none;transition:background 0.15s,color 0.15s;white-space:nowrap;cursor:pointer;"
                                    onmouseover="this.style.background='#1a345b';this.style.color='#fff'" onmouseout="this.style.background='#fff';this.style.color='#1a345b'"
                                    title="Import opening balances from an Excel file exported by this system or matching the same column format (Code, Account Name, Type, Debit, Credit).">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                        <polyline points="7 18 12 13 17 18" />
                                        <line x1="12" y1="13" x2="12" y2="3" />
                                    </svg>
                                    Import
                                    <form id="tb-import-form" method="POST"
                                        action="{{ route('companies.reports.trial-balance.import', $company) }}"
                                        enctype="multipart/form-data" style="display:none;">
                                        @csrf
                                        <input type="file" name="file" id="tb-import-file"
                                            accept=".xlsx,.xls,.csv"
                                            onchange="document.getElementById('tb-import-form').submit();">
                                    </form>
                                    <script>document.querySelector('label[title^="Import"]').addEventListener('click',function(){document.getElementById('tb-import-file').click();});</script>
                                </label>
                            @endif
                        </div>
                    </div>

                    <form method="GET" action="{{ route('companies.reports.trial-balance', $company) }}"
                        class="is-filter-bar">
                        <div>
                            <label for="tb-start">From</label>
                            <input type="date" id="tb-start" name="start_date" value="{{ $startDate }}">
                        </div>
                        <div>
                            <label for="tb-end">To</label>
                            <input type="date" id="tb-end" name="end_date" value="{{ $endDate }}">
                        </div>
                        <div>
                            <label for="tb-rounding">Rounding</label>
                            <select id="tb-rounding" name="rounding">
                                <option value="1" @selected($rounding === 1)>Exact (R)</option>
                                <option value="1000" @selected($rounding === 1000)>Thousands (R&rsquo;000)</option>
                                <option value="1000000" @selected($rounding === 1000000)>Millions (R&rsquo;m)</option>
                            </select>
                        </div>
                        <button type="submit" class="is-filter-btn">Apply</button>
                    </form>

                    @if (session('import_errors'))
                        <div style="margin:0 1.25rem 1rem;background:#fff7ed;border:1px solid #fed7aa;padding:0.75rem 1rem;font-size:0.78rem;">
                            <strong style="color:#c2410c;">Import warnings:</strong>
                            <ul style="margin:0.35rem 0 0 1.25rem;padding:0;color:#92400e;">
                                @foreach (session('import_errors') as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($accounts->isEmpty())
                        <div class="empty-state">
                            <p style="font-weight:700;color:#5a7186;margin:0 0 0.3rem;">No accounts found</p>
                            <p style="font-size:0.8rem;margin:0;">Set up your chart of accounts first.</p>
                        </div>
                    @else
                        @if (!$isBalanced)
                            <div class="tb-off-banner" style="margin:0 1.25rem 8pt;">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"
                                    stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <line x1="12" y1="8" x2="12" y2="12" />
                                    <line x1="12" y1="16" x2="12.01" y2="16" />
                                    <path
                                        d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                                </svg>
                                Out of Balance &mdash; Difference:
                                {{ $roundingLabel }}&nbsp;{{ number_format($difference / $rounding, $roundingDecimals) }}
                            </div>
                        @endif
                        @if ($rounding > 1)
                            <p style="font-size:6.5pt;color:#6f869b;margin:0 1.25rem 6pt;font-style:italic;">Amounts
                                in {{ $roundingLabel }}</p>
                        @endif
                        @php
                            $typeOrder = ['assets', 'liabilities', 'equity', 'income', 'expenses'];
                            $grouped = $accounts->groupBy('account_type');
                        @endphp
                        <div style="padding: 0 1rem 0.5rem; overflow-x:auto;">
                            <table class="tb-table">
                                <thead>
                                    <tr>
                                        <th style="width:60px;">Code</th>
                                        <th>Account Name</th>
                                        <th class="hide-mobile" style="width:90px;">Type</th>
                                        <th class="right" style="width:100px;">Debit ({{ $roundingLabel }})</th>
                                        <th class="right" style="width:100px;">Credit ({{ $roundingLabel }})</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($typeOrder as $type)
                                        @if ($grouped->has($type))
                                            @php
                                                $sectDebit  = $sectionTotals[$type]['debit']  ?? 0;
                                                $sectCredit = $sectionTotals[$type]['credit'] ?? 0;
                                            @endphp
                                            <tr class="tb-section-row" data-section="{{ $type }}"
                                                aria-expanded="true" role="button" tabindex="0">
                                                <td colspan="3">
                                                    <span class="tb-section-label">
                                                        <span class="tb-chevron" aria-hidden="true">
                                                            <svg width="10" height="10" viewBox="0 0 24 24"
                                                                fill="none" stroke="currentColor" stroke-width="3"
                                                                stroke-linecap="round" stroke-linejoin="round">
                                                                <polyline points="6 9 12 15 18 9"/>
                                                            </svg>
                                                        </span>
                                                        {{ $typeLabels[$type] ?? $type }}
                                                    </span>
                                                </td>
                                                <td class="right" style="color:{{ $sectDebit > 0 ? '#1a345b' : '#9ec1f5' }};">
                                                    {{ $sectDebit > 0 ? number_format($sectDebit / $rounding, $roundingDecimals) : '—' }}
                                                </td>
                                                <td class="right" style="color:{{ $sectCredit > 0 ? '#1a345b' : '#9ec1f5' }};">
                                                    {{ $sectCredit > 0 ? number_format($sectCredit / $rounding, $roundingDecimals) : '—' }}
                                                </td>
                                            </tr>
                                            @foreach ($grouped[$type] as $account)
                                                @php
                                                    $opening = (float) $account->opening_balance;
                                                    $pDebits = (float) $account->posted_debits;
                                                    $pCredits = (float) $account->posted_credits;
                                                    $isDebitNormal = in_array($account->account_type, $debitNormal);
                                                    $balance = $isDebitNormal
                                                        ? $opening + $pDebits - $pCredits
                                                        : $opening + $pCredits - $pDebits;
                                                    if ($isDebitNormal) {
                                                        $debit = $balance >= 0 ? $balance : 0;
                                                        $credit = $balance < 0 ? abs($balance) : 0;
                                                    } else {
                                                        $credit = $balance >= 0 ? $balance : 0;
                                                        $debit = $balance < 0 ? abs($balance) : 0;
                                                    }
                                                @endphp
                                                <tr class="tb-acct-row" data-section="{{ $type }}">
                                                    <td class="code">{{ $account->account_code }}</td>
                                                    <td>{{ $account->account_name }}</td>
                                                    <td class="type-label hide-mobile">
                                                        {{ $typeLabels[$account->account_type] ?? $account->account_type }}
                                                    </td>
                                                    <td class="{{ $debit > 0 ? 'right' : 'right dim' }}">
                                                        {{ $debit > 0 ? number_format($debit / $rounding, $roundingDecimals) : '—' }}
                                                    </td>
                                                    <td class="{{ $credit > 0 ? 'right' : 'right dim' }}">
                                                        {{ $credit > 0 ? number_format($credit / $rounding, $roundingDecimals) : '—' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="tb-grand-total">
                                        <td colspan="3">TOTAL</td>
                                        <td class="right">
                                            {{ number_format($totalDebit / $rounding, $roundingDecimals) }}</td>
                                        <td class="right">
                                            {{ number_format($totalCredit / $rounding, $roundingDecimals) }}</td>
                                    </tr>
                                    @if (!$isBalanced)
                                        <tr class="tb-diff-row">
                                            <td colspan="3">Difference</td>
                                            <td colspan="2" class="right">
                                                {{ number_format($difference / $rounding, $roundingDecimals) }}
                                            </td>
                                        </tr>
                                    @endif
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>

    <script>
        (function () {
            const storageKey = 'tb-collapsed-{{ $company->id }}';

            function readState() {
                try {
                    const raw = localStorage.getItem(storageKey);
                    return raw ? JSON.parse(raw) : [];
                } catch (e) { return []; }
            }

            function writeState(arr) {
                try { localStorage.setItem(storageKey, JSON.stringify(arr)); } catch (e) {}
            }

            function applyState(collapsed) {
                document.querySelectorAll('.tb-section-row').forEach(row => {
                    const section = row.dataset.section;
                    const isCollapsed = collapsed.includes(section);
                    row.classList.toggle('is-collapsed', isCollapsed);
                    row.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
                });
                document.querySelectorAll('.tb-acct-row').forEach(row => {
                    row.classList.toggle('is-hidden', collapsed.includes(row.dataset.section));
                });
            }

            function toggle(section) {
                const collapsed = readState();
                const idx = collapsed.indexOf(section);
                if (idx >= 0) collapsed.splice(idx, 1);
                else collapsed.push(section);
                writeState(collapsed);
                applyState(collapsed);
            }

            document.querySelectorAll('.tb-section-row').forEach(row => {
                row.addEventListener('click', () => toggle(row.dataset.section));
                row.addEventListener('keydown', e => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        toggle(row.dataset.section);
                    }
                });
            });

            applyState(readState());
        })();
    </script>
@endsection
