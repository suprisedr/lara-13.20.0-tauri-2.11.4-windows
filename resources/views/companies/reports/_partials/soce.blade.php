{{--
    Statement of Changes in Equity (IFRS for SMEs)

    Required data (from CompanyController::changesInEquityData):
      $company, $startDate, $endDate, $rounding
      $equityMovement, $ociAccounts, $revalSurplus,
      $equityDetails (current year), $equityDetailsPrior (prior year)
--}}
@php
    $roundingLabel = match ($rounding) {
        1000 => "R'000",
        1000000 => "R'm",
        default => 'R',
    };
    $roundingDecimals = match ($rounding) {
        1000 => 0,
        1000000 => 2,
        default => 2,
    };
    $curYear   = \Carbon\Carbon::parse($endDate)->format('Y');
    $priorYear = \Carbon\Carbon::parse($endDate)->subYear()->format('Y');

    $eq = $equityMovement;
    $rv = $revalSurplus ?? ['open_prior' => 0, 'close_prior' => 0, 'movement_prior' => 0, 'open_cur' => 0, 'close_cur' => 0, 'movement_cur' => 0];
    $hasReval = ($rv['open_prior'] != 0 || $rv['close_cur'] != 0 || $rv['movement_prior'] != 0 || $rv['movement_cur'] != 0);

    $eqDetailsCur   = $equityDetails ?? [];
    $eqDetailsPri   = $equityDetailsPrior ?? [];

    $soceOciC = (float) collect($ociAccounts ?? collect())->sum('oci_net');
    $soceOciP = (float) collect($ociAccounts ?? collect())->sum('oci_net_prior');

    $fmt = fn($v) => $v != 0 ? number_format($v / $rounding, $roundingDecimals, '.', ' ') : '—';
    $amtClass = fn($v) => $v == 0 ? 'afs-amount afs-dim' : ($v < 0 ? 'afs-amount afs-abnormal' : 'afs-amount');
@endphp

