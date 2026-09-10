@extends('layouts.public')

@section('title', $company->registered_name . ' — Edit Cash Flow Statement')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .cfm-section-head {
            font-size: 0.68rem; font-weight: 800; text-transform: uppercase;
            letter-spacing: 0.08em; color: #005bf0; background: #f4fafc;
            padding: 0.45rem 1.25rem; border-top: 1px solid #d3e2f5;
            border-bottom: 1px solid #d3e2f5;
        }
        .cfm-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
        .cfm-table th {
            text-align: left; font-size: 0.66rem; font-weight: 700;
            letter-spacing: 0.06em; text-transform: uppercase; color: #5a7186;
            padding: 0.45rem 1.25rem; border-bottom: 1.5px solid #d3e2f5;
        }
        .cfm-table th.num { text-align: right; }
        .cfm-table td { padding: 0.35rem 1.25rem; border-bottom: 0.5px solid #f4fafc; vertical-align: middle; }
        .cfm-label { color: #191919; }
        .cfm-input {
            font-family: inherit; font-size: 0.82rem; border: 1.5px solid #d3e2f5;
            border-radius: 0; padding: 0.25rem 0.5rem; width: 100%;
            text-align: right; background: #fff; box-sizing: border-box;
        }
        .cfm-input:focus { outline: none; border-color: #005bf0; }
        .cfm-name-input {
            font-family: inherit; font-size: 0.82rem; border: 1.5px solid #d3e2f5;
            border-radius: 0; padding: 0.25rem 0.5rem; width: 100%;
            background: #fff; box-sizing: border-box;
        }
        .cfm-name-input:focus { outline: none; border-color: #005bf0; }
        .cfm-remove {
            background: none; border: none; color: #d3e2f5; cursor: pointer;
            font-size: 1rem; line-height: 1; padding: 0.15rem 0.3rem; border-radius: 0;
        }
        .cfm-remove:hover { color: #ef4444; }
        .cfm-add-btn {
            display: inline-flex; align-items: center; gap: 0.3rem;
            font-size: 0.75rem; font-weight: 700; color: #005bf0;
            background: none; border: 1.5px dashed #9ec1f5; border-radius: 0;
            padding: 0.3rem 0.75rem; cursor: pointer; font-family: inherit;
            margin: 0.5rem 1.25rem;
        }
        .cfm-add-btn:hover { background: #f4fafc; }
    </style>
@endpush

@section('content')
@php
    $hasPrior  = $showPriorInputs;
    $curYear   = \Carbon\Carbon::parse($endDate)->format('Y');
    $priYear   = \Carbon\Carbon::parse($priorEnd)->format('Y');
    $labelCol  = $hasPrior ? '44%' : '70%';
    $amtCol    = $hasPrior ? '24%' : '26%';
@endphp
<div class="co-wrap">
    <div class="co-body">
        @include('companies._sidebar')
        <main class="co-main" style="padding:1.25rem 1.5rem;">
            <div class="co-card">
                <div class="co-card-head">
                    <div>
                        <h2 style="font-size:1.05rem;font-weight:800;color:#191919;margin:0;">
                            Edit Cash Flow Statement
                        </h2>
                        @if ($hasPrior)
                            <p style="font-size:0.78rem;color:#5a7186;margin:0.2rem 0 0;">
                                No prior-year data found for {{ $priYear }} — enter it below alongside {{ $curYear }}.
                            </p>
                        @endif
                    </div>
                    <a href="{{ route('companies.reports.cash-flow', [$company, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
                        style="display:inline-flex;align-items:center;gap:0.35rem;background:#f4fafc;color:#191919;border-radius:0;padding:0.4rem 0.85rem;font-size:0.74rem;font-weight:700;text-decoration:none;white-space:nowrap;">
                        &larr; View Statement
                    </a>
                </div>

                <form method="POST" action="{{ route('companies.cash-flow-manual.save', $company) }}"
                      style="padding-bottom:1.5rem;">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="period_start" value="{{ $startDate }}">
                    <input type="hidden" name="period_end" value="{{ $endDate }}">
                    <input type="hidden" name="show_prior" value="{{ $hasPrior ? '1' : '0' }}">
                    <input type="hidden" name="prior_period_start" value="{{ $priorStart }}">
                    <input type="hidden" name="prior_period_end" value="{{ $priorEnd }}">

                    {{-- ══ OPERATING ══ --}}
                    <div class="cfm-section-head">Cash flows from operating activities</div>
                    <table class="cfm-table">
                        <thead>
                            <tr>
                                <th style="width:{{ $labelCol }};">Line item</th>
                                <th class="num" style="width:{{ $amtCol }};">
                                    {{ $hasPrior ? $curYear . ' (R)' : 'Amount (R)' }}
                                </th>
                                @if ($hasPrior)
                                    <th class="num" style="width:{{ $amtCol }};">{{ $priYear }} (R)</th>
                                @endif
                                <th style="width:4%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($operatingFixed as $key => $row)
                                <tr>
                                    <td class="cfm-label">{{ $row['label'] }}</td>
                                    <td>
                                        <input class="cfm-input" type="number" step="0.01"
                                               name="operating[{{ $key }}][cur]"
                                               value="{{ old("operating.{$key}.cur", $row['entry']?->current_amount ?? '') }}">
                                    </td>
                                    @if ($hasPrior)
                                        <td>
                                            <input class="cfm-input" type="number" step="0.01"
                                                   name="operating[{{ $key }}][pri]"
                                                   value="{{ old("operating.{$key}.pri", $row['prior']?->current_amount ?? '') }}">
                                        </td>
                                    @endif
                                    <td></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- ══ INVESTING ══ --}}
                    <div class="cfm-section-head">Cash flows from investing activities</div>
                    <table class="cfm-table">
                        <thead>
                            <tr>
                                <th style="width:{{ $labelCol }};">Line item</th>
                                <th class="num" style="width:{{ $amtCol }};">
                                    {{ $hasPrior ? $curYear . ' (R)' : 'Amount (R)' }}
                                </th>
                                @if ($hasPrior)
                                    <th class="num" style="width:{{ $amtCol }};">{{ $priYear }} (R)</th>
                                @endif
                                <th style="width:4%;"></th>
                            </tr>
                        </thead>
                        <tbody id="investing-body">
                            @foreach ($investingLines as $i => $row)
                                <tr class="dyn-row">
                                    <td>
                                        <input class="cfm-name-input" type="text"
                                               name="investing[{{ $i }}][name]"
                                               value="{{ old("investing.{$i}.name", $row['entry']->line_name) }}"
                                               placeholder="e.g. Purchase of equipment">
                                    </td>
                                    <td>
                                        <input class="cfm-input" type="number" step="0.01"
                                               name="investing[{{ $i }}][cur]"
                                               value="{{ old("investing.{$i}.cur", $row['entry']->current_amount) }}">
                                    </td>
                                    @if ($hasPrior)
                                        <td>
                                            <input class="cfm-input" type="number" step="0.01"
                                                   name="investing[{{ $i }}][pri]"
                                                   value="{{ old("investing.{$i}.pri", $row['priorAmount']) }}">
                                        </td>
                                    @endif
                                    <td style="text-align:center;">
                                        <button type="button" class="cfm-remove"
                                                onclick="this.closest('tr').remove()">&times;</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if (! empty($registerInvestingSuggestions))
                        <div style="padding:0.35rem 1.25rem;font-size:0.7rem;color:#5a7186;background:#f7fbfd;border-top:1px dashed #d3e2f5;">
                            <strong style="color:#005bf0;">Register-derived lines</strong> — click to add to the statement
                        </div>
                        <table class="cfm-table" style="background:#f7fbfd;">
                            <tbody>
                                @foreach ($registerInvestingSuggestions as $s)
                                    <tr class="cfm-suggestion" style="cursor:pointer;opacity:0.75;"
                                        onclick="addPrefilledLine('investing', 'investing-body', '{{ addslashes($s['name']) }}', {{ $s['cur'] }}, {{ $s['pri'] }}); this.remove();"
                                        title="Click to add this line">
                                        <td class="cfm-label" style="width:{{ $labelCol }};color:#005bf0;">
                                            <span style="font-size:0.7rem;background:#eaf8fb;color:#005bf0;padding:0.1rem 0.35rem;margin-right:0.35rem;font-weight:700;">AUTO</span>
                                            {{ $s['name'] }}
                                        </td>
                                        <td style="width:{{ $amtCol }};text-align:right;font-family:inherit;font-size:0.82rem;color:#5a7186;">
                                            {{ number_format($s['cur'], 2) }}
                                        </td>
                                        @if ($hasPrior)
                                            <td style="width:{{ $amtCol }};text-align:right;font-family:inherit;font-size:0.82rem;color:#5a7186;">
                                                {{ number_format($s['pri'], 2) }}
                                            </td>
                                        @endif
                                        <td style="width:4%;text-align:center;font-size:1rem;color:#005bf0;">+</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                    <button type="button" class="cfm-add-btn"
                            onclick="addLine('investing', 'investing-body', 'e.g. Purchase of equipment')">
                        + Add investing line
                    </button>

                    {{-- ══ FINANCING ══ --}}
                    <div class="cfm-section-head">Cash flows from financing activities</div>
                    <table class="cfm-table">
                        <thead>
                            <tr>
                                <th style="width:{{ $labelCol }};">Line item</th>
                                <th class="num" style="width:{{ $amtCol }};">
                                    {{ $hasPrior ? $curYear . ' (R)' : 'Amount (R)' }}
                                </th>
                                @if ($hasPrior)
                                    <th class="num" style="width:{{ $amtCol }};">{{ $priYear }} (R)</th>
                                @endif
                                <th style="width:4%;"></th>
                            </tr>
                        </thead>
                        <tbody id="financing-body">
                            @foreach ($financingLines as $i => $row)
                                <tr class="dyn-row">
                                    <td>
                                        <input class="cfm-name-input" type="text"
                                               name="financing[{{ $i }}][name]"
                                               value="{{ old("financing.{$i}.name", $row['entry']->line_name) }}"
                                               placeholder="e.g. Proceeds from bank loan">
                                    </td>
                                    <td>
                                        <input class="cfm-input" type="number" step="0.01"
                                               name="financing[{{ $i }}][cur]"
                                               value="{{ old("financing.{$i}.cur", $row['entry']->current_amount) }}">
                                    </td>
                                    @if ($hasPrior)
                                        <td>
                                            <input class="cfm-input" type="number" step="0.01"
                                                   name="financing[{{ $i }}][pri]"
                                                   value="{{ old("financing.{$i}.pri", $row['priorAmount']) }}">
                                        </td>
                                    @endif
                                    <td style="text-align:center;">
                                        <button type="button" class="cfm-remove"
                                                onclick="this.closest('tr').remove()">&times;</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if (! empty($registerFinancingSuggestions))
                        <div style="padding:0.35rem 1.25rem;font-size:0.7rem;color:#5a7186;background:#f7fbfd;border-top:1px dashed #d3e2f5;">
                            <strong style="color:#005bf0;">Register-derived lines</strong> — click to add to the statement
                        </div>
                        <table class="cfm-table" style="background:#f7fbfd;">
                            <tbody>
                                @foreach ($registerFinancingSuggestions as $s)
                                    <tr class="cfm-suggestion" style="cursor:pointer;opacity:0.75;"
                                        onclick="addPrefilledLine('financing', 'financing-body', '{{ addslashes($s['name']) }}', {{ $s['cur'] }}, {{ $s['pri'] }}); this.remove();"
                                        title="Click to add this line">
                                        <td class="cfm-label" style="width:{{ $labelCol }};color:#005bf0;">
                                            <span style="font-size:0.7rem;background:#eaf8fb;color:#005bf0;padding:0.1rem 0.35rem;margin-right:0.35rem;font-weight:700;">AUTO</span>
                                            {{ $s['name'] }}
                                        </td>
                                        <td style="width:{{ $amtCol }};text-align:right;font-family:inherit;font-size:0.82rem;color:#5a7186;">
                                            {{ number_format($s['cur'], 2) }}
                                        </td>
                                        @if ($hasPrior)
                                            <td style="width:{{ $amtCol }};text-align:right;font-family:inherit;font-size:0.82rem;color:#5a7186;">
                                                {{ number_format($s['pri'], 2) }}
                                            </td>
                                        @endif
                                        <td style="width:4%;text-align:center;font-size:1rem;color:#005bf0;">+</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                    <button type="button" class="cfm-add-btn"
                            onclick="addLine('financing', 'financing-body', 'e.g. Proceeds from bank loan')">
                        + Add financing line
                    </button>

                    <div style="padding:1rem 1.25rem 0;position:sticky;bottom:0;background:#fff;padding-top:0.6rem;">
                        <button type="submit"
                            style="background:#005bf0;color:#fff;border:none;border-radius:0;padding:0.5rem 1.4rem;font-size:0.82rem;font-weight:700;cursor:pointer;font-family:inherit;">
                            Save Statement
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<script>
const dynCounters  = {
    investing: {{ $investingLines->count() }},
    financing: {{ $financingLines->count() }},
};
const hasPrior = {{ $hasPrior ? 'true' : 'false' }};

function addPrefilledLine(section, tbodyId, name, curVal, priVal) {
    const idx   = dynCounters[section]++;
    const tbody = document.getElementById(tbodyId);
    const tr    = document.createElement('tr');
    tr.className = 'dyn-row';

    const priCell = hasPrior
        ? `<td><input class="cfm-input" type="number" step="0.01" name="${section}[${idx}][pri]" value="${priVal}"></td>`
        : '';

    tr.innerHTML = `
        <td><input class="cfm-name-input" type="text" name="${section}[${idx}][name]" value="${name}"></td>
        <td><input class="cfm-input" type="number" step="0.01" name="${section}[${idx}][cur]" value="${curVal}"></td>
        ${priCell}
        <td style="text-align:center;">
            <button type="button" class="cfm-remove" onclick="this.closest('tr').remove()">&times;</button>
        </td>
    `;
    tbody.appendChild(tr);
}

function addLine(section, tbodyId, placeholder) {
    const idx   = dynCounters[section]++;
    const tbody = document.getElementById(tbodyId);
    const tr    = document.createElement('tr');
    tr.className = 'dyn-row';

    const priCell = hasPrior
        ? `<td><input class="cfm-input" type="number" step="0.01" name="${section}[${idx}][pri]"></td>`
        : '';

    tr.innerHTML = `
        <td><input class="cfm-name-input" type="text" name="${section}[${idx}][name]" placeholder="${placeholder}"></td>
        <td><input class="cfm-input" type="number" step="0.01" name="${section}[${idx}][cur]"></td>
        ${priCell}
        <td style="text-align:center;">
            <button type="button" class="cfm-remove" onclick="this.closest('tr').remove()">&times;</button>
        </td>
    `;
    tbody.appendChild(tr);
    tr.querySelector('input[type=text]').focus();
}
</script>
@endsection
