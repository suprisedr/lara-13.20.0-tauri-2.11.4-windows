{{--
    Partial: _coa-account-rows.blade.php
    Variables: $account (ChartOfAccount with ->items loaded), $typeLabels (array)
    $company is available via parent view scope.
--}}
@php
    $hasChildren = $account->items->isNotEmpty();
    $childSum = $hasChildren ? ((float) ($account->balance ?? 0) + $account->items->sum('balance')) : 0;
    $displayBal = $hasChildren ? $childSum : (float) ($account->balance ?? $account->opening_balance);
    $addChildUrl = route('companies.chart-of-accounts.items.store', [$company, $account]);
    $addChildJs =
        "openAddChildModal({$account->id}, " .
        json_encode($account->account_name) .
        ', ' .
        json_encode($account->account_code) .
        ', ' .
        json_encode($addChildUrl) .
        ')';
    $ancestorIds = $ancestorIds ?? [];
    $groupId = 'group-' . $account->id;

    // Build a human-readable tooltip for an abnormal balance.
    $abnormalTip = function (string $accountType) {
        return match ($accountType) {
            'assets'     => 'Asset account has a credit balance — this is abnormal. Click to view transactions.',
            'liabilities'=> 'Liability account has a debit balance — this is abnormal. Click to view transactions.',
            'equity'     => 'Equity account has a debit balance — this is abnormal. Click to view transactions.',
            'income'     => 'Income account has a debit balance — this is abnormal. Click to view transactions.',
            'expenses'   => 'Expense account has a credit balance — this is abnormal. Click to view transactions.',
            default      => 'Abnormal balance detected. Click to view transactions.',
        };
    };

    // Render a COA balance cell, with a link + badge when the balance is abnormal.
    $coaBalCell = function (float $bal, int $accountId, string $accountType) use ($company, $abnormalTip) {
        $color = $bal < 0 ? '#b91c1c' : ($bal == 0 ? '#c4b5fd' : '#4c1d95');
        $text  = $bal != 0 ? 'R ' . number_format(abs($bal), 2) . ($bal < 0 ? ' Cr' : '') : '—';
        if ($bal < 0) {
            $url     = route('companies.transactions', [$company, 'account_id' => $accountId]);
            $tip     = e($abnormalTip($accountType));
            $badge   = '<span style="display:inline-block;margin-left:4px;font-size:5pt;font-weight:700;font-family:Helvetica,Arial,\'DejaVu Sans\',sans-serif;letter-spacing:0.03em;background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;padding:0 3px;vertical-align:middle;">ABNORMAL</span>';
            return "<td style=\"text-align:right;font-family:\'DejaVu Sans Mono\',monospace;font-size:6.5pt;color:{$color};white-space:nowrap;\"><a href=\"{$url}\" title=\"{$tip}\" style=\"color:inherit;text-decoration:none;\">{$text}</a>{$badge}</td>";
        }
        return "<td style=\"text-align:right;font-family:'DejaVu Sans Mono',monospace;font-size:6.5pt;color:{$color};white-space:nowrap;\">{$text}</td>";
    };
@endphp

