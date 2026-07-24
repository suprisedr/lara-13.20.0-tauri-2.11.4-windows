<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Actions &mdash; {{ $company->registered_name }}</title>
    @include('pdf._report-header-styles')
    <style>
        @page {
            margin: 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            font-size: 7pt;
            color: #23282d;
            background: #fff;
            line-height: 1.4;
        }

        .page-frame {
            border: none;
            padding: 8mm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 3pt 4pt;
            border-top: 1.5px solid #4c1d95;
            border-bottom: 1.5px solid #4c1d95;
            text-align: left;
            color: #4c1d95;
            background: #ede9fe;
        }

        tbody tr {
            border-bottom: 0.4px solid #ddd6fe;
        }

        tbody td {
            padding: 3pt 4pt;
            font-size: 6.5pt;
            color: #23282d;
            vertical-align: top;
        }

        td.muted {
            color: #6b5b8a;
        }

        .badge {
            display: inline-block;
            font-size: 5.5pt;
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 2px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-open {
            background: #fef9c3;
            color: #854d0e;
        }

        .badge-resolved {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-high {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge-medium {
            background: #fef9c3;
            color: #854d0e;
        }

        .badge-low {
            background: #f3f4f6;
            color: #6b7280;
        }

        .footer {
            margin-top: 14px;
            padding-top: 5px;
            border-top: 1px solid #c4b5fd;
            font-size: 6pt;
            color: #8b7aad;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="page-frame">

        @include('pdf._report-header', [
            'company'      => $company,
            'reportTitle'  => 'Company Actions',
            'reportPeriod' => $tabLabel . ' — ' . $actions->count() . ' ' . \Illuminate\Support\Str::plural('item', $actions->count()),
            'roundingLabel'=> 'R',
            'compare'      => false,
        ])

        <table>
            <thead>
                <tr>
                    <th style="width:26%">Title</th>
                    <th style="width:9%">Priority</th>
                    <th style="width:10%">Source</th>
                    <th style="width:9%">Status</th>
                    <th style="width:26%">Details</th>
                    <th style="width:10%">Created</th>
                    <th style="width:10%">Resolved</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($actions as $action)
                    <tr>
                        <td>{{ $action->title }}</td>
                        <td><span class="badge badge-{{ $action->priority }}">{{ ucfirst($action->priority) }}</span></td>
                        <td class="muted">
                            {{ match ($action->source) {
                                'ai-agent' => 'AI Agent',
                                'system'   => 'System',
                                default    => 'Manual',
                            } }}
                        </td>
                        <td><span class="badge badge-{{ $action->isResolved() ? 'resolved' : 'open' }}">{{ $action->isResolved() ? 'Resolved' : 'Open' }}</span></td>
                        <td class="muted">{{ \Illuminate\Support\Str::limit($action->body ?? '', 90) }}</td>
                        <td class="muted">{{ $action->created_at->format('d M Y') }}</td>
                        <td class="muted">{{ $action->resolved_at?->format('d M Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:#8b7aad;padding:10pt;">No actions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            Generated on {{ now()->format('d F Y \a\t H:i') }} &mdash; For internal use only.
        </div>

    </div>
</body>

</html>
