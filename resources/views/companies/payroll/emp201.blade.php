@extends('layouts.public')

@section('title', 'EMP201 — ' . $run->period_label)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .emp-box { background:#fff; border:1px solid rgba(94,23,235,0.1); border-radius:0; padding:1.25rem 1.5rem; margin-bottom:1rem; }
        .emp-title { font-size:0.62rem; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#5e17eb; margin:0 0 0.75rem; }
        .emp-row { display:flex; justify-content:space-between; padding:0.35rem 0; border-bottom:1px solid #f3f4f6; font-size:0.82rem; }
        .emp-row:last-child { border-bottom:none; }
        @media print {
            .co-topbar, .co-sidebar, nav, footer, button, a[href] { display:none !important; }
            .co-main { padding:0 !important; }
            .co-body { display:block !important; }
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar', ['backLabel' => 'Payroll Run', 'backRoute' => route('companies.payroll.runs.show', [$company, $run]), 'topbarMeta' => $run->period_label, 'topbarActions' => '<button onclick="window.print()">Print</button>'])

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                {{-- Employer details --}}
                <div class="emp-box">
                    <p class="emp-title">Employer Details</p>
                    <div class="emp-row"><span>Registered Name</span><strong>{{ $company->registered_name }}</strong></div>
                    <div class="emp-row"><span>PAYE Reference Number</span><strong>{{ $company->paye_number ?? 'N/A' }}</strong></div>
                    <div class="emp-row"><span>UIF Reference Number</span><strong>{{ $company->uif_number ?? 'N/A' }}</strong></div>
                    <div class="emp-row"><span>SDL Reference Number</span><strong>{{ $company->sdl_number ?? 'N/A' }}</strong></div>
                    <div class="emp-row"><span>Pay Period</span><strong>{{ $run->period_start->format('d M Y') }} to {{ $run->period_end->format('d M Y') }}</strong></div>
                    <div class="emp-row"><span>Pay Date</span><strong>{{ $run->pay_date->format('d M Y') }}</strong></div>
                    <div class="emp-row"><span>Number of Employees</span><strong>{{ $payslips->count() }}</strong></div>
                </div>

                {{-- PAYE --}}
                <div class="emp-box">
                    <p class="emp-title">PAYE — Pay As You Earn</p>
                    <div class="emp-row"><span>Total Gross Taxable Remuneration</span><span>R {{ number_format($run->total_gross_earnings, 2) }}</span></div>
                    <div class="emp-row"><span><strong>Total PAYE Withheld</strong></span><strong style="color:#dc2626;">R {{ number_format($run->total_paye, 2) }}</strong></div>
                </div>

                {{-- UIF --}}
                <div class="emp-box">
                    <p class="emp-title">UIF — Unemployment Insurance Fund</p>
                    <div class="emp-row"><span>Employee Contributions (1%, capped at R17,712/month)</span><span>R {{ number_format($run->total_uif_employee, 2) }}</span></div>
                    <div class="emp-row"><span>Employer Contributions (1%, capped at R17,712/month)</span><span>R {{ number_format($run->total_uif_employer, 2) }}</span></div>
                    <div class="emp-row"><span><strong>Total UIF</strong></span><strong>R {{ number_format((float)$run->total_uif_employee + (float)$run->total_uif_employer, 2) }}</strong></div>
                </div>

                {{-- SDL --}}
                <div class="emp-box">
                    <p class="emp-title">SDL — Skills Development Levy</p>
                    <div class="emp-row"><span>Total Leviable Amount (gross remuneration)</span><span>R {{ number_format($run->total_gross_earnings, 2) }}</span></div>
                    <div class="emp-row"><span><strong>SDL @ 1%</strong></span><strong>R {{ number_format($run->total_sdl, 2) }}</strong></div>
                </div>

                {{-- Grand total --}}
                <div style="background:#f5f3ff;border:1.5px solid #ddd6fe;border-radius:0;padding:1rem 1.5rem;display:flex;justify-content:space-between;font-weight:800;font-size:0.95rem;margin-bottom:1.5rem;">
                    <span>Total Amount Payable to SARS (PAYE + UIF + SDL)</span>
                    <span style="color:#5e17eb;">
                        R {{ number_format(
                            (float)$run->total_paye
                            + (float)$run->total_uif_employee
                            + (float)$run->total_uif_employer
                            + (float)$run->total_sdl,
                            2
                        ) }}
                    </span>
                </div>

                {{-- Per-employee breakdown --}}
                <div class="emp-box">
                    <p class="emp-title">Employee Breakdown</p>
                    <div style="overflow-x:auto;">
                        <table class="co-table" style="font-size:0.78rem;">
                            <thead>
                                <tr>
                                    <th>Emp #</th>
                                    <th>Name</th>
                                    <th>Tax Ref</th>
                                    <th style="text-align:right;">Gross</th>
                                    <th style="text-align:right;">PAYE</th>
                                    <th style="text-align:right;">UIF (emp)</th>
                                    <th style="text-align:right;">UIF (er)</th>
                                    <th style="text-align:right;">SDL</th>
                                    <th style="text-align:right;">Net Pay</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payslips as $payslip)
                                    <tr>
                                        <td style="font-family:monospace;">{{ $payslip->employee->employee_number }}</td>
                                        <td>{{ $payslip->employee->full_name }}</td>
                                        <td style="font-family:monospace;color:#888;">{{ $payslip->employee->tax_reference_number ?? '—' }}</td>
                                        <td style="text-align:right;">R {{ number_format($payslip->gross_earnings, 2) }}</td>
                                        <td style="text-align:right;color:#dc2626;">R {{ number_format($payslip->paye, 2) }}</td>
                                        <td style="text-align:right;">R {{ number_format($payslip->uif_employee, 2) }}</td>
                                        <td style="text-align:right;">R {{ number_format($payslip->uif_employer, 2) }}</td>
                                        <td style="text-align:right;">R {{ number_format($payslip->sdl, 2) }}</td>
                                        <td style="text-align:right;font-weight:700;color:#15803d;">R {{ number_format($payslip->net_pay, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr style="font-weight:700;background:#f9fafb;">
                                    <td colspan="3">Total</td>
                                    <td style="text-align:right;">R {{ number_format($run->total_gross_earnings, 2) }}</td>
                                    <td style="text-align:right;color:#dc2626;">R {{ number_format($run->total_paye, 2) }}</td>
                                    <td style="text-align:right;">R {{ number_format($run->total_uif_employee, 2) }}</td>
                                    <td style="text-align:right;">R {{ number_format($run->total_uif_employer, 2) }}</td>
                                    <td style="text-align:right;">R {{ number_format($run->total_sdl, 2) }}</td>
                                    <td style="text-align:right;color:#15803d;">R {{ number_format($run->total_net_pay, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <p style="font-size:0.72rem;color:#888;margin-top:0.5rem;">
                    * This EMP201 summary is generated from payroll records. Verify against SARS eFiling before submission.
                    Tax rates apply to the 2025/2026 tax year. UIF capped at R17,712/month per the Unemployment Insurance Contributions Act.
                    SDL rate 1% as per the Skills Development Levies Act.
                </p>

            </main>
        </div>
    </div>
@endsection
