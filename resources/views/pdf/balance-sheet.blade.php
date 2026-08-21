@php
    $roundingLabel = match ($rounding ?? 1) { 1000 => "R'000", 1000000 => "R'm", default => 'R' };
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $company->registered_name }} — Statement of Financial Position</title>
    @include('pdf._afs-styles')
</head>

<body>
    <div class="page-frame">
        @include('pdf._letterhead')

        <div class="afs-doc-header">
            <div class="afs-doc-title">Statement of Financial Position</div>
            <div class="afs-doc-subtitle">as at {{ \Carbon\Carbon::parse($asOfDate)->format('d F Y') }}</div>
            <div class="afs-doc-currency">
                Presented in South African Rand{!! (!empty($roundingLabel) && $roundingLabel !== 'R') ? ' &mdash; figures in ' . e($roundingLabel) : '' !!}
            </div>
        </div>

        @include('companies.reports._partials.sofp')

        <div class="afs-footer">
            {{ $company->registered_name }} &middot; Statement of Financial Position &middot;
            Generated {{ now()->format('d F Y') }}
        </div>
    </div>
    @include('pdf._attribution')
</body>

</html>
