{{--
    Statement of Cash Flows (IFRS for SMEs, direct method)

    Required data (from CompanyController::cashFlowViewData):
      $company, $endDate, $rounding, $compare
      $cf = [
        receipts, payments, cashGenerated, interest, tax, netOperating,
        investingLines[{name,cur,pri}], netInvesting,
        financingLines[{name,cur,pri}], netFinancing,
        netMovement, cashBegin, cashEnd,
        prior => [ receipts, payments, cashGenerated, interest, tax, netOperating,
                   netInvesting, netFinancing, netMovement, cashBegin, cashEnd ] | null
      ]
--}}
@php
    $roundingLabel = match ($rounding) { 1000 => "R'000", 1000000 => "R'm", default => 'R' };
    $roundingDecimals = match ($rounding) { 1000 => 0, 1000000 => 2, default => 2 };
    $cols = $compare ? 4 : 3;
    $currentYearLabel = \Carbon\Carbon::parse($endDate)->format('Y');
    $priorYearLabel = $compare ? \Carbon\Carbon::parse($endDate)->subYear()->format('Y') : null;

    $prior = $cf['prior'] ?? null;
    // Signed format: negatives in parentheses, nil as a dash.
    $sgn = function ($v) use ($rounding, $roundingDecimals) {
        if (round((float) $v, 2) == 0) return '—';
        $n = number_format(abs($v) / $rounding, $roundingDecimals);
        return $v < 0 ? "({$n})" : $n;
    };
    $pv = fn($key) => $prior[$key] ?? 0;
    $amtClass = fn($v) => round((float)$v, 2) == 0 ? 'afs-amount afs-dim' : ($v < 0 ? 'afs-amount afs-abnormal' : 'afs-amount');
@endphp

