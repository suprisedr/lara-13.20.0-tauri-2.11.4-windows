{{-- Shared AFS PDF letterhead. Expects $company.

     Detail set matches pdf/_report-header.blade.php — the treatment
     used by the trial-balance report: logo, company name, entity
     type, full postal address on the left; registration, income tax
     and VAT numbers plus the financial year end on the right.

     Typography follows the AFS document rather than the A4 report
     header: 13pt bold navy name, 10pt navy meta. --}}
@php
    $monthNames = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
                   7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
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

    $lhLogoData = null;
    if ($company->logo_path) {
        $lhLogoPath = storage_path('app/public/' . $company->logo_path);
        if (file_exists($lhLogoPath)) {
            $lhExt  = strtolower(pathinfo($lhLogoPath, PATHINFO_EXTENSION));
            $lhMime = $lhExt === 'png' ? 'image/png' : ($lhExt === 'gif' ? 'image/gif' : 'image/jpeg');
            $lhLogoData = 'data:' . $lhMime . ';base64,' . base64_encode(file_get_contents($lhLogoPath));
        }
    }
@endphp
<div class="afs-letterhead-pdf">
    @if ($lhLogoData)
        <div class="lh-logo">
            <img src="{{ $lhLogoData }}" class="lh-logo-img" alt="">
        </div>
    @endif
    <div class="lh-left">
        <div class="company-name">{{ $company->registered_name }}</div>
        @if ($company->company_type_label)
            <div class="company-sub">{{ $company->company_type_label }}</div>
        @endif
        @if ($addressLine)
            <div class="company-meta">{{ $addressLine }}</div>
        @endif
    </div>
    <div class="lh-right">
        @if ($company->registration_number)
            <div class="company-meta"><strong>Registration No.:</strong> {{ $company->registration_number }}</div>
        @endif
        @if ($company->income_tax_number)
            <div class="company-meta"><strong>Income Tax No.:</strong> {{ $company->income_tax_number }}</div>
        @endif
        @if ($company->vat_number)
            <div class="company-meta"><strong>VAT No.:</strong> {{ $company->vat_number }}</div>
        @endif
        @if ($fyeLabel)
            <div class="company-meta">{{ $fyeLabel }}</div>
        @endif
    </div>
</div>
