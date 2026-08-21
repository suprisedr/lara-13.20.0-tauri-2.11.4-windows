@php
    $roundingLabel = match ($rounding ?? 1) { 1000 => "R'000", 1000000 => "R'm", default => 'R' };
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $company->registered_name }} — Statement of Changes in Equity</title>
    @include('pdf._afs-styles')
</head>

<body>
    <div class="page-frame">
        @include('pdf._letterhead')

        <div class="afs-doc-header">
            <div class="afs-doc-title">Statement of Changes in Equity</div>
            <div class="afs-doc-subtitle">
                for the period {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} to
                {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}
            </div>
            <div class="afs-doc-currency">
                Presented in South African Rand{!! (!empty($roundingLabel) && $roundingLabel !== 'R') ? ' &mdash; figures in ' . e($roundingLabel) : '' !!}
            </div>
        </div>

        @include('companies.reports._partials.soce')

        <div class="afs-footer">
            {{ $company->registered_name }} &middot; Statement of Changes in Equity &middot;
            Generated {{ now()->format('d F Y') }}
        </div>
    </div>
    @include('pdf._attribution')
</body>

</html>