@if ($hasChildren)
    {{-- Parent (group) account row --}}
    <tr class="is-group-header coa-header coa-collapsed coa-searchable" data-coa-id="{{ $groupId }}" data-coa-parents="{{ implode(' ', $ancestorIds) }}" data-search="{{ strtolower($account->account_code . ' ' . $account->account_name . ' ' . ($account->category ?? '') . ' ' . ($account->description ?? '')) }}">
        <td style="font-family:'DejaVu Sans Mono',monospace;font-size:6.5pt;color:#7c3aed;white-space:nowrap;font-weight:700;">
            {{ $account->account_code }}
        </td>
        <td style="font-size:6pt;color:#8b7aad;">{{ $account->category ?? '' }}</td>
        <td style="font-weight:700;font-size:7pt;color:#4c1d95;">
            {!! $coaToggle !!}<a class="coa-account-link" href="{{ route('companies.transactions', [$company, 'account_id' => $account->id]) }}" title="View transactions for {{ $account->account_name }}">{{ $account->account_name }}</a>
            <span class="coa-role-badge coa-role-group">Group</span>
            <button type="button" class="coa-add-child-btn" onclick="{{ $addChildJs }}">
                + Child
            </button>
            @include('companies._coa-row-actions', ['acct' => $account])
        </td>
        <td class="hide-mobile"
            style="font-size:6pt;color:#8b7aad;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            {{ $account->description ?? '—' }}
        </td>
        {!! $coaBalCell((float) $childSum, $account->id, $account->account_type) !!}
    </tr>

    {{-- Child (item) account rows --}}
    @foreach ($account->items as $item)
        @php
            $itemBal = (float) ($item->balance ?? $item->opening_balance);
        @endphp
        <tr class="is-item-row coa-searchable" data-coa-parents="{{ implode(' ', array_merge($ancestorIds, [$groupId])) }}" data-search="{{ strtolower($item->account_code . ' ' . $item->account_name . ' ' . ($item->category ?? '') . ' ' . ($item->description ?? '')) }}">
            <td style="font-family:'DejaVu Sans Mono',monospace;font-size:6.5pt;color:#6b5b8a;white-space:nowrap;">
                {{ $item->account_code }}
            </td>
            <td style="font-size:6pt;color:#8b7aad;">{{ $item->category ?? '' }}</td>
            <td style="font-size:7pt;color:#4c1d95;">
                {!! $coaFileIcon !!}<a class="coa-account-link" href="{{ route('companies.transactions', [$company, 'account_id' => $item->id]) }}" title="View transactions for {{ $item->account_name }}">{{ $item->account_name }}</a>
                @include('companies._coa-row-actions', ['acct' => $item])
                <label class="coa-sep-toggle {{ $item->show_separately ? 'is-on' : '' }}"
                    title="Show this account on its own line in the financial statements (instead of rolling it into its parent)">
                    <input type="checkbox" {{ $item->show_separately ? 'checked' : '' }}
                        data-url="{{ route('companies.chart-of-accounts.show-separately', [$company, $item]) }}"
                        onchange="toggleShowSeparately(this)">
                    Separate
                </label>
                @if (in_array($item->account_type, ['income', 'expenses']))
                <label class="coa-sep-toggle {{ $item->is_oci ? 'is-on' : '' }}"
                    title="Classify this account as Other Comprehensive Income — it will appear in the OCI section of the income statement, not in profit or loss">
                    <input type="checkbox" {{ $item->is_oci ? 'checked' : '' }}
                        data-url="{{ route('companies.chart-of-accounts.oci', [$company, $item]) }}"
                        onchange="toggleOci(this)">
                    OCI
                </label>
                @endif
                @if ($item->account_type === 'assets')
                <label class="coa-sep-toggle {{ $item->is_ppe ? 'is-on' : '' }}"
                    title="Flag as PPE control account — balance sheet will use the asset register's carrying amount instead">
                    <input type="checkbox" {{ $item->is_ppe ? 'checked' : '' }}
                        data-url="{{ route('companies.chart-of-accounts.ppe', [$company, $item]) }}"
                        onchange="togglePpe(this)">
                    IAS 16
                </label>
                <label class="coa-sep-toggle {{ $item->is_intangible ? 'is-on' : '' }}"
                    title="Flag as intangible asset control account — balance sheet will use the intangible register's carrying amount instead">
                    <input type="checkbox" {{ $item->is_intangible ? 'checked' : '' }}
                        data-url="{{ route('companies.chart-of-accounts.intangible', [$company, $item]) }}"
                        onchange="toggleIntangible(this)">
                    IAS 38
                </label>
                <label class="coa-sep-toggle {{ $item->is_inventory ? 'is-on' : '' }}"
                    title="Flag as inventory control account — balance sheet will use the inventory register's carrying amount instead">
                    <input type="checkbox" {{ $item->is_inventory ? 'checked' : '' }}
                        data-url="{{ route('companies.chart-of-accounts.inventory', [$company, $item]) }}"
                        onchange="toggleInventory(this)">
                    IAS 2
                </label>
                <label class="coa-sep-toggle {{ $item->is_investment_property ? 'is-on' : '' }}"
                    title="Flag as investment property control account — balance sheet will use the investment property register's carrying amount instead">
                    <input type="checkbox" {{ $item->is_investment_property ? 'checked' : '' }}
                        data-url="{{ route('companies.chart-of-accounts.investment-property', [$company, $item]) }}"
                        onchange="toggleInvestmentProperty(this)">
                    IAS 40
                </label>
                <label class="coa-sep-toggle {{ $item->is_biological_asset ? 'is-on' : '' }}"
                    title="Flag as biological asset control account — balance sheet will use the biological asset register's carrying amount instead">
                    <input type="checkbox" {{ $item->is_biological_asset ? 'checked' : '' }}
                        data-url="{{ route('companies.chart-of-accounts.biological-asset', [$company, $item]) }}"
                        onchange="toggleBiologicalAsset(this)">
                    IAS 41
                </label>
                @endif
                @if (in_array($item->account_type, ['assets', 'liabilities']))
                <label class="coa-sep-toggle {{ $item->is_lease_asset ? 'is-on' : '' }}"
                    title="Flag as lease control account — balance sheet will use the lease register's carrying amount instead">
                    <input type="checkbox" {{ $item->is_lease_asset ? 'checked' : '' }}
                        data-url="{{ route('companies.chart-of-accounts.lease-asset', [$company, $item]) }}"
                        onchange="toggleLeaseAsset(this)">
                    IFRS 16
                </label>
                @endif
                @if ($item->account_type === 'assets')
                <label class="coa-sep-toggle {{ $item->is_cash ? 'is-on' : '' }}"
                    title="Flag as cash or cash equivalent — used by the cash flow statement (IAS 7) to identify cash accounts">
                    <input type="checkbox" {{ $item->is_cash ? 'checked' : '' }}
                        data-url="{{ route('companies.chart-of-accounts.cash', [$company, $item]) }}"
                        onchange="toggleCash(this)">
                    IAS 7
                </label>
                @endif
            </td>
            <td class="hide-mobile"
                style="font-size:6pt;color:#8b7aad;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                {{ $item->description ?? '—' }}
            </td>
            {!! $coaBalCell($itemBal, $item->id, $item->account_type) !!}
        </tr>
    @endforeach

    {{-- Group subtotal --}}
    <tr class="is-group-subtotal" data-coa-parents="{{ implode(' ', $ancestorIds) }}">
        <td></td>
        <td></td>
        <td colspan="2" style="color:#4c1d95;font-size:7pt;">Total {{ $account->account_name }}</td>
        {!! $coaBalCell((float) $childSum, $account->id, $account->account_type) !!}
    </tr>
