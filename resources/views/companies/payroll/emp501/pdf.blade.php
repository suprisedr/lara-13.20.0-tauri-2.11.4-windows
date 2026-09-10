<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>EMP501 Reconciliation - {{ $report['tax_year_label'] }}</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
            font-size: 7.5pt;
            color: #000;
            background: #fff;
            line-height: 1.35;
            padding: 30px 40px 40px 40px;
        }

        .report-header {
            text-align: center;
            margin-bottom: 14px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
        }

        .report-header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin: 0;
            letter-spacing: 0.05em;
        }

        .report-header p {
            font-size: 8pt;
            margin: 2px 0 0;
        }

        .section-title {
            font-size: 8pt;
            font-weight: bold;
            background: #f4fafc;
            padding: 3px 6px;
            margin: 10px 0 4px;
            border-bottom: 1px solid #000;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .info-table td {
            padding: 2px 4px;
            font-size: 7.5pt;
            vertical-align: top;
        }

        .info-table td.lbl {
            font-weight: bold;
            width: 180px;
            color: #191919;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .data-table thead td {
            font-weight: bold;
            font-size: 7pt;
            border-bottom: 1.5px solid #000;
            padding: 2px 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .data-table thead td.amt { text-align: right; }

        .data-table tbody td {
            padding: 2px 4px;
            font-size: 7.5pt;
            border-bottom: 1px solid #d3e2f5;
        }

        .data-table tbody td.amt {
            text-align: right;
            font-family: DejaVu Sans, monospace;
        }

        .data-table tfoot td {
            font-weight: bold;
            border-top: 1.5px solid #000;
            padding: 3px 4px;
            font-size: 7.5pt;
        }

        .data-table tfoot td.amt {
            text-align: right;
            font-family: DejaVu Sans, monospace;
        }

        .summary-box {
            border: 2px solid #000;
            padding: 6px 10px;
            margin-top: 8px;
        }

        .summary-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-box td {
            padding: 2px 4px;
            font-size: 8pt;
        }

        .summary-box td.amt {
            text-align: right;
            font-weight: bold;
            font-family: DejaVu Sans, monospace;
        }

        .footer-text {
            text-align: center;
            font-size: 6.5pt;
            color: #5a7186;
            margin-top: 10px;
        }

        .two-col {
            width: 100%;
            border-collapse: collapse;
        }

        .two-col > tbody > tr > td {
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }

        .two-col > tbody > tr > td:last-child {
            padding-right: 0;
            padding-left: 10px;
        }
    </style>
</head>
<body>

    {{-- Report header --}}
    <div class="report-header">
        <h1>EMP501 EMPLOYER ANNUAL RECONCILIATION</h1>
        <p>{{ $company->registered_name }} -- Tax Year {{ $report['tax_year_label'] }}</p>
        <p style="font-size:7pt;color:#5a7186;">Period: {{ \Carbon\Carbon::parse($report['tax_year_start'])->format('d/m/Y') }} to {{ \Carbon\Carbon::parse($report['tax_year_end'])->format('d/m/Y') }}</p>
    </div>

    {{-- Employer details and summary side by side --}}
    <table class="two-col">
        <tr>
            <td>
                <div class="section-title">EMPLOYER DETAILS</div>
                <table class="info-table">
                    <tr><td class="lbl">Registered Name</td><td>{{ $company->registered_name }}</td></tr>
                    <tr><td class="lbl">Registration Number</td><td>{{ $company->registration_number ?? '---' }}</td></tr>
                    <tr><td class="lbl">PAYE Reference No.</td><td>{{ $company->paye_number ?? '---' }}</td></tr>
                    <tr><td class="lbl">UIF Reference No.</td><td>{{ $company->uif_number ?? '---' }}</td></tr>
                    <tr><td class="lbl">SDL Reference No.</td><td>{{ $company->sdl_number ?? '---' }}</td></tr>
                </table>
            </td>
            <td>
                <div class="section-title">RECONCILIATION SUMMARY</div>
                <table class="info-table">
                    <tr><td class="lbl">Total Employees</td><td>{{ $report['total_employees'] }}</td></tr>
                    <tr><td class="lbl">Total Payroll Runs</td><td>{{ $report['total_runs'] }}</td></tr>
                    <tr><td class="lbl">IRP5 Certificates</td><td>{{ $report['irp5_totals']['certificates'] }}</td></tr>
                    <tr><td class="lbl">Gross Remuneration</td><td>R {{ number_format($report['emp201_totals']['gross_remuneration'], 2) }}</td></tr>
                    <tr><td class="lbl">Total Liability</td><td style="font-weight:bold;">R {{ number_format($report['emp201_totals']['total_liability'], 2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Variance analysis --}}
    <div class="section-title">VARIANCE ANALYSIS (EMP201 DECLARED vs IRP5 CERTIFICATES)</div>
    <table class="data-table">
        <thead>
            <tr>
                <td>Item</td>
                <td class="amt">EMP201 Declared (R)</td>
                <td class="amt">IRP5 Certificates (R)</td>
                <td class="amt">Variance (R)</td>
                <td class="amt">Status</td>
            </tr>
        </thead>
        <tbody>
            @foreach (['gross_remuneration' => 'Gross Remuneration', 'paye' => 'PAYE', 'uif' => 'UIF (employee)', 'sdl' => 'SDL'] as $key => $label)
                <tr>
                    <td>{{ $label }}</td>
                    <td class="amt">{{ number_format($report['variance'][$key]['emp201'], 2) }}</td>
                    <td class="amt">{{ number_format($report['variance'][$key]['irp5'], 2) }}</td>
                    <td class="amt">{{ number_format(abs($report['variance'][$key]['diff']), 2) }}</td>
                    <td class="amt">{{ $report['variance'][$key]['diff'] == 0 ? 'Balanced' : ($report['variance'][$key]['diff'] > 0 ? 'Over-declared' : 'Under-declared') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Monthly breakdown --}}
    <div class="section-title">MONTHLY BREAKDOWN (EMP201 PERIODS)</div>
    <table class="data-table">
        <thead>
            <tr>
                <td>Month</td>
                <td class="amt">Employees</td>
                <td class="amt">Gross (R)</td>
                <td class="amt">PAYE (R)</td>
                <td class="amt">UIF emp (R)</td>
                <td class="amt">UIF er (R)</td>
                <td class="amt">SDL (R)</td>
                <td class="amt">Total (R)</td>
            </tr>
        </thead>
        <tbody>
            @php $mGross = 0; $mPaye = 0; $mUifE = 0; $mUifR = 0; $mSdl = 0; $mTotal = 0; @endphp
            @foreach ($report['monthly_breakdown'] as $month)
                @php
                    $mGross += $month['gross'];
                    $mPaye += $month['paye'];
                    $mUifE += $month['uif_employee'];
                    $mUifR += $month['uif_employer'];
                    $mSdl += $month['sdl'];
                    $mTotal += $month['total_liability'];
                @endphp
                <tr>
                    <td>{{ $month['month'] }}</td>
                    <td class="amt">{{ $month['employees'] }}</td>
                    <td class="amt">{{ number_format($month['gross'], 2) }}</td>
                    <td class="amt">{{ number_format($month['paye'], 2) }}</td>
                    <td class="amt">{{ number_format($month['uif_employee'], 2) }}</td>
                    <td class="amt">{{ number_format($month['uif_employer'], 2) }}</td>
                    <td class="amt">{{ number_format($month['sdl'], 2) }}</td>
                    <td class="amt">{{ number_format($month['total_liability'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="amt"></td>
                <td class="amt">{{ number_format($mGross, 2) }}</td>
                <td class="amt">{{ number_format($mPaye, 2) }}</td>
                <td class="amt">{{ number_format($mUifE, 2) }}</td>
                <td class="amt">{{ number_format($mUifR, 2) }}</td>
                <td class="amt">{{ number_format($mSdl, 2) }}</td>
                <td class="amt">{{ number_format($mTotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer-text">
        EMP501 Employer Annual Reconciliation -- {{ $company->registered_name }} -- Tax Year {{ $report['tax_year_label'] }}.
        Generated from payroll records. Verify against SARS eFiling before submission.
    </div>

    @include('pdf._attribution', ['attrLeft' => '15mm', 'attrWidth' => '180mm'])
</body>
</html>
