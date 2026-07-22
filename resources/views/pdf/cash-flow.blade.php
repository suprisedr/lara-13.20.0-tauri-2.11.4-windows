<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $company->registered_name }} — Statement of Cash Flows</title>
    @include('pdf._afs-styles')
</head>

<body>
    <div class="page-frame">
        @include('pdf._letterhead')

        <div class="afs-doc-header">
            <div class="afs-doc-title">Statement of Cash Flows</div>
            <div class="afs-doc-subtitle">
                for the period {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} to
                {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}
            </div>
        </div>

        @include('companies.reports._partials.socf')

        <div class="afs-footer">
            {{ $company->registered_name }} &middot; Statement of Cash Flows &middot;
            Generated {{ now()->format('d F Y') }}
        </div>
    </div>
</body>

</html>
