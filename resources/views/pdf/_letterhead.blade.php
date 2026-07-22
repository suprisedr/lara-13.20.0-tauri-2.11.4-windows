{{-- Shared AFS PDF letterhead. Expects $company. --}}
@php
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
        <div class="company-meta">
            {{ $company->company_type_label ?? '' }}
            @if ($company->registration_number)
                &nbsp;&nbsp;Registration number: {{ $company->registration_number }}
            @endif
            @if ($company->income_tax_number)
                &nbsp;&nbsp;Income tax number: {{ $company->income_tax_number }}
            @endif
        </div>
        @if ($company->address_line_1 || $company->city)
            <div class="company-meta">
                {{ implode(', ', array_filter([$company->address_line_1, $company->city])) }}
            </div>
        @endif
    </div>
    <div class="lh-right">
        <div class="company-meta">Date: {{ now()->format('d F Y') }}</div>
        <div class="company-meta">Prepared by: {{ auth()->user()?->name ?? $company->registered_name }}</div>
    </div>
</div>
