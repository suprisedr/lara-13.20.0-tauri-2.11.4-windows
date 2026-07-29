<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>IRP5 Certificates - {{ $company->registered_name }}</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 7.5pt;
            color: #000;
            background: #fff;
            line-height: 1.35;
        }

        .certificate-page {
            padding: 30px 40px 40px 40px;
            page-break-after: always;
        }

        .certificate-page:last-child {
            page-break-after: avoid;
        }

        .cert-header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
        }

        .cert-header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin: 0;
            letter-spacing: 0.05em;
        }

        .cert-header p {
            font-size: 8pt;
            margin: 2px 0 0;
        }

        .section-title {
            font-size: 8pt;
            font-weight: bold;
            background: #f0f0f0;
            padding: 3px 6px;
            margin: 8px 0 4px;
            border-bottom: 1px solid #000;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 2px 4px;
            font-size: 7.5pt;
            vertical-align: top;
        }

        .info-table td.lbl {
            font-weight: bold;
            width: 140px;
            color: #333;
        }

        .info-table td.val {
            font-family: DejaVu Sans, monospace;
        }

        .codes-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .codes-table thead td {
            font-weight: bold;
            font-size: 7pt;
            border-bottom: 1.5px solid #000;
            padding: 2px 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .codes-table tbody td {
            padding: 2px 4px;
            font-size: 7.5pt;
            border-bottom: 1px solid #ddd;
        }

        .codes-table .code-col {
            font-family: DejaVu Sans, monospace;
            font-weight: bold;
            width: 50px;
        }

        .codes-table .amt {
            text-align: right;
            width: 100px;
            font-family: DejaVu Sans, monospace;
        }

        .total-row td {
            font-weight: bold;
            border-top: 1.5px solid #000;
            border-bottom: none;
            padding-top: 4px;
        }

        .summary-box {
            border: 2px solid #000;
            padding: 6px 10px;
            margin-top: 10px;
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
            color: #666;
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

@foreach ($certificates as $certificate)
    <div class="certificate-page">
        @php $emp = $certificate['employee']; @endphp

        <div class="cert-header">
            <h1>{{ $certificate['certificate_type'] }} TAX CERTIFICATE</h1>
            <p>Employees' Tax Certificate [IT3(a) / IRP5] -- Tax Year {{ $certificate['tax_year_label'] }}</p>
            <p style="font-size:7pt;color:#666;">Period: {{ \Carbon\Carbon::parse($certificate['tax_year_start'])->format('d/m/Y') }} to {{ \Carbon\Carbon::parse($certificate['tax_year_end'])->format('d/m/Y') }}</p>
        </div>

        <table class="two-col">
            <tr>
                <td>
                    <div class="section-title">EMPLOYER DETAILS</div>
                    <table class="info-table">
                        <tr><td class="lbl">Registered Name</td><td class="val">{{ $company->registered_name }}</td></tr>
                        <tr><td class="lbl">Trading Name</td><td class="val">{{ $company->trading_name ?? $company->registered_name }}</td></tr>
                        <tr><td class="lbl">Reg. Number</td><td class="val">{{ $company->registration_number ?? '---' }}</td></tr>
                        <tr><td class="lbl">PAYE Ref. No.</td><td class="val">{{ $company->paye_number ?? '---' }}</td></tr>
                        <tr><td class="lbl">UIF Ref. No.</td><td class="val">{{ $company->uif_number ?? '---' }}</td></tr>
                        <tr><td class="lbl">SDL Ref. No.</td><td class="val">{{ $company->sdl_number ?? '---' }}</td></tr>
                    </table>
                </td>
                <td>
                    <div class="section-title">EMPLOYEE DETAILS</div>
                    <table class="info-table">
                        <tr><td class="lbl">Employee No.</td><td class="val">{{ $emp->employee_number }}</td></tr>
                        <tr><td class="lbl">Surname</td><td class="val">{{ $emp->last_name }}</td></tr>
                        <tr><td class="lbl">First Name(s)</td><td class="val">{{ $emp->first_name }}</td></tr>
                        <tr><td class="lbl">ID Number</td><td class="val">{{ $emp->id_number ?? '---' }}</td></tr>
                        <tr><td class="lbl">Tax Ref. No.</td><td class="val">{{ $emp->tax_reference_number ?? '---' }}</td></tr>
                        <tr><td class="lbl">Date of Birth</td><td class="val">{{ $emp->date_of_birth?->format('d/m/Y') ?? '---' }}</td></tr>
                        <tr><td class="lbl">Periods Employed</td><td class="val">{{ $certificate['periods_employed'] }} month(s)</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="two-col">
            <tr>
                <td>
                    <div class="section-title">INCOME SOURCE CODES</div>
                    <table class="codes-table">
                        <thead>
                            <tr>
                                <td>Code</td>
                                <td>Description</td>
                                <td class="amt">Amount (R)</td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($certificate['income_sources'] as $source)
                                <tr>
                                    <td class="code-col">{{ $source['code'] }}</td>
                                    <td>{{ $source['description'] }}</td>
                                    <td class="amt">{{ number_format($source['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="total-row">
                                <td></td>
                                <td>Gross Remuneration</td>
                                <td class="amt">{{ number_format($certificate['gross_remuneration'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td>
                    <div class="section-title">DEDUCTION CODES</div>
                    <table class="codes-table">
                        <thead>
                            <tr>
                                <td>Code</td>
                                <td>Description</td>
                                <td class="amt">Amount (R)</td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($certificate['deduction_codes'] as $deduction)
                                <tr>
                                    <td class="code-col">{{ $deduction['code'] }}</td>
                                    <td>{{ $deduction['description'] }}</td>
                                    <td class="amt">{{ number_format($deduction['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="total-row">
                                <td></td>
                                <td>Total Deductions</td>
                                <td class="amt">{{ number_format($certificate['total_deductions'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <div class="summary-box">
            <table>
                <tr>
                    <td>Gross Remuneration</td>
                    <td class="amt">R {{ number_format($certificate['gross_remuneration'], 2) }}</td>
                    <td style="width:40px;"></td>
                    <td>Total PAYE Deducted (4001)</td>
                    <td class="amt">R {{ number_format($certificate['total_tax'], 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="footer-text">
            {{ $company->registered_name }} -- {{ $certificate['tax_year_label'] }} tax year -- {{ $certificate['payslip_count'] }} payslip(s)
        </div>
    </div>
@endforeach

</body>
</html>
