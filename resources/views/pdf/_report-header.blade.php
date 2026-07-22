{{--
    Professional financial-report header for PDF statements.

    Expects:
        $company         — App\Models\Company
        $reportTitle     — string (e.g. "Statement of Financial Position")
        $reportPeriod    — string (e.g. "As at 28 February 2026" or "For the year ended …")
        $roundingLabel   — string (e.g. "R", "R'000", "R'm")
        $compare         — bool (optional, controls 4th column width)
--}}
@php
    $monthNames = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
    $fyeMonth = $company->financial_year_end_month ?? null;
    $fyeLabel = $fyeMonth && isset($monthNames[$fyeMonth])
        ? 'Financial year ending ' . $monthNames[$fyeMonth]
        : null;

    $addressParts = array_filter([
        $company->address_line_1 ?? null,
        $company->address_line_2 ?? null,
        $company->city ?? null,
        $company->province ?? null,
        $company->postal_code ?? null,
    ]);
    $addressLine = !empty($addressParts) ? implode(', ', $addressParts) : null;

    $rptLogoData = null;
    if ($company->logo_path) {
        $rptLogoPath = storage_path('app/public/' . $company->logo_path);
        if (file_exists($rptLogoPath)) {
            $rptExt  = strtolower(pathinfo($rptLogoPath, PATHINFO_EXTENSION));
            $rptMime = $rptExt === 'png' ? 'image/png' : ($rptExt === 'gif' ? 'image/gif' : 'image/jpeg');
            $rptLogoData = 'data:' . $rptMime . ';base64,' . base64_encode(file_get_contents($rptLogoPath));
        }
    }
@endphp

<div class="rpt-letterhead">
    @if ($rptLogoData)
        <div class="rpt-lh-logo">
            <img src="{{ $rptLogoData }}" class="rpt-logo-img" alt="">
        </div>
    @endif
    <div class="rpt-lh-left">
        <div class="rpt-company-name">{{ $company->registered_name }}</div>
        @if ($company->company_type_label)
            <div class="rpt-company-sub">{{ $company->company_type_label }}</div>
        @endif
        @if ($addressLine)
            <div class="rpt-company-meta">{{ $addressLine }}</div>
        @endif
    </div>
    <div class="rpt-lh-right">
        @if ($company->registration_number)
            <div class="rpt-company-meta"><strong>Registration No.:</strong> {{ $company->registration_number }}</div>
        @endif
        @if ($company->income_tax_number)
            <div class="rpt-company-meta"><strong>Income Tax No.:</strong> {{ $company->income_tax_number }}</div>
        @endif
        @if ($company->vat_number)
            <div class="rpt-company-meta"><strong>VAT No.:</strong> {{ $company->vat_number }}</div>
        @endif
        @if ($fyeLabel)
            <div class="rpt-company-meta">{{ $fyeLabel }}</div>
        @endif
    </div>
</div>

<div class="rpt-doc-header">
    <div class="rpt-doc-title">{{ $reportTitle }}</div>
    <div class="rpt-doc-subtitle">{{ $reportPeriod }}</div>
    <div class="rpt-doc-currency">
        Presented in South African Rand
        @if (!empty($roundingLabel) && $roundingLabel !== 'R')
            &mdash; figures in {{ $roundingLabel }}
        @endif
    </div>
</div>

