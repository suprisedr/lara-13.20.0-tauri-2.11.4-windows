<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Chart of Accounts &mdash; {{ $company->registered_name }}</title>
    @include('pdf._afs-styles')
    <style>
        /* ── Chart of accounts table (AFS design language) ── */
        table.coa {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        table.coa thead th {
            padding: 4pt 5pt 5pt 5pt;
            font-weight: normal;
            color: #4a5f78;
            border-bottom: 0.75pt solid #16355c;
            text-align: left;
            vertical-align: bottom;
        }

        table.coa thead th.num {
            text-align: right;
            font-weight: bold;
            color: #16355c;
        }

        table.coa tr { page-break-inside: avoid; }

        table.coa tr.coa-section td {
            padding: 10pt 5pt 3pt 5pt;
            font-weight: bold;
            font-size: 10.5pt;
            color: #16355c;
        }

        table.coa tr.coa-group td {
            padding: 3.5pt 5pt;
            font-weight: bold;
            color: #16355c;
            border-bottom: 0.4pt solid #ddebf5;
        }

        table.coa tr.coa-item td {
            padding: 3.5pt 5pt;
            border-bottom: 0.4pt solid #ddebf5;
            color: #23282d;
        }

        table.coa tr.coa-item td.name { padding-left: 18pt; }

        table.coa td.num  { text-align: right; white-space: nowrap; }
        table.coa td.code { white-space: nowrap; }
        table.coa td.dim  { color: #7a90a5; }

        table.coa tr.coa-total td {
            padding: 4.5pt 5pt;
            font-weight: bold;
            color: #16355c;
            border-top: 0.75pt solid #16355c;
            border-bottom: 0.75pt solid #16355c;
        }

        table.coa tr.coa-grand td {
            padding: 5pt;
            font-weight: bold;
            color: #16355c;
            background: #e7f3fb;
            border-top: 0.75pt solid #16355c;
            border-bottom: 1.5pt solid #16355c;
        }

        .coa-inactive { color: #b91c1c; }
    </style>
</head>

<body>
    <div class="page-frame">

        @php
            $typeOrder = ['assets', 'liabilities', 'equity', 'income', 'expenses'];
            $grouped = $accounts->groupBy('account_type');
            $typeLabels = [
                'assets' => 'Assets',
                'liabilities' => 'Liabilities',
                'equity' => 'Equity',
                'income' => 'Income',
                'expenses' => 'Expenses',
            ];
            $grandTotal = $accounts->sum('balance');
        @endphp

        @include('pdf._letterhead')

        <div class="afs-doc-header">
            <div class="afs-doc-title">Chart of Accounts</div>
            <div class="afs-doc-subtitle">{{ $periodLabel }} &middot; Figures in R</div>
        </div>

        {{-- Accounts Table --}}
        <table class="coa">
            <thead>
                <tr>
                    <th style="width:8%">Code</th>
                    <th style="width:26%">Account Name</th>
                    <th style="width:13%">Category</th>
                    <th style="width:7%">Role</th>
                    <th style="width:26%">Description</th>
                    <th style="width:7%">Status</th>
                    <th style="width:13%" class="num">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($typeOrder as $type)
                    @if ($grouped->has($type))
                        @php
                            $sectionAccounts = $grouped[$type]->sortBy('account_code');
                            $sectionTotal = $sectionAccounts->sum('balance');
                        @endphp
                        <tr class="coa-section">
                            <td colspan="7">{{ $typeLabels[$type] }}</td>
                        </tr>
                        @foreach ($sectionAccounts as $account)
                            <tr class="{{ $account->parent_id ? 'coa-item' : 'coa-group' }}">
                                <td class="code">{{ $account->account_code }}</td>
                                <td class="{{ $account->parent_id ? 'name' : '' }}">{{ $account->account_name }}</td>
                                <td class="dim">{{ $account->category ?? '—' }}</td>
                                <td class="dim">{{ $account->parent_id ? 'Item' : 'Group' }}</td>
                                <td class="dim">
                                    {{ \Illuminate\Support\Str::limit($account->description ?? '—', 60) }}</td>
                                <td>
                                    @if ($account->is_active)
                                        Active
                                    @else
                                        <span class="coa-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td class="num">{{ number_format($account->balance, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="coa-total">
                            <td colspan="6" style="text-align:right;padding-right:8pt;">Total {{ $typeLabels[$type] }}</td>
                            <td class="num">{{ number_format($sectionTotal, 2) }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr class="coa-grand">
                    <td colspan="6" style="text-align:right;padding-right:8pt;">Grand Total</td>
                    <td class="num">{{ number_format($grandTotal, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="afs-footer">
            {{ $company->registered_name }} &middot; Chart of Accounts &middot;
            Generated {{ now()->format('d F Y') }}
        </div>

    </div>
</body>

</html>
