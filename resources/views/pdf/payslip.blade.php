<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Payslip — {{ $payslip->employee->full_name }}</title>
    <style>
        @page {
            margin: 0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 7.5pt;
            color: #000;
            background: #fff;
            line-height: 1.35;
            padding: 50px 60px 100px 60px;
        }

        .company-logo {
            max-width: 280px;
            max-height: 100px;
        }

        .company-name-fallback {
            font-size: 16pt;
            font-weight: bold;
        }

        .address-block {
            text-align: right;
            font-size: 7.5pt;
            line-height: 1.5;
        }

        .meta-label {
            font-weight: bold;
            font-size: 7.5pt;
            display: inline-block;
            width: 140px;
        }

        .meta-value {
            font-size: 7.5pt;
        }

        .divider {
            border: none;
            border-top: 2px solid #000;
            margin: 16px 0 20px 0;
        }

        .content-cols {
            width: 100%;
            border-collapse: collapse;
        }

        .content-cols > tbody > tr > td {
            vertical-align: top;
            width: 50%;
        }

        .content-cols > tbody > tr > td.col-left {
            padding-right: 24px;
        }

        .content-cols > tbody > tr > td.col-right {
            padding-left: 24px;
        }

        .section-head {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .section-head td {
            font-weight: bold;
            font-size: 8pt;
            padding-bottom: 4px;
            border-bottom: 1.5px solid #000;
        }

        .section-head td.amt {
            text-align: right;
        }

        .line-item {
            width: 100%;
            border-collapse: collapse;
        }

        .line-item td {
            font-size: 7.5pt;
            padding: 3px 0;
        }

        .line-item td.amt {
            text-align: right;
        }

        .nett-pay-wrapper {
            position: fixed;
            bottom: 20px;
            left: 60px;
            right: 60px;
        }

        .nett-pay-bar {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 10px 0;
        }

        .nett-pay-bar table {
            width: 100%;
            border-collapse: collapse;
        }

        .nett-pay-bar td {
            font-size: 10pt;
            font-weight: bold;
        }

        .nett-pay-bar td.amt {
            text-align: right;
        }

        .footer-text {
            text-align: center;
            font-size: 7pt;
            color: #666;
            margin-top: 8px;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
        <tr>
            <td style="vertical-align:top;width:55%;">
                @php
                    $logoData = null;
                    if ($company->logo_path) {
                        $logoPath = storage_path('app/public/' . $company->logo_path);
                        if (file_exists($logoPath)) {
                            $ext  = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                            $mime = $ext === 'png' ? 'image/png' : ($ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : 'image/png');
                            $logoData = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
                        }
                    }
                @endphp
                @if ($logoData)
                    <img src="{{ $logoData }}" class="company-logo" alt="">
                @else
                    <div class="company-name-fallback">{{ $company->registered_name }}</div>
                @endif
            </td>
            <td style="vertical-align:top;width:45%;">
                <div class="address-block">
                    @if ($company->address_line_1)<div>{{ $company->address_line_1 }}</div>@endif
                    @if ($company->address_line_2)<div>{{ $company->address_line_2 }}</div>@endif
                    @if ($company->city)<div>{{ $company->city }}</div>@endif
                    @if ($company->province)<div>{{ $company->province }}</div>@endif
                    @if ($company->postal_code)<div>{{ $company->postal_code }}</div>@endif
                    @if ($company->registration_number)<div>Reg. No: {{ $company->registration_number }}</div>@endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Employee meta --}}
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="vertical-align:top;width:50%;">
                <div><span class="meta-label">Employee Name</span><span class="meta-value">{{ $payslip->employee->full_name }}</span></div>
                <div><span class="meta-label">Pay Period</span><span class="meta-value">{{ $payslip->payrollRun->period_start->format('Y-m-d') }} to {{ $payslip->payrollRun->period_end->format('Y-m-d') }}</span></div>
                <div><span class="meta-label">Pay Date</span><span class="meta-value">{{ $payslip->pay_date ? \Carbon\Carbon::parse($payslip->pay_date)->format('Y-m-d') : '—' }}</span></div>
                <div><span class="meta-label">Pay Frequency</span><span class="meta-value">{{ ucfirst($payslip->payrollRun->pay_frequency ?? $payslip->employee->pay_frequency ?? 'Monthly') }}</span></div>
                <div><span class="meta-label">ID Number</span><span class="meta-value">{{ $payslip->employee->id_number ?? '—' }}</span></div>
            </td>
            <td style="vertical-align:top;width:50%;">
                <div><span class="meta-label">Employee Number</span><span class="meta-value">{{ $payslip->employee->employee_number }}</span></div>
                <div><span class="meta-label">Job Title</span><span class="meta-value">{{ $payslip->employee->job_title ?? '—' }}</span></div>
                <div><span class="meta-label">Income Tax Number</span><span class="meta-value">{{ $payslip->employee->tax_reference_number ?? '—' }}</span></div>
                <div><span class="meta-label">Employment Date</span><span class="meta-value">{{ $payslip->employee->start_date->format('Y-m-d') }}</span></div>
                @if ($payslip->employee->address_line_1)
                    <div style="margin-top:6px;">
                        <span class="meta-label">Address</span>
                        <span class="meta-value">
                            {{ $payslip->employee->address_line_1 }}
                            @if ($payslip->employee->address_line_2), {{ $payslip->employee->address_line_2 }}@endif
                            @if ($payslip->employee->city), {{ $payslip->employee->city }}@endif
                            @if ($payslip->employee->province), {{ $payslip->employee->province }}@endif
                            @if ($payslip->employee->postal_code), {{ $payslip->employee->postal_code }}@endif
                        </span>
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <hr class="divider">

    {{-- Income / Employer Contribution side by side --}}
    <table class="content-cols">
        <tr>
            <td class="col-left">

                {{-- Income --}}
                <table class="section-head">
                    <tr>
                        <td>Income</td>
                        <td class="amt">{{ number_format($payslip->gross_earnings, 2) }}</td>
                    </tr>
                </table>
                <table class="line-item">
                    @foreach ($payslip->earningLines as $line)
                        <tr>
                            <td>{{ $line->description }}</td>
                            <td class="amt">{{ number_format($line->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </table>

                {{-- Deduction --}}
                <table class="section-head">
                    <tr>
                        <td>Deduction</td>
                        <td class="amt">{{ number_format($payslip->total_deductions, 2) }}</td>
                    </tr>
                </table>
                <table class="line-item">
                    @foreach ($payslip->deductionLines as $line)
                        <tr>
                            <td>{{ $line->description }}{{ $line->is_statutory ? ' *' : '' }}</td>
                            <td class="amt">{{ number_format($line->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </table>

            </td>
            <td class="col-right">

                {{-- Employer Contribution --}}
                <table class="section-head">
                    <tr>
                        <td>Employer Contribution</td>
                        <td class="amt">{{ number_format($payslip->employerLines->sum('amount'), 2) }}</td>
                    </tr>
                </table>
                <table class="line-item">
                    @foreach ($payslip->employerLines as $line)
                        <tr>
                            <td>{{ $line->description }}</td>
                            <td class="amt">{{ number_format($line->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </table>

            </td>
        </tr>
    </table>

    {{-- Nett Pay pinned to bottom --}}
    <div class="nett-pay-wrapper">
        <div class="nett-pay-bar">
            <table>
                <tr>
                    <td>NETT PAY</td>
                    <td class="amt">R {{ number_format($payslip->net_pay, 2) }}</td>
                </tr>
            </table>
        </div>
        <div class="footer-text">
            {{ $company->registered_name }}
        </div>
    </div>

</body>
</html>