<table class="afs-table afs-matrix">
    <thead>
        <tr>
            <th class="afs-col-label">Figures in {{ $roundingLabel }}</th>
            <th class="afs-col-amount">Share capital</th>
            @if ($hasReval)
                <th class="afs-col-amount">Revaluation surplus</th>
            @endif
            <th class="afs-col-amount">Retained income</th>
            <th class="afs-col-amount">Total equity</th>
        </tr>
    </thead>
    <tbody>
        {{-- ════ PRIOR YEAR ════ --}}
        @php
            $priOpenReval = (float) $rv['open_prior'];
            $priOpenTotal = $eq['share_open_prior'] + $eq['retained_open_prior'] + $priOpenReval;
        @endphp
        <tr class="afs-item-row">
            <td class="afs-name">Balance at beginning of {{ $priorYear }}</td>
            <td class="{{ $amtClass($eq['share_open_prior']) }}">{{ $fmt($eq['share_open_prior']) }}</td>
            @if ($hasReval)
                <td class="{{ $amtClass($priOpenReval) }}">{{ $fmt($priOpenReval) }}</td>
            @endif
            <td class="{{ $amtClass($eq['retained_open_prior']) }}">{{ $fmt($eq['retained_open_prior']) }}</td>
            <td class="{{ $amtClass($priOpenTotal) }}">{{ $fmt($priOpenTotal) }}</td>
        </tr>
        <tr class="afs-item-row">
            <td class="afs-name">{{ $eq['profit_prior'] >= 0 ? 'Profit' : 'Loss' }} for the year</td>
            <td class="afs-amount afs-dim">—</td>
            @if ($hasReval)<td class="afs-amount afs-dim">—</td>@endif
            <td class="{{ $amtClass($eq['profit_prior']) }}">{{ $fmt($eq['profit_prior']) }}</td>
            <td class="{{ $amtClass($eq['profit_prior']) }}">{{ $fmt($eq['profit_prior']) }}</td>
        </tr>
        @if ($soceOciP != 0)
            <tr class="afs-item-row">
                <td class="afs-name">Other comprehensive income</td>
                <td class="afs-amount afs-dim">—</td>
                @if ($hasReval)<td class="afs-amount afs-dim">—</td>@endif
                <td class="{{ $amtClass($soceOciP) }}">{{ $fmt($soceOciP) }}</td>
                <td class="{{ $amtClass($soceOciP) }}">{{ $fmt($soceOciP) }}</td>
            </tr>
        @endif
        @if ($rv['movement_prior'] != 0)
            <tr class="afs-item-row">
                <td class="afs-name">Revaluation surplus movement</td>
                <td class="afs-amount afs-dim">—</td>
                @if ($hasReval)
                    <td class="{{ $amtClass($rv['movement_prior']) }}">{{ $fmt($rv['movement_prior']) }}</td>
                @endif
                <td class="afs-amount afs-dim">—</td>
                <td class="{{ $amtClass($rv['movement_prior']) }}">{{ $fmt($rv['movement_prior']) }}</td>
            </tr>
        @endif
        @foreach ($eqDetailsPri as $detail)
            <tr class="afs-item-row">
                <td class="afs-name">{{ $detail['name'] }}</td>
                <td class="afs-amount afs-dim">—</td>
                @if ($hasReval)<td class="afs-amount afs-dim">—</td>@endif
                <td class="{{ $amtClass($detail['amount']) }}">{{ $fmt($detail['amount']) }}</td>
                <td class="{{ $amtClass($detail['amount']) }}">{{ $fmt($detail['amount']) }}</td>
            </tr>
        @endforeach
        @php
            $shareIssuePrior = round($eq['share_open'] - $eq['share_open_prior'], 2);
        @endphp
        @if ($shareIssuePrior != 0)
            <tr class="afs-item-row">
                <td class="afs-name">Issue of shares</td>
                <td class="{{ $amtClass($shareIssuePrior) }}">{{ $fmt($shareIssuePrior) }}</td>
                @if ($hasReval)<td class="afs-amount afs-dim">—</td>@endif
                <td class="afs-amount afs-dim">—</td>
                <td class="{{ $amtClass($shareIssuePrior) }}">{{ $fmt($shareIssuePrior) }}</td>
            </tr>
        @endif

        {{-- ════ CURRENT YEAR OPENING ════ --}}
        @php
            $curOpenReval = (float) $rv['open_cur'];
            $curOpenTotal = $eq['share_open'] + $eq['retained_open'] + $curOpenReval;
        @endphp
        <tr class="afs-subtotal">
            <td class="afs-name">Balance at beginning of {{ $curYear }}</td>
            <td class="{{ $amtClass($eq['share_open']) }}">{{ $fmt($eq['share_open']) }}</td>
            @if ($hasReval)
                <td class="{{ $amtClass($curOpenReval) }}">{{ $fmt($curOpenReval) }}</td>
            @endif
            <td class="{{ $amtClass($eq['retained_open']) }}">{{ $fmt($eq['retained_open']) }}</td>
            <td class="{{ $amtClass($curOpenTotal) }}">{{ $fmt($curOpenTotal) }}</td>
        </tr>

        @if ($eq['share_issue'] != 0)
            <tr class="afs-item-row">
                <td class="afs-name">Issue of shares</td>
                <td class="{{ $amtClass($eq['share_issue']) }}">{{ $fmt($eq['share_issue']) }}</td>
                @if ($hasReval)<td class="afs-amount afs-dim">—</td>@endif
                <td class="afs-amount afs-dim">—</td>
                <td class="{{ $amtClass($eq['share_issue']) }}">{{ $fmt($eq['share_issue']) }}</td>
            </tr>
        @endif
        <tr class="afs-item-row">
            <td class="afs-name">{{ $eq['profit_current'] >= 0 ? 'Profit' : 'Loss' }} for the year</td>
            <td class="afs-amount afs-dim">—</td>
            @if ($hasReval)<td class="afs-amount afs-dim">—</td>@endif
            <td class="{{ $amtClass($eq['profit_current']) }}">{{ $fmt($eq['profit_current']) }}</td>
            <td class="{{ $amtClass($eq['profit_current']) }}">{{ $fmt($eq['profit_current']) }}</td>
        </tr>
        @if ($soceOciC != 0)
            <tr class="afs-item-row">
                <td class="afs-name">Other comprehensive income</td>
                <td class="afs-amount afs-dim">—</td>
                @if ($hasReval)<td class="afs-amount afs-dim">—</td>@endif
                <td class="{{ $amtClass($soceOciC) }}">{{ $fmt($soceOciC) }}</td>
                <td class="{{ $amtClass($soceOciC) }}">{{ $fmt($soceOciC) }}</td>
            </tr>
        @endif
        @if ($rv['movement_cur'] != 0)
            <tr class="afs-item-row">
                <td class="afs-name">Revaluation surplus movement</td>
                <td class="afs-amount afs-dim">—</td>
                @if ($hasReval)
                    <td class="{{ $amtClass($rv['movement_cur']) }}">{{ $fmt($rv['movement_cur']) }}</td>
                @endif
                <td class="afs-amount afs-dim">—</td>
                <td class="{{ $amtClass($rv['movement_cur']) }}">{{ $fmt($rv['movement_cur']) }}</td>
            </tr>
        @endif
        @foreach ($eqDetailsCur as $detail)
            <tr class="afs-item-row">
                <td class="afs-name">{{ $detail['name'] }}</td>
                <td class="afs-amount afs-dim">—</td>
                @if ($hasReval)<td class="afs-amount afs-dim">—</td>@endif
                <td class="{{ $amtClass($detail['amount']) }}">{{ $fmt($detail['amount']) }}</td>
                <td class="{{ $amtClass($detail['amount']) }}">{{ $fmt($detail['amount']) }}</td>
            </tr>
        @endforeach

        {{-- ════ CLOSING ════ --}}
        @php
            $closeReval = (float) $rv['close_cur'];
            $closeTotal = $eq['share_close'] + $eq['retained_close'] + $closeReval;
        @endphp
        <tr class="afs-grand-total">
            <td class="afs-name">Balance at end of {{ $curYear }}</td>
            <td class="{{ $amtClass($eq['share_close']) }}">{{ $fmt($eq['share_close']) }}</td>
            @if ($hasReval)
                <td class="{{ $amtClass($closeReval) }}">{{ $fmt($closeReval) }}</td>
            @endif
            <td class="{{ $amtClass($eq['retained_close']) }}">{{ $fmt($eq['retained_close']) }}</td>
            <td class="{{ $amtClass($closeTotal) }}">{{ $fmt($closeTotal) }}</td>
        </tr>
    </tbody>
</table>
