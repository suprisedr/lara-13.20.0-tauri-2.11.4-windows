@extends('layouts.public')

@section('title', $certificate['certificate_type'] . ' — ' . $certificate['employee']->full_name)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .cert-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6pt;
        }

        .cert-detail-item {
            font-size: 7pt;
            margin-bottom: 2pt;
        }

        .cert-detail-item .lbl {
            font-size: 5.5pt;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #8b7aad;
            margin-bottom: 1pt;
        }

        .cert-detail-item .val {
            color: #23282d;
            font-weight: 600;
        }

        .source-code {
            font-family: 'DejaVu Sans Mono', monospace;
            color: #4c1d95;
            font-weight: 700;
            min-width: 30pt;
            display: inline-block;
        }

        .cert-line {
            display: flex;
            justify-content: space-between;
            padding: 2pt 0;
            border-bottom: 0.4pt solid #ddd6fe;
            font-size: 7pt;
        }

        .cert-line:last-child { border-bottom: none; }

        @media print {
            .co-topbar, .co-sidebar, nav, footer, .cert-actions { display:none !important; }
            .co-main { padding:0 !important; }
            .co-body { display:block !important; }
        }

        @media (max-width: 640px) {
            .cert-detail-grid { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar', ['backLabel' => 'Tax Certificates', 'backRoute' => route('companies.payroll.irp5.index', [$company, 'tax_year' => $certificate['tax_year']]), 'topbarMeta' => $certificate['certificate_type'] . ' ' . $certificate['tax_year_label']])

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                {{-- Actions --}}
                <div class="reg-mgmt-bar cert-actions">
                    <div style="display:flex;gap:4pt;">
                        <a href="{{ route('companies.payroll.irp5.pdf', [$company, $certificate['employee'], 'tax_year' => $certificate['tax_year']]) }}" class="reg-btn primary">
                            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Download PDF
                        </a>
                        <a href="javascript:window.print()" class="reg-btn">
                            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                            Print
                        </a>
                    </div>
                </div>

                <div class="reg-doc">
                    <div class="reg-doc-body">

                        {{-- Letterhead --}}
                        <div class="afs-letterhead">
                            <div>
                                <div class="afs-letterhead-name">{{ $certificate['certificate_type'] }} Tax Certificate</div>
                                <div class="afs-letterhead-meta">
                                    Tax Year: {{ $certificate['tax_year_label'] }}<br>
                                    {{ \Carbon\Carbon::parse($certificate['tax_year_start'])->format('d M Y') }} to {{ \Carbon\Carbon::parse($certificate['tax_year_end'])->format('d M Y') }}
                                </div>
                            </div>
                            <div class="afs-letterhead-meta afs-right">
                                <div style="font-size:9pt;font-weight:800;color:#4c1d95;margin-bottom:3pt;">{{ $certificate['employee']->full_name }}</div>
                                <div style="font-size:6.5pt;color:#6b5b8a;">
                                    {{ $certificate['employee']->employee_number }}<br>
                                    {{ $certificate['periods_employed'] }} month(s) employed
                                </div>
                            </div>
                        </div>

                        {{-- Employer details --}}
                        <div class="reg-section-header" style="margin-top:8pt;">Employer Details</div>
                        <div class="cert-detail-grid">
                            <div>
                                <div class="cert-detail-item"><div class="lbl">Registered Name</div><div class="val">{{ $company->registered_name }}</div></div>
                                <div class="cert-detail-item"><div class="lbl">Trading Name</div><div class="val">{{ $company->trading_name ?? $company->registered_name }}</div></div>
                                <div class="cert-detail-item"><div class="lbl">Registration Number</div><div class="val">{{ $company->registration_number ?? 'N/A' }}</div></div>
                            </div>
                            <div>
                                <div class="cert-detail-item"><div class="lbl">PAYE Reference Number</div><div class="val">{{ $company->paye_number ?? 'N/A' }}</div></div>
                                <div class="cert-detail-item"><div class="lbl">UIF Reference Number</div><div class="val">{{ $company->uif_number ?? 'N/A' }}</div></div>
                                <div class="cert-detail-item"><div class="lbl">SDL Reference Number</div><div class="val">{{ $company->sdl_number ?? 'N/A' }}</div></div>
                            </div>
                        </div>
                        @if ($company->address_line_1)
                            <div class="cert-detail-item" style="margin-top:2pt;"><div class="lbl">Address</div><div class="val">{{ implode(', ', array_filter([$company->address_line_1, $company->address_line_2, $company->city, $company->province, $company->postal_code])) }}</div></div>
                        @endif

                        {{-- Employee details --}}
                        @php $emp = $certificate['employee']; @endphp
                        <div class="reg-section-header" style="margin-top:8pt;">Employee Details</div>
                        <div class="cert-detail-grid">
                            <div>
                                <div class="cert-detail-item"><div class="lbl">Employee Number</div><div class="val">{{ $emp->employee_number }}</div></div>
                                <div class="cert-detail-item"><div class="lbl">Surname</div><div class="val">{{ $emp->last_name }}</div></div>
                                <div class="cert-detail-item"><div class="lbl">First Name(s)</div><div class="val">{{ $emp->first_name }}</div></div>
                                <div class="cert-detail-item"><div class="lbl">ID Number</div><div class="val">{{ $emp->id_number ?? 'N/A' }}</div></div>
                                @if ($emp->passport_number)
                                    <div class="cert-detail-item"><div class="lbl">Passport Number</div><div class="val">{{ $emp->passport_number }}</div></div>
                                @endif
                            </div>
                            <div>
                                <div class="cert-detail-item"><div class="lbl">Tax Reference Number</div><div class="val">{{ $emp->tax_reference_number ?? 'N/A' }}</div></div>
                                <div class="cert-detail-item"><div class="lbl">Date of Birth</div><div class="val">{{ $emp->date_of_birth?->format('d M Y') ?? 'N/A' }}</div></div>
                                <div class="cert-detail-item"><div class="lbl">Employment Type</div><div class="val">{{ ucfirst(str_replace('_', ' ', $emp->employment_type ?? '---')) }}</div></div>
                                <div class="cert-detail-item"><div class="lbl">Periods Employed</div><div class="val">{{ $certificate['periods_employed'] }} month(s)</div></div>
                                @if ($emp->address_line_1)
                                    <div class="cert-detail-item"><div class="lbl">Residential Address</div><div class="val">{{ implode(', ', array_filter([$emp->address_line_1, $emp->address_line_2, $emp->city, $emp->province, $emp->postal_code])) }}</div></div>
                                @endif
                            </div>
                        </div>

                        {{-- Income source codes --}}
                        <div class="reg-section-header" style="margin-top:8pt;">Income / Gross Remuneration</div>
                        @forelse ($certificate['income_sources'] as $source)
                            <div class="cert-line">
                                <span><span class="source-code">{{ $source['code'] }}</span> {{ $source['description'] }}</span>
                                <strong>R {{ number_format($source['amount'], 2) }}</strong>
                            </div>
                        @empty
                            <div class="cert-line"><span style="color:#8b7aad;">No income recorded</span><span>---</span></div>
                        @endforelse
                        <div class="cert-line" style="border-top:1pt solid #4c1d95;padding-top:3pt;font-weight:800;">
                            <span>Gross Remuneration</span>
                            <span>R {{ number_format($certificate['gross_remuneration'], 2) }}</span>
                        </div>

                        {{-- Deduction codes --}}
                        <div class="reg-section-header" style="margin-top:8pt;">Deductions</div>
                        @forelse ($certificate['deduction_codes'] as $deduction)
                            <div class="cert-line">
                                <span><span class="source-code">{{ $deduction['code'] }}</span> {{ $deduction['description'] }}</span>
                                <strong>R {{ number_format($deduction['amount'], 2) }}</strong>
                            </div>
                        @empty
                            <div class="cert-line"><span style="color:#8b7aad;">No deductions recorded</span><span>---</span></div>
                        @endforelse
                        <div class="cert-line" style="border-top:1pt solid #4c1d95;padding-top:3pt;font-weight:800;">
                            <span>Total Deductions</span>
                            <span>R {{ number_format($certificate['total_deductions'], 2) }}</span>
                        </div>

                        {{-- Summary --}}
                        <div style="background:#f5f3ff;border:1px solid #ddd6fe;padding:6pt 8pt;margin:8pt 0;">
                            <div style="display:flex;justify-content:space-between;padding:2pt 0;font-size:7pt;">
                                <span>Gross Remuneration</span>
                                <strong>R {{ number_format($certificate['gross_remuneration'], 2) }}</strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;padding:2pt 0;font-size:7pt;">
                                <span>Total PAYE Deducted (Code 4001)</span>
                                <strong style="color:#dc2626;">R {{ number_format($certificate['total_tax'], 2) }}</strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;padding:2pt 0;font-size:7pt;">
                                <span>Total Deductions (excl. PAYE)</span>
                                <strong>R {{ number_format($certificate['total_deductions'] - $certificate['total_tax'], 2) }}</strong>
                            </div>
                        </div>

                        <p style="font-size:6pt;color:#8b7aad;margin-top:4pt;">
                            * This {{ $certificate['certificate_type'] }} certificate is generated from posted payroll records for the {{ $certificate['tax_year_label'] }} tax year.
                            Verify against SARS requirements before submission. Source codes follow SARS IRP5 specifications.
                            Based on {{ $certificate['payslip_count'] }} payslip(s).
                        </p>

                    </div>
                </div>

            </main>
        </div>
    </div>
@endsection
