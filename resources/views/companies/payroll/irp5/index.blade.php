@extends('layouts.public')

@section('title', 'IRP5/IT3(a) Tax Certificates')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar', ['backLabel' => 'Payroll Runs', 'backRoute' => route('companies.payroll.runs.index', $company), 'topbarMeta' => 'Tax Certificates'])

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                <div class="reg-doc">
                    <div class="reg-doc-body">

                        <div class="reg-mgmt-bar">
                            <div>
                                <div class="reg-doc-title">IRP5/IT3(a) Tax Certificates</div>
                                <div class="reg-doc-subtitle">
                                    <form method="GET" action="{{ route('companies.payroll.irp5.index', $company) }}" style="display:inline-flex;align-items:center;gap:3pt;">
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
                            <a href="{{ route('companies.payroll.irp5.bulk-pdf', [$company, 'tax_year' => $selectedYear]) }}" class="reg-btn primary">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                Download All IRP5s (PDF)
                            </a>
                        </div>

                        <hr class="reg-divider">

                        <div style="overflow-x:auto;">
                            <table class="reg-table">
                                <thead>
                                    <tr>
                                        <th>Emp #</th>
                                        <th>Employee Name</th>
                                        <th>ID / Passport</th>
                                        <th>Tax Reference</th>
                                        <th>Status</th>
                                        <th class="amt">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($employeeData as $item)
                                        <tr>
                                            <td style="font-family:'DejaVu Sans Mono',monospace;color:#1a345b;font-weight:700;">{{ $item['employee']->employee_number }}</td>
                                            <td style="font-weight:700;">{{ $item['employee']->full_name }}</td>
                                            <td class="dim" style="font-family:'DejaVu Sans Mono',monospace;">{{ $item['employee']->id_number ?? $item['employee']->passport_number ?? '---' }}</td>
                                            <td class="dim" style="font-family:'DejaVu Sans Mono',monospace;">{{ $item['employee']->tax_reference_number ?? '---' }}</td>
                                            <td>
                                                @if ($item['has_data'])
                                                    <span class="reg-status" style="color:#15803d;border-color:#15803d;background:#dcfce7;">Ready</span>
                                                @else
                                                    <span class="reg-status" style="color:#6f869b;border-color:#9ec1f5;background:#f4fafc;">No Data</span>
                                                @endif
                                            </td>
                                            <td class="amt">
                                                @if ($item['has_data'])
                                                    <a href="{{ route('companies.payroll.irp5.show', [$company, $item['employee'], 'tax_year' => $selectedYear]) }}" class="reg-link" style="margin-right:4pt;">
                                                        Preview
                                                    </a>
                                                    <a href="{{ route('companies.payroll.irp5.pdf', [$company, $item['employee'], 'tax_year' => $selectedYear]) }}" class="reg-link">
                                                        PDF
                                                    </a>
                                                @else
                                                    <span style="color:#9ec1f5;">---</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" style="text-align:center;color:#6f869b;padding:12pt;">No employees found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <p style="font-size:6pt;color:#6f869b;margin-top:6pt;">
                            * IRP5 certificates are generated from posted payroll runs. Only employees with posted payslips in the selected tax year will show as "Ready".
                        </p>

                    </div>
                </div>

            </main>
        </div>
    </div>
@endsection
