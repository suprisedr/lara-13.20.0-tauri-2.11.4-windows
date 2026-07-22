{{--
    Statement of Financial Position (IFRS for SMEs presentation)

    Required data (from CompanyController::buildBalanceSheet + mergeBalanceSheetComparatives):
      $company, $asOfDate, $rounding, $compare, $priorAsOfDate
      $currentAssets, $nonCurrentAssets, $currentLiabilities, $nonCurrentLiabilities, $equityAccounts
      $totalCurrentAssets, $totalNonCurrentAssets, $totalAssets
      $totalCurrentLiabilities, $totalNonCurrentLiabilities, $totalLiabilities, $totalEquity
      (prior_* variants when $compare)
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
    $cols = $compare ? 4 : 3;
    $currentYearLabel = \Carbon\Carbon::parse($asOfDate)->format('d M Y');
    $priorYearLabel = $compare && $priorAsOfDate ? \Carbon\Carbon::parse($priorAsOfDate)->format('d M Y') : null;
    $isBalanced = abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01;

    // Render a section's rows. Children (items) of grouped accounts are flattened
// to individual line items per AFS presentation convention.
// $suppressedIds: account IDs replaced by register-based lines (PPE / intangible).
//   - For a parent group: suppressed children are skipped; if every child is suppressed
//     the whole group is omitted (replaced by the register carrying-value rows).
//     Non-suppressed siblings in a mixed group are preserved.
//   - For a standalone account: omitted if its own ID is in $suppressedIds.
$afsRows = function ($accounts, $compare, $rounding, $roundingDecimals, $suppressedIds = []) {
    $rows = [];
    foreach ($accounts as $account) {
        if ($account->items->isNotEmpty()) {
            $visible = $account->items->filter(fn($item) => !in_array($item->id, $suppressedIds));
            if ($visible->isEmpty()) {
                continue;
            }

            $rolled = $visible->where('show_separately', false);
            $separate = $visible->where('show_separately', true);

            $rows[] = [
                'name'       => $account->account_name,
                'bal'        => (float) $account->balance + $rolled->sum('balance'),
                'prior'      => (float) ($account->prior_balance ?? 0) + $rolled->sum(fn($i) => (float) ($i->prior_balance ?? 0)),
                'account_id' => $account->id,
            ];

            foreach ($separate as $item) {
                $rows[] = [
                    'name'              => $item->account_name,
                    'bal'               => (float) $item->balance,
                    'prior'             => (float) ($item->prior_balance ?? 0),
                    'is_separate_child' => true,
                    'account_id'        => $item->id,
                ];
            }
        } else {
            if (in_array($account->id, $suppressedIds)) {
                continue;
            }
            $rows[] = [
                'name'       => $account->account_name,
                'bal'        => (float) $account->groupBalance,
                'prior'      => (float) ($account->prior_groupBalance ?? 0),
                'account_id' => $account->id,
            ];
        }
    }
    return array_values(array_filter($rows, function ($r) use ($compare) {
        if (abs($r['bal']) >= 0.01) return true;
        return $compare && abs($r['prior'] ?? 0) >= 0.01;
    }));
};

$fmt = function ($v) use ($rounding, $roundingDecimals) {
    return $v != 0 ? number_format(abs($v) / $rounding, $roundingDecimals) : '—';
};

$amtClass = fn($v) => $v == 0 ? 'afs-amount afs-dim' : ($v < 0 ? 'afs-amount afs-abnormal' : 'afs-amount');

$isPdf = $isPdf ?? false;
// Wrap abnormal amounts in a link to the account's transaction history (web only).
$traceLink = function ($v, $row) use ($company, $isPdf, $fmt, $amtClass) {
    $text = $fmt($v);
    $class = $amtClass($v);
    if (!$isPdf && $v < 0 && !empty($row['account_id'])) {
        $url = route('companies.transactions', [$company, 'account_id' => $row['account_id']]);
        return "<td class=\"{$class}\"><a href=\"{$url}\" style=\"color:inherit;text-decoration:underline dotted;text-underline-offset:2px;\" title=\"View transactions for {$row['name']}\">{$text}</a></td>";
    }
    return "<td class=\"{$class}\">{$text}</td>";
};

// Resolve which AFS note a balance-sheet line refers to, by matching keywords
// in the account name to a note slug. Returns the note slug or null.
// PPE keywords are intentionally absent: PPE accounts are presented as a single
// consolidated line with an explicit note_slug set by the caller.
$noteForName = function ($name) {
    $n = strtolower($name);
    $map = [
        'intangible-assets'           => ['intangible', 'goodwill', 'software', 'patent', 'trademark'],
        'inventories'                 => ['inventor', 'stock'],
        'trade-and-other-receivables' => ['receivable', 'debtor'],
        'cash-and-cash-equivalents'   => ['cash', 'bank'],
        'share-capital'               => ['share capital', 'ordinary share', 'stated capital'],
        'trade-and-other-payables'    => ['payable', 'creditor'],
        'borrowings'                  => ['borrow', 'loan', 'finance lease', 'mortgage'],
    ];
    foreach ($map as $slug => $keywords) {
        foreach ($keywords as $k) {
            if (str_contains($n, $k)) {
                return $slug;
            }
        }
    }
    return null;
};

// Note reference cell — links a statement line to its note in the AFS notes.
$noteRefs = $noteRefs ?? [];
$noteCell = function ($slug) use ($noteRefs, $company) {
    if ($slug === null || !isset($noteRefs[$slug])) {
        return '';
    }
    $ref = $noteRefs[$slug];
    $url = route('companies.notes-to-afs.show', [$company, $slug]);
    return '<a href="' .
        $url .
        '" style="color:#5e17eb;text-decoration:none;font-weight:600;" title="See note ' .
        $ref['n'] .
        '">' .
        $ref['n'] .
        '</a>';
    };
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

        {{-- ════ ASSETS ════ --}}
        <tr class="afs-section-main">
            <td colspan="{{ $cols }}">Assets</td>
        </tr>

        {{-- Non-Current Assets (presented first per IFRS for SMEs) --}}
        <tr class="afs-section-sub">
            <td colspan="{{ $cols }}">Non-Current Assets</td>
        </tr>
        @php
            // PPE class lines: one net carrying-value row per class instead of showing
            // cost and accumulated-depreciation accounts separately.
            $ppeLinkedAccountIds = $ppeLinkedAccountIds ?? [];
            $ppeCarrying         = $ppeCarrying ?? [];
            $ppeCarryingPrior    = $ppeCarryingPrior ?? [];

            $ppeTotalCarrying = collect($ppeCarrying)->sum('carrying');
            $ppeTotalPrior    = collect($ppeCarryingPrior)->sum('carrying');
            $ppeRows = !empty($ppeCarrying) ? [[
                'name'      => 'Property, Plant and Equipment',
                'bal'       => $ppeTotalCarrying,
                'prior'     => $ppeTotalPrior,
                'note_slug' => 'property-plant-equipment',
            ]] : [];

            // Intangible assets from the intangible register (IAS 38).
            $intangibleLinkedAccountIds = $intangibleLinkedAccountIds ?? [];
            $intangibleCarrying      = $intangibleCarrying ?? [];
            $intangibleCarryingPrior = $intangibleCarryingPrior ?? [];

            $intTotalCarrying = collect($intangibleCarrying)->sum('carrying');
            $intTotalPrior    = collect($intangibleCarryingPrior)->sum('carrying');
            $intRows = !empty($intangibleCarrying) ? [[
                'name'      => 'Intangible Assets',
                'bal'       => $intTotalCarrying,
                'prior'     => $intTotalPrior,
                'note_slug' => 'intangible-assets',
            ]] : [];

            // Investment properties from the register (IAS 40).
            $ipLinkedAccountIds = $ipLinkedAccountIds ?? [];
            $ipCarrying      = $ipCarrying ?? [];
            $ipCarryingPrior = $ipCarryingPrior ?? [];
            $ipTotalCarrying = collect($ipCarrying)->sum('carrying');
            $ipTotalPrior    = collect($ipCarryingPrior)->sum('carrying');
            $ipRows = !empty($ipCarrying) ? [[
                'name'      => 'Investment Properties',
                'bal'       => $ipTotalCarrying,
                'prior'     => $ipTotalPrior,
                'note_slug' => 'investment-properties',
            ]] : [];

            // Biological assets from the register (IAS 41).
            $baLinkedAccountIds = $baLinkedAccountIds ?? [];
            $baCarrying      = $baCarrying ?? [];
            $baCarryingPrior = $baCarryingPrior ?? [];
            $baTotalCarrying = collect($baCarrying)->sum('carrying');
            $baTotalPrior    = collect($baCarryingPrior)->sum('carrying');
            $baRows = !empty($baCarrying) ? [[
                'name'      => 'Biological Assets',
                'bal'       => $baTotalCarrying,
                'prior'     => $baTotalPrior,
                'note_slug' => 'biological-assets',
            ]] : [];

            // Right-of-use assets from the lease register (IFRS 16).
            $rouLinkedAccountIds = $rouLinkedAccountIds ?? [];
            $rouCarrying      = $rouCarrying ?? [];
            $rouCarryingPrior = $rouCarryingPrior ?? [];
            $rouTotalCarrying = collect($rouCarrying)->sum('carrying');
            $rouTotalPrior    = collect($rouCarryingPrior)->sum('carrying');
            $rouRows = !empty($rouCarrying) ? [[
                'name'      => 'Right-of-Use Assets',
                'bal'       => $rouTotalCarrying,
                'prior'     => $rouTotalPrior,
                'note_slug' => 'right-of-use-assets',
            ]] : [];

            // Merge all register-suppressed account IDs so they are excluded from normal rows.
            $suppressedIds = array_merge($ppeLinkedAccountIds, $intangibleLinkedAccountIds, $ipLinkedAccountIds, $baLinkedAccountIds, $rouLinkedAccountIds);
            $normalRows = $afsRows($nonCurrentAssets, $compare, $rounding, $roundingDecimals, $suppressedIds);
            $rows = array_merge($ppeRows, $intRows, $ipRows, $baRows, $rouRows, $normalRows);
        @endphp
        @forelse ($rows as $i => $row)
            <tr class="afs-item-row {{ $i === count($rows) - 1 ? 'afs-item-last' : '' }}">
                <td class="afs-name"@if($row['is_separate_child'] ?? false) style="padding-left:1.75rem;color:#555;"@endif>{{ $row['name'] }}</td>
                <td class="afs-note">{!! $noteCell($row['note_slug'] ?? $noteForName($row['name'])) !!}</td>
                {!! $traceLink($row['bal'], $row) !!}
                @if ($compare)
                    {!! $traceLink($row['prior'], $row) !!}
                @endif
            </tr>
        @empty
            <tr class="afs-item-row afs-item-last">
                <td colspan="{{ $cols }}" class="afs-empty">None</td>
            </tr>
        @endforelse
        <tr class="afs-subtotal">
            <td class="afs-name">Total Non-Current Assets</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($totalNonCurrentAssets) }}">{{ $fmt($totalNonCurrentAssets) }}</td>
            @if ($compare)
                <td class="{{ $amtClass($totalNonCurrentAssetsPrior ?? 0) }}">{{ $fmt($totalNonCurrentAssetsPrior ?? 0) }}</td>
            @endif
        </tr>

        {{-- Current Assets --}}
        <tr class="afs-section-sub">
            <td colspan="{{ $cols }}">Current Assets</td>
        </tr>
        @php
            // Inventory from the inventory register (IAS 2) — same pattern as PPE/intangibles.
            $inventoryLinkedAccountIds = $inventoryLinkedAccountIds ?? [];
            $inventoryCarrying      = $inventoryCarrying ?? [];
            $inventoryCarryingPrior = $inventoryCarryingPrior ?? [];

            $invTotalCarrying = collect($inventoryCarrying)->sum('carrying');
            $invTotalPrior    = collect($inventoryCarryingPrior)->sum('carrying');
            $invRows = !empty($inventoryCarrying) ? [[
                'name'      => 'Inventories',
                'bal'       => $invTotalCarrying,
                'prior'     => $invTotalPrior,
                'note_slug' => 'inventories',
            ]] : [];

            $currentAssetSuppressed = $inventoryLinkedAccountIds;
            $normalRows = $afsRows($currentAssets, $compare, $rounding, $roundingDecimals, $currentAssetSuppressed);
            $rows = array_merge($invRows, $normalRows);
        @endphp
        @forelse ($rows as $i => $row)
            <tr class="afs-item-row {{ $i === count($rows) - 1 ? 'afs-item-last' : '' }}">
                <td class="afs-name"@if($row['is_separate_child'] ?? false) style="padding-left:1.75rem;color:#555;"@endif>{{ $row['name'] }}</td>
                <td class="afs-note">{!! $noteCell($noteForName($row['name'])) !!}</td>
                {!! $traceLink($row['bal'], $row) !!}
                @if ($compare)
                    {!! $traceLink($row['prior'], $row) !!}
                @endif
            </tr>
        @empty
            <tr class="afs-item-row afs-item-last">
                <td colspan="{{ $cols }}" class="afs-empty">None</td>
            </tr>
        @endforelse
        <tr class="afs-subtotal">
            <td class="afs-name">Total Current Assets</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($totalCurrentAssets) }}">{{ $fmt($totalCurrentAssets) }}</td>
            @if ($compare)
                <td class="{{ $amtClass($totalCurrentAssetsPrior ?? 0) }}">{{ $fmt($totalCurrentAssetsPrior ?? 0) }}</td>
            @endif
        </tr>

        <tr class="afs-grand-total">
            <td class="afs-name">Total Assets</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($totalAssets) }}">{{ $fmt($totalAssets) }}</td>
            @if ($compare)
                <td class="{{ $amtClass($totalAssetsPrior ?? 0) }}">{{ $fmt($totalAssetsPrior ?? 0) }}</td>
            @endif
        </tr>

        {{-- ════ EQUITY AND LIABILITIES ════ --}}
        <tr class="afs-section-main">
            <td colspan="{{ $cols }}">Equity and Liabilities</td>
        </tr>

        {{-- Equity --}}
        <tr class="afs-section-sub">
            <td colspan="{{ $cols }}">Equity</td>
        </tr>
        @php $rows = $afsRows($equityAccounts, $compare, $rounding, $roundingDecimals); @endphp
        @forelse ($rows as $i => $row)
            <tr class="afs-item-row {{ $i === count($rows) - 1 ? 'afs-item-last' : '' }}">
                <td class="afs-name"@if($row['is_separate_child'] ?? false) style="padding-left:1.75rem;color:#555;"@endif>{{ $row['name'] }}</td>
                <td class="afs-note">{!! $noteCell($noteForName($row['name'])) !!}</td>
                {!! $traceLink($row['bal'], $row) !!}
                @if ($compare)
                    {!! $traceLink($row['prior'], $row) !!}
                @endif
            </tr>
        @empty
            <tr class="afs-item-row afs-item-last">
                <td colspan="{{ $cols }}" class="afs-empty">None</td>
            </tr>
        @endforelse
        <tr class="afs-subtotal">
            <td class="afs-name">Total Equity</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($totalEquity) }}">{{ $fmt($totalEquity) }}</td>
            @if ($compare)
                <td class="{{ $amtClass($totalEquityPrior ?? 0) }}">{{ $fmt($totalEquityPrior ?? 0) }}</td>
            @endif
        </tr>

        {{-- Non-Current Liabilities --}}
        <tr class="afs-section-sub">
            <td colspan="{{ $cols }}">Non-Current Liabilities</td>
        </tr>
        @php
            $leaseLiabLinkedAccountIds = $leaseLiabLinkedAccountIds ?? [];
            $leaseLiabCarrying      = $leaseLiabCarrying ?? [];
            $leaseLiabCarryingPrior = $leaseLiabCarryingPrior ?? [];
            $llTotalCarrying = collect($leaseLiabCarrying)->sum('carrying');
            $llTotalPrior    = collect($leaseLiabCarryingPrior)->sum('carrying');
            $llRows = !empty($leaseLiabCarrying) ? [[
                'name'      => 'Lease Liabilities',
                'bal'       => $llTotalCarrying,
                'prior'     => $llTotalPrior,
                'note_slug' => 'lease-liabilities',
            ]] : [];

            $normalRows = $afsRows($nonCurrentLiabilities, $compare, $rounding, $roundingDecimals, $leaseLiabLinkedAccountIds);
            $rows = array_merge($llRows, $normalRows);
        @endphp
        @forelse ($rows as $i => $row)
            <tr class="afs-item-row {{ $i === count($rows) - 1 ? 'afs-item-last' : '' }}">
                <td class="afs-name"@if($row['is_separate_child'] ?? false) style="padding-left:1.75rem;color:#555;"@endif>{{ $row['name'] }}</td>
                <td class="afs-note">{!! $noteCell($noteForName($row['name'])) !!}</td>
                {!! $traceLink($row['bal'], $row) !!}
                @if ($compare)
                    {!! $traceLink($row['prior'], $row) !!}
                @endif
            </tr>
        @empty
            <tr class="afs-item-row afs-item-last">
                <td colspan="{{ $cols }}" class="afs-empty">None</td>
            </tr>
        @endforelse
        <tr class="afs-subtotal">
            <td class="afs-name">Total Non-Current Liabilities</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($totalNonCurrentLiabilities ?? 0) }}">{{ $fmt($totalNonCurrentLiabilities ?? 0) }}</td>
            @if ($compare)
                <td class="{{ $amtClass($totalNonCurrentLiabilitiesPrior ?? 0) }}">{{ $fmt($totalNonCurrentLiabilitiesPrior ?? 0) }}</td>
            @endif
        </tr>

        {{-- Current Liabilities --}}
        <tr class="afs-section-sub">
            <td colspan="{{ $cols }}">Current Liabilities</td>
        </tr>
        @php $rows = $afsRows($currentLiabilities, $compare, $rounding, $roundingDecimals); @endphp
        @forelse ($rows as $i => $row)
            <tr class="afs-item-row {{ $i === count($rows) - 1 ? 'afs-item-last' : '' }}">
                <td class="afs-name"@if($row['is_separate_child'] ?? false) style="padding-left:1.75rem;color:#555;"@endif>{{ $row['name'] }}</td>
                <td class="afs-note">{!! $noteCell($noteForName($row['name'])) !!}</td>
                {!! $traceLink($row['bal'], $row) !!}
                @if ($compare)
                    {!! $traceLink($row['prior'], $row) !!}
                @endif
            </tr>
        @empty
            <tr class="afs-item-row afs-item-last">
                <td colspan="{{ $cols }}" class="afs-empty">None</td>
            </tr>
        @endforelse
        <tr class="afs-subtotal">
            <td class="afs-name">Total Current Liabilities</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($totalCurrentLiabilities ?? 0) }}">{{ $fmt($totalCurrentLiabilities ?? 0) }}</td>
            @if ($compare)
                <td class="{{ $amtClass($totalCurrentLiabilitiesPrior ?? 0) }}">{{ $fmt($totalCurrentLiabilitiesPrior ?? 0) }}</td>
            @endif
        </tr>

        <tr class="afs-named-subtotal">
            <td class="afs-name">Total Liabilities</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($totalLiabilities) }}">{{ $fmt($totalLiabilities) }}</td>
            @if ($compare)
                <td class="{{ $amtClass($totalLiabilitiesPrior ?? 0) }}">{{ $fmt($totalLiabilitiesPrior ?? 0) }}</td>
            @endif
        </tr>

    </tbody>
    <tfoot>
        <tr class="afs-grand-total">
            <td class="afs-name">Total Equity and Liabilities</td>
            <td class="afs-note"></td>
            @php $eqLiab = $totalLiabilities + $totalEquity; $eqLiabPrior = ($totalLiabilitiesPrior ?? 0) + ($totalEquityPrior ?? 0); @endphp
            <td class="{{ $amtClass($eqLiab) }}">{{ $fmt($eqLiab) }}</td>
            @if ($compare)
                <td class="{{ $amtClass($eqLiabPrior) }}">{{ $fmt($eqLiabPrior) }}</td>
            @endif
        </tr>
    </tfoot>
</table>

@if (!$isBalanced)
    <p class="afs-warning"><strong>Warning:</strong> Statement of Financial Position is out of balance.</p>
@endif
