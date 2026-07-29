@extends('layouts.public')

@section('title', 'EMP201 — ' . $run->period_label)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .recon-line {
            display: flex;
            justify-content: space-between;
            padding: 2pt 0;
            border-bottom: 0.4pt solid #ddd6fe;
            font-size: 7pt;
        }

        .recon-line:last-child { border-bottom: none; }

        @media print {
            .co-topbar, .co-sidebar, nav, footer, .reg-mgmt-bar { display:none !important; }
            .co-main { padding:0 !important; }
            .co-body { display:block !important; }
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar', ['backLabel' => 'Payroll Run', 'backRoute' => route('companies.payroll.runs.show', [$company, $run]), 'topbarMeta' => $run->period_label])

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                <div class="reg-mgmt-bar">
                    <div>
                        <div class="reg-doc-title">EMP201 Monthly Return</div>
                        <div class="reg-doc-subtitle">{{ $run->period_label }}</div>
                    </div>
                    <button onclick="window.print()" class="reg-btn">
                        <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        Print
                    </button>
                </div>

                <div class="reg-doc">
                    <div class="reg-doc-body">

                        {{-- Letterhead --}}
                        <div class="afs-letterhead">
                            <div>
                                <div class="afs-letterhead-name">EMP201 Monthly Employer Return</div>
                                <div class="afs-letterhead-meta">
                                    {{ $run->period_start->format('d M Y') }} to {{ $run->period_end->format('d M Y') }}<br>
                                    Pay Date: {{ $run->pay_date->format('d M Y') }}
                                </div>
                            </div>
                            <div class="afs-letterhead-meta afs-right">
                                <div style="font-size:9pt;font-weight:800;color:#4c1d95;margin-bottom:3pt;">{{ $company->registered_name }}</div>
                                <div style="font-size:6.5pt;color:#6b5b8a;">
                                    {{ $payslips->count() }} employee{{ $payslips->count() !== 1 ? 's' : '' }}
                                </div>
                            </div>
                        </div>

                        {{-- Employer Details --}}
                        <div class="reg-section-header" style="margin-top:8pt;">Employer Details</div>
                        <div class="recon-line"><span>Registered Name</span><strong>{{ $company->registered_name }}</strong></div>
                        <div class="recon-line"><span>PAYE Reference Number</span><strong>{{ $company->paye_number ?? 'N/A' }}</strong></div>
                        <div class="recon-line"><span>UIF Reference Number</span><strong>{{ $company->uif_number ?? 'N/A' }}</strong></div>
                        <div class="recon-line"><span>SDL Reference Number</span><strong>{{ $company->sdl_number ?? 'N/A' }}</strong></div>
                        <div class="recon-line"><span>Pay Period</span><strong>{{ $run->period_start->format('d M Y') }} to {{ $run->period_end->format('d M Y') }}</strong></div>
                        <div class="recon-line"><span>Pay Date</span><strong>{{ $run->pay_date->format('d M Y') }}</strong></div>
                        <div class="recon-line"><span>Number of Employees</span><strong>{{ $payslips->count() }}</strong></div>

                        {{-- PAYE --}}
                        <div class="reg-section-header" style="margin-top:8pt;">PAYE — Pay As You Earn</div>
                        <div class="recon-line"><span>Total Gross Taxable Remuneration</span><span>R {{ number_format($run->total_gross_earnings, 2) }}</span></div>
                        <div class="recon-line" style="font-weight:700;"><span>Total PAYE Withheld</span><strong style="color:#dc2626;">R {{ number_format($run->total_paye, 2) }}</strong></div>

                        {{-- UIF --}}
                        <div class="reg-section-header" style="margin-top:8pt;">UIF — Unemployment Insurance Fund</div>
                        <div class="recon-line"><span>Employee Contributions (1%, capped at R17,712/month)</span><span>R {{ number_format($run->total_uif_employee, 2) }}</span></div>
                        <div class="recon-line"><span>Employer Contributions (1%, capped at R17,712/month)</span><span>R {{ number_format($run->total_uif_employer, 2) }}</span></div>
                        <div class="recon-line" style="font-weight:700;"><span>Total UIF</span><strong>R {{ number_format((float)$run->total_uif_employee + (float)$run->total_uif_employer, 2) }}</strong></div>

                        {{-- SDL --}}
                        <div class="reg-section-header" style="margin-top:8pt;">SDL — Skills Development Levy</div>
                        <div class="recon-line"><span>Total Leviable Amount (gross remuneration)</span><span>R {{ number_format($run->total_gross_earnings, 2) }}</span></div>
                        <div class="recon-line" style="font-weight:700;"><span>SDL @ 1%</span><strong>R {{ number_format($run->total_sdl, 2) }}</strong></div>

                        {{-- Grand total --}}
                        <div style="background:#f5f3ff;border:1px solid #ddd6fe;padding:6pt 8pt;margin:8pt 0;">
                            <div style="display:flex;justify-content:space-between;padding:2pt 0;font-size:7pt;font-weight:800;">
                                <span>Total Amount Payable to SARS (PAYE + UIF + SDL)</span>
                                <span style="color:#4c1d95;">
                                    R {{ number_format(
                                        (float)$run->total_paye
                                        + (float)$run->total_uif_employee
                                        + (float)$run->total_uif_employer
                                        + (float)$run->total_sdl,
                                        2
                                    ) }}
                                </span>
                            </div>
                        </div>

                        {{-- Per-employee breakdown --}}
                        <div class="reg-section-header" style="margin-top:8pt;">Employee Breakdown</div>
                        <div style="overflow-x:auto;">
                            <table class="reg-table">
                                <thead>
                                    <tr>
                                        <th>Emp #</th>
                                        <th>Name</th>
                                        <th>Tax Ref</th>
                                        <th class="amt">Gross</th>
                                        <th class="amt">PAYE</th>
                                        <th class="amt">UIF (emp)</th>
                                        <th class="amt">UIF (er)</th>
                                        <th class="amt">SDL</th>
                                        <th class="amt">Net Pay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($payslips as $payslip)
                                        <tr>
                                            <td style="font-family:'DejaVu Sans Mono',monospace;color:#4c1d95;font-weight:700;">{{ $payslip->employee->employee_number }}</td>
                                            <td style="font-weight:700;">{{ $payslip->employee->full_name }}</td>
                                            <td class="dim" style="font-family:'DejaVu Sans Mono',monospace;">{{ $payslip->employee->tax_reference_number ?? '---' }}</td>
                                            <td class="amt">R {{ number_format($payslip->gross_earnings, 2) }}</td>
                                            <td class="amt" style="color:#dc2626;">R {{ number_format($payslip->paye, 2) }}</td>
                                            <td class="amt">R {{ number_format($payslip->uif_employee, 2) }}</td>
                                            <td class="amt">R {{ number_format($payslip->uif_employer, 2) }}</td>
                                            <td class="amt">R {{ number_format($payslip->sdl, 2) }}</td>
                                            <td class="amt" style="font-weight:700;color:#15803d;">R {{ number_format($payslip->net_pay, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3">Total</td>
                                        <td class="amt">R {{ number_format($run->total_gross_earnings, 2) }}</td>
                                        <td class="amt" style="color:#dc2626;">R {{ number_format($run->total_paye, 2) }}</td>
                                        <td class="amt">R {{ number_format($run->total_uif_employee, 2) }}</td>
                                        <td class="amt">R {{ number_format($run->total_uif_employer, 2) }}</td>
                                        <td class="amt">R {{ number_format($run->total_sdl, 2) }}</td>
                                        <td class="amt" style="color:#15803d;">R {{ number_format($run->total_net_pay, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <p style="font-size:6pt;color:#8b7aad;margin-top:4pt;">
                            * This EMP201 summary is generated from payroll records. Verify against SARS eFiling before submission.
                            Tax rates apply to the 2025/2026 tax year. UIF capped at R17,712/month per the Unemployment Insurance Contributions Act.
                            SDL rate 1% as per the Skills Development Levies Act.
                        </p>

                    </div>
                </div>

            </main>
        </div>
    </div>
@endsection