@else
    {{-- Standalone account (no children) — can still receive children --}}
    <tr class="coa-searchable" data-coa-parents="{{ implode(' ', $ancestorIds) }}" data-search="{{ strtolower($account->account_code . ' ' . $account->account_name . ' ' . ($account->category ?? '') . ' ' . ($account->description ?? '')) }}">
        <td style="font-family:'DejaVu Sans Mono',monospace;font-size:6.5pt;font-weight:700;color:#7c3aed;white-space:nowrap;">
            {{ $account->account_code }}
        </td>
        <td style="font-size:6pt;color:#8b7aad;">{{ $account->category ?? '' }}</td>
        <td style="font-weight:600;font-size:7pt;color:#4c1d95;">
            {!! $coaFileIcon !!}<a class="coa-account-link" href="{{ route('companies.transactions', [$company, 'account_id' => $account->id]) }}" title="View transactions for {{ $account->account_name }}">{{ $account->account_name }}</a>
            <button type="button" class="coa-add-child-btn" onclick="{{ $addChildJs }}">
                + Child
            </button>
            @include('companies._coa-row-actions', ['acct' => $account])
            @if (in_array($account->account_type, ['income', 'expenses']))
            <label class="coa-sep-toggle {{ $account->is_oci ? 'is-on' : '' }}"
                title="Classify this account as Other Comprehensive Income — it will appear in the OCI section of the income statement, not in profit or loss">
                <input type="checkbox" {{ $account->is_oci ? 'checked' : '' }}
                    data-url="{{ route('companies.chart-of-accounts.oci', [$company, $account]) }}"
                    onchange="toggleOci(this)">
                OCI
            </label>
            @endif
            @if ($account->account_type === 'assets')
            <label class="coa-sep-toggle {{ $account->is_ppe ? 'is-on' : '' }}"
                title="Flag as PPE control account — balance sheet will use the asset register's carrying amount instead">
                <input type="checkbox" {{ $account->is_ppe ? 'checked' : '' }}
                    data-url="{{ route('companies.chart-of-accounts.ppe', [$company, $account]) }}"
                    onchange="togglePpe(this)">
                IAS 16
            </label>
            <label class="coa-sep-toggle {{ $account->is_intangible ? 'is-on' : '' }}"
                title="Flag as intangible asset control account — balance sheet will use the intangible register's carrying amount instead">
                <input type="checkbox" {{ $account->is_intangible ? 'checked' : '' }}
                    data-url="{{ route('companies.chart-of-accounts.intangible', [$company, $account]) }}"
                    onchange="toggleIntangible(this)">
                IAS 38
            </label>
            <label class="coa-sep-toggle {{ $account->is_inventory ? 'is-on' : '' }}"
                title="Flag as inventory control account — balance sheet will use the inventory register's carrying amount instead">
                <input type="checkbox" {{ $account->is_inventory ? 'checked' : '' }}
                    data-url="{{ route('companies.chart-of-accounts.inventory', [$company, $account]) }}"
                    onchange="toggleInventory(this)">
                IAS 2
            </label>
            <label class="coa-sep-toggle {{ $account->is_investment_property ? 'is-on' : '' }}"
                title="Flag as investment property control account — balance sheet will use the investment property register's carrying amount instead">
                <input type="checkbox" {{ $account->is_investment_property ? 'checked' : '' }}
                    data-url="{{ route('companies.chart-of-accounts.investment-property', [$company, $account]) }}"
                    onchange="toggleInvestmentProperty(this)">
                IAS 40
            </label>
            <label class="coa-sep-toggle {{ $account->is_biological_asset ? 'is-on' : '' }}"
                title="Flag as biological asset control account — balance sheet will use the biological asset register's carrying amount instead">
                <input type="checkbox" {{ $account->is_biological_asset ? 'checked' : '' }}
                    data-url="{{ route('companies.chart-of-accounts.biological-asset', [$company, $account]) }}"
                    onchange="toggleBiologicalAsset(this)">
                IAS 41
            </label>
            @endif
            @if (in_array($account->account_type, ['assets', 'liabilities']))
            <label class="coa-sep-toggle {{ $account->is_lease_asset ? 'is-on' : '' }}"
                title="Flag as lease control account — balance sheet will use the lease register's carrying amount instead">
                <input type="checkbox" {{ $account->is_lease_asset ? 'checked' : '' }}
                    data-url="{{ route('companies.chart-of-accounts.lease-asset', [$company, $account]) }}"
                    onchange="toggleLeaseAsset(this)">
                IFRS 16
            </label>
            @endif
            @if ($account->account_type === 'assets')
            <label class="coa-sep-toggle {{ $account->is_cash ? 'is-on' : '' }}"
                title="Flag as cash or cash equivalent — used by the cash flow statement (IAS 7) to identify cash accounts">
                <input type="checkbox" {{ $account->is_cash ? 'checked' : '' }}
                    data-url="{{ route('companies.chart-of-accounts.cash', [$company, $account]) }}"
                    onchange="toggleCash(this)">
                IAS 7
            </label>
            @endif
        </td>
        <td class="hide-mobile"
            style="font-size:6pt;color:#8b7aad;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            {{ $account->description ?? '—' }}
        </td>
        {!! $coaBalCell($displayBal, $account->id, $account->account_type) !!}
    </tr>
@endif
