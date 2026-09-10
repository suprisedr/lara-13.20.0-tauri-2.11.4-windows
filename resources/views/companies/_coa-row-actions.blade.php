{{--
    Inline edit + delete controls for a chart-of-accounts row.
    Variables: $acct (ChartOfAccount), $company (parent scope).
--}}
<button type="button" class="coa-rename-btn"
    onclick="openRenameModal({{ json_encode(route('companies.chart-of-accounts.update', [$company, $acct])) }}, {{ json_encode($acct->account_name) }}, {{ $acct->is_contra ? 'true' : 'false' }})"
    title="Edit this account">&#9998;</button>
@if ($acct->is_contra)
    <span class="coa-sep-toggle is-on" style="margin-left:4pt;" title="Contra account — excluded from financial statements">Contra</span>
@endif
@if ($acct->deletable ?? false)
    <form method="POST" action="{{ route('companies.chart-of-accounts.destroy', [$company, $acct]) }}"
        style="display:inline;" onsubmit="return false"
        data-confirm-label="Chart of Accounts"
        data-confirm-title="Delete Account"
        data-confirm-body="This account will be permanently deleted. This cannot be undone."
        data-confirm-text="Delete"
        data-confirm-danger="1">
        @csrf
        @method('DELETE')
        <button type="submit" class="coa-rename-btn" title="Delete account" style="color:#9ec1f5;">&times;</button>
    </form>
@endif
