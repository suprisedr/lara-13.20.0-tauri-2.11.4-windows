<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Actions &mdash; {{ $company->registered_name }}</title>
    @include('pdf._report-header-styles')
    <style>
        /* Page geometry from CGL-YE25 sectPr: 28800 x 16200 twips
           landscape = 508mm x 285.75mm. dompdf ignores @page margins,
           so the inset lives on .page-frame below. The controller
           also sets this explicitly via setPaper([0,0,1440,810]). */
        @page {
            size: 508mm 285.75mm;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
            font-size: 10.5pt;
            color: #191919;
            background: #fff;
            line-height: 1.2857;
        }

        .page-frame {
            border: none;
            padding: 26.46mm 26.46mm 19.32mm 26.46mm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            font-size: 10.5pt;
            font-weight: bold;
            text-transform: none;
            letter-spacing: 0;
            padding: 3pt 4pt;
            border-top: none;
            border-bottom: 0.5pt solid #000000;
            text-align: left;
            color: #ffffff;
            background: #005bf0;
        }

        tbody tr {
            border-bottom: 0.4px solid #d3e2f5;
        }

        tbody td {
            padding: 3pt 4pt;
            font-size: 10.5pt;
            color: #191919;
            vertical-align: top;
        }

        td.muted {
            color: #5a7186;
        }

        .badge {
            display: inline-block;
            font-size: 7pt;
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 2px;
            text-transform: none;
            letter-spacing: 0;
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
            background: #f4fafc;
            color: #5a7186;
        }

        .footer {
            margin-top: 14px;
            padding-top: 5px;
            border-top: 1px solid #9ec1f5;
            font-size: 7pt;
            color: #6f869b;
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
                        <td colspan="7" style="text-align:center;color:#6f869b;padding:10pt;">No actions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            Generated on {{ now()->format('d F Y \a\t H:i') }} &mdash; For internal use only.
        </div>

    </div>
    @include('pdf._attribution')
</body>

</html>
