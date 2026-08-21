@extends('layouts.public')

@section('title', 'EMP501 Reconciliation — ' . $report['tax_year_label'])
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .recon-line {
            display: flex;
            justify-content: space-between;
            padding: 2pt 0;
            border-bottom: 0.4pt solid #d3e2f5;
            font-size: 7pt;
        }

        .recon-line:last-child { border-bottom: none; }

        .var-positive { color: #dc2626; }
        .var-zero { color: #15803d; }
        .var-negative { color: #dc2626; }

        @media print {
            .co-topbar, .co-sidebar, nav, footer, .emp501-toolbar { display:none !important; }
            .co-main { padding:0 !important; }
            .co-body { display:block !important; }
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar', ['backLabel' => 'Payroll Runs', 'backRoute' => route('companies.payroll.runs.index', $company), 'topbarMeta' => 'EMP501 Reconciliation'])

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                <div class="reg-doc">
                    <div class="reg-doc-body">

                        <div class="reg-mgmt-bar emp501-toolbar">
                            <div>
                                <div class="reg-doc-title">EMP501 Reconciliation</div>
                                <div class="reg-doc-subtitle">
                                    <form method="GET" action="{{ route('companies.payroll.emp501.index', $company) }}" style="display:inline-flex;align-items:center;gap:3pt;">
                                        <span>Tax Year:</span>
                                        <select name="tax_year" id="tax_year" onchange="this.form.submit()" style="border:0.5pt solid #9ec1f5;padding:1pt 4pt;font-size:6.5pt;background:#fff;color:#1a345b;font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;">
                                            @foreach ($taxYears as $year)
                                                <option value="{{ $year }}" {{ $year === $selectedYear ? 'selected' : '' }}>
                                                    {{ $year - 1 }}/{{ $year }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                            </div>
                            <a href="{{ route('companies.payroll.emp501.pdf', [$company, 'tax_year' => $selectedYear]) }}" class="reg-btn primary">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                Download PDF
                            </a>
                        </div>

                        <hr class="reg-divider">

                        {{-- Summary --}}
                        <div class="reg-section-header">Reconciliation Summary</div>
                        <div class="recon-line"><span>Tax Year</span><strong>{{ $report['tax_year_label'] }}</strong></div>
                        <div class="recon-line"><span>Period</span><strong>{{ \Carbon\Carbon::parse($report['tax_year_start'])->format('d M Y') }} to {{ \Carbon\Carbon::parse($report['tax_year_end'])->format('d M Y') }}</strong></div>
                        <div class="recon-line"><span>Total Employees</span><strong>{{ $report['total_employees'] }}</strong></div>
                        <div class="recon-line"><span>Total Payroll Runs</span><strong>{{ $report['total_runs'] }}</strong></div>
                        <div class="recon-line"><span>IRP5 Certificates Generated</span><strong>{{ $report['irp5_totals']['certificates'] }}</strong></div>

                        {{-- Employer details --}}
                        <div class="reg-section-header" style="margin-top:8pt;">Employer Details</div>
                        <div class="recon-line"><span>Registered Name</span><strong>{{ $company->registered_name }}</strong></div>
                        <div class="recon-line"><span>PAYE Reference Number</span><strong>{{ $company->paye_number ?? 'N/A' }}</strong></div>
                        <div class="recon-line"><span>UIF Reference Number</span><strong>{{ $company->uif_number ?? 'N/A' }}</strong></div>
                        <div class="recon-line"><span>SDL Reference Number</span><strong>{{ $company->sdl_number ?? 'N/A' }}</strong></div>

                        {{-- EMP201 Totals --}}
                        <div class="reg-section-header" style="margin-top:8pt;">EMP201 Declared Totals (Monthly Returns)</div>
                        <div class="recon-line"><span>Gross Remuneration</span><span>R {{ number_format($report['emp201_totals']['gross_remuneration'], 2) }}</span></div>
                        <div class="recon-line"><span>Total PAYE</span><span style="color:#dc2626;">R {{ number_format($report['emp201_totals']['paye'], 2) }}</span></div>
                        <div class="recon-line"><span>Total UIF (employee + employer)</span><span>R {{ number_format($report['emp201_totals']['total_uif'], 2) }}</span></div>
                        <div class="recon-line"><span>Total SDL</span><span>R {{ number_format($report['emp201_totals']['sdl'], 2) }}</span></div>
                        <div class="recon-line" style="font-weight:700;"><span>Total Liability Declared</span><strong style="color:#1a345b;">R {{ number_format($report['emp201_totals']['total_liability'], 2) }}</strong></div>

                        {{-- Variance Analysis --}}
                        <div class="reg-section-header" style="margin-top:8pt;">Variance Analysis (EMP201 vs IRP5 Certificates)</div>
                        <div style="overflow-x:auto;">
                            <table class="reg-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="amt">EMP201 Declared</th>
                                        <th class="amt">IRP5 Certificates</th>
                                        <th class="amt">Variance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (['gross_remuneration' => 'Gross Remuneration', 'paye' => 'PAYE', 'uif' => 'UIF (employee)', 'sdl' => 'SDL'] as $key => $label)
                                        <tr>
                                            <td>{{ $label }}</td>
                                            <td class="amt">R {{ number_format($report['variance'][$key]['emp201'], 2) }}</td>
                                            <td class="amt">R {{ number_format($report['variance'][$key]['irp5'], 2) }}</td>
                                            <td class="amt {{ $report['variance'][$key]['diff'] == 0 ? 'var-zero' : ($report['variance'][$key]['diff'] > 0 ? 'var-positive' : 'var-negative') }}">
                                                R {{ number_format(abs($report['variance'][$key]['diff']), 2) }}
                                                @if ($report['variance'][$key]['diff'] != 0)
                                                    ({{ $report['variance'][$key]['diff'] > 0 ? 'over' : 'under' }})
                                                @else
                                                    (balanced)
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Monthly Breakdown --}}
                        <div class="reg-section-header" style="margin-top:8pt;">Monthly Breakdown (EMP201 Periods)</div>
                        <div style="overflow-x:auto;">
                            <table class="reg-table">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th class="amt">Employees</th>
                                        <th class="amt">Gross</th>
                                        <th class="amt">PAYE</th>
                                        <th class="amt">UIF (emp)</th>
                                        <th class="amt">UIF (er)</th>
                                        <th class="amt">SDL</th>
                                        <th class="amt">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $mGross = 0; $mPaye = 0; $mUifE = 0; $mUifR = 0; $mSdl = 0; $mTotal = 0;
                                    @endphp
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
                                            <td class="amt">R {{ number_format($month['gross'], 2) }}</td>
                                            <td class="amt" style="color:#dc2626;">R {{ number_format($month['paye'], 2) }}</td>
                                            <td class="amt">R {{ number_format($month['uif_employee'], 2) }}</td>
                                            <td class="amt">R {{ number_format($month['uif_employer'], 2) }}</td>
                                            <td class="amt">R {{ number_format($month['sdl'], 2) }}</td>
                                            <td class="amt" style="font-weight:700;">R {{ number_format($month['total_liability'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total</td>
                                        <td></td>
                                        <td class="amt">R {{ number_format($mGross, 2) }}</td>
                                        <td class="amt" style="color:#dc2626;">R {{ number_format($mPaye, 2) }}</td>
                                        <td class="amt">R {{ number_format($mUifE, 2) }}</td>
                                        <td class="amt">R {{ number_format($mUifR, 2) }}</td>
                                        <td class="amt">R {{ number_format($mSdl, 2) }}</td>
                                        <td class="amt" style="color:#1a345b;">R {{ number_format($mTotal, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <p style="font-size:6pt;color:#6f869b;margin-top:4pt;">
                            * This EMP501 reconciliation is generated from payroll records and IRP5 certificate data.
                            Verify against SARS eFiling before submission. Variances should be investigated and resolved before filing.
                        </p>

                    </div>
                </div>

            </main>
        </div>
    </div>
@endsection