<table class="afs-table">
    <thead>
        <tr>
            <th class="afs-col-label">Figures in {{ $roundingLabel }}</th>
            <th class="afs-col-note">Note(s)</th>
            <th class="afs-col-amount">{{ $currentYearLabel }}</th>
            @if ($compare)
                <th class="afs-col-amount">{{ $priorYearLabel }}</th>
            @endif
        </tr>
    </thead>
    <tbody>

        {{-- ════ OPERATING ACTIVITIES ════ --}}
        <tr class="afs-section-main">
            <td colspan="{{ $cols }}">Cash flows from operating activities</td>
        </tr>
        <tr class="afs-item-row">
            <td class="afs-name">Cash receipts from customers</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($cf['receipts']) }}">{{ $sgn($cf['receipts']) }}</td>
            @if ($compare)<td class="{{ $amtClass($pv('receipts')) }}">{{ $sgn($pv('receipts')) }}</td>@endif
        </tr>
        <tr class="afs-item-row {{ $cf['interest'] == 0 && $cf['tax'] == 0 ? 'afs-item-last' : '' }}">
            <td class="afs-name">Cash paid to suppliers and employees</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($cf['payments']) }}">{{ $sgn($cf['payments']) }}</td>
            @if ($compare)<td class="{{ $amtClass($pv('payments')) }}">{{ $sgn($pv('payments')) }}</td>@endif
        </tr>
        <tr class="afs-subtotal">
            <td class="afs-name">Cash generated from operations</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($cf['cashGenerated']) }}">{{ $sgn($cf['cashGenerated']) }}</td>
            @if ($compare)<td class="{{ $amtClass($pv('cashGenerated')) }}">{{ $sgn($pv('cashGenerated')) }}</td>@endif
        </tr>
        @if ($cf['interest'] != 0 || ($prior && $pv('interest') != 0))
            <tr class="afs-item-row">
                <td class="afs-name">Finance costs paid</td>
                <td class="afs-note"></td>
                <td class="{{ $amtClass($cf['interest']) }}">{{ $sgn($cf['interest']) }}</td>
                @if ($compare)<td class="{{ $amtClass($pv('interest')) }}">{{ $sgn($pv('interest')) }}</td>@endif
            </tr>
        @endif
        @if ($cf['tax'] != 0 || ($prior && $pv('tax') != 0))
            <tr class="afs-item-row">
                <td class="afs-name">Tax paid</td>
                <td class="afs-note"></td>
                <td class="{{ $amtClass($cf['tax']) }}">{{ $sgn($cf['tax']) }}</td>
                @if ($compare)<td class="{{ $amtClass($pv('tax')) }}">{{ $sgn($pv('tax')) }}</td>@endif
            </tr>
        @endif
        <tr class="afs-named-subtotal">
            <td class="afs-name">Net cash {{ $cf['netOperating'] >= 0 ? 'from' : 'used in' }} operating activities</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($cf['netOperating']) }}">{{ $sgn($cf['netOperating']) }}</td>
            @if ($compare)<td class="{{ $amtClass($pv('netOperating')) }}">{{ $sgn($pv('netOperating')) }}</td>@endif
        </tr>

        {{-- ════ INVESTING ACTIVITIES ════ --}}
        <tr class="afs-section-main">
            <td colspan="{{ $cols }}">Cash flows from investing activities</td>
        </tr>
        @php $invLines = collect($cf['investingLines'])->filter(fn($l) => round((float)$l['cur'], 2) != 0 || round((float)$l['pri'], 2) != 0); @endphp
        @forelse ($invLines as $line)
            <tr class="afs-item-row">
                <td class="afs-name">
                    {{ $line['name'] }}
                    @if (($line['source'] ?? '') === 'register')
                        <span style="font-size:0.55rem;background:#ede9fe;color:#5e17eb;padding:0.05rem 0.3rem;margin-left:0.3rem;font-weight:700;vertical-align:middle;letter-spacing:0.04em;">AUTO</span>
                    @endif
                </td>
                <td class="afs-note"></td>
                <td class="{{ $amtClass($line['cur']) }}">{{ $sgn($line['cur']) }}</td>
                @if ($compare)<td class="{{ $amtClass($line['pri']) }}">{{ $sgn($line['pri']) }}</td>@endif
            </tr>
        @empty
            <tr class="afs-item-row afs-item-last">
                <td colspan="{{ $cols }}" class="afs-empty">No investing activities for this period</td>
            </tr>
        @endforelse
        <tr class="afs-named-subtotal">
            <td class="afs-name">Net cash {{ $cf['netInvesting'] >= 0 ? 'from' : 'used in' }} investing activities</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($cf['netInvesting']) }}">{{ $sgn($cf['netInvesting']) }}</td>
            @if ($compare)<td class="{{ $amtClass($pv('netInvesting')) }}">{{ $sgn($pv('netInvesting')) }}</td>@endif
        </tr>

        {{-- ════ FINANCING ACTIVITIES ════ --}}
        <tr class="afs-section-main">
            <td colspan="{{ $cols }}">Cash flows from financing activities</td>
        </tr>
        @php $finLines = collect($cf['financingLines'])->filter(fn($l) => round((float)$l['cur'], 2) != 0 || round((float)$l['pri'], 2) != 0); @endphp
        @forelse ($finLines as $line)
            <tr class="afs-item-row">
                <td class="afs-name">{{ $line['name'] }}</td>
                <td class="afs-note"></td>
                <td class="{{ $amtClass($line['cur']) }}">{{ $sgn($line['cur']) }}</td>
                @if ($compare)<td class="{{ $amtClass($line['pri']) }}">{{ $sgn($line['pri']) }}</td>@endif
            </tr>
        @empty
            <tr class="afs-item-row afs-item-last">
                <td colspan="{{ $cols }}" class="afs-empty">No financing activities for this period</td>
            </tr>
        @endforelse
        <tr class="afs-named-subtotal">
            <td class="afs-name">Net cash {{ $cf['netFinancing'] >= 0 ? 'from' : 'used in' }} financing activities</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($cf['netFinancing']) }}">{{ $sgn($cf['netFinancing']) }}</td>
            @if ($compare)<td class="{{ $amtClass($pv('netFinancing')) }}">{{ $sgn($pv('netFinancing')) }}</td>@endif
        </tr>

        {{-- ════ RECONCILIATION ════ --}}
        <tr class="afs-subtotal">
            <td class="afs-name">Net {{ $cf['netMovement'] >= 0 ? 'increase' : 'decrease' }} in cash and cash equivalents</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($cf['netMovement']) }}">{{ $sgn($cf['netMovement']) }}</td>
            @if ($compare)<td class="{{ $amtClass($pv('netMovement')) }}">{{ $sgn($pv('netMovement')) }}</td>@endif
        </tr>
        <tr class="afs-item-row">
            <td class="afs-name">Cash and cash equivalents at the beginning of the year</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($cf['cashBegin']) }}">{{ $sgn($cf['cashBegin']) }}</td>
            @if ($compare)<td class="{{ $amtClass($pv('cashBegin')) }}">{{ $sgn($pv('cashBegin')) }}</td>@endif
        </tr>
    </tbody>
    <tfoot>
        <tr class="afs-grand-total">
            <td class="afs-name">Cash and cash equivalents at the end of the year</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($cf['cashEnd']) }}">{{ $sgn($cf['cashEnd']) }}</td>
            @if ($compare)<td class="{{ $amtClass($pv('cashEnd')) }}">{{ $sgn($pv('cashEnd')) }}</td>@endif
        </tr>
    </tfoot>
</table>
