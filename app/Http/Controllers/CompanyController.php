<?php

namespace App\Http\Controllers;

use App\Exports\AllRegistersExport;
use App\Exports\ChartOfAccountsExport;
use App\Exports\RegisterExport;
use App\Exports\TrialBalanceExport;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\FinancialPeriod;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class CompanyController extends Controller
{
    public function show(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        return view('companies.show', compact('company'));
    }

    public function dashboard(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $defaultStart = $this->fyStartDate($company);
        $defaultEnd = now()->format('Y-m-d');

        $startDate = $request->input('start_date', $defaultStart);
        $endDate = $request->input('end_date', $defaultEnd);

        $startCarbon = Carbon::parse($startDate);
        $endCarbon = Carbon::parse($endDate);
        $priorStart = $startCarbon->copy()->subYear()->format('Y-m-d');
        $priorEnd = $endCarbon->copy()->subYear()->format('Y-m-d');

        $incomeExpenseIds = $company->chartOfAccounts()
            ->whereIn('account_type', ['income', 'expenses'])
            ->where('is_oci', false)->where('is_contra', false)
            ->pluck('id');
        $incomeIds = $company->chartOfAccounts()
            ->where('account_type', 'income')
            ->where('is_oci', false)->where('is_contra', false)
            ->pluck('id');
        $expenseIds = $company->chartOfAccounts()
            ->where('account_type', 'expenses')
            ->where('is_oci', false)->where('is_contra', false)
            ->pluck('id');

        $curTotals = $this->accountNetTotals($company, $incomeExpenseIds, $startDate, $endDate);
        $priorTotals = $this->accountNetTotals($company, $incomeExpenseIds, $priorStart, $priorEnd);

        $sumByType = function ($ids, $totals, $type) {
            return $ids->reduce(function ($carry, $id) use ($totals, $type) {
                $row = $totals->get($id);
                $d = $row ? (float) $row->total_debits : 0.0;
                $c = $row ? (float) $row->total_credits : 0.0;
                return $carry + ($type === 'income' ? $c - $d : $d - $c);
            }, 0.0);
        };

        $revenue = $sumByType($incomeIds, $curTotals, 'income');
        $expenses = $sumByType($expenseIds, $curTotals, 'expenses');
        $netProfit = $revenue - $expenses;
        $priorRevenue = $sumByType($incomeIds, $priorTotals, 'income');
        $priorExpenses = $sumByType($expenseIds, $priorTotals, 'expenses');
        $priorNetProfit = $priorRevenue - $priorExpenses;

        $cashAccounts = $company->chartOfAccounts()
            ->where('account_type', 'assets')->where('is_cash', true)->get();
        $cashIds = $cashAccounts->pluck('id');
        $cashPosition = 0.0;
        if ($cashIds->isNotEmpty()) {
            $cashPosition = $this->cashBalanceAsOf($company, $cashAccounts, $endDate);
        }

        $monthlyData = [];
        $cursor = $startCarbon->copy()->startOfMonth();
        while ($cursor->lte($endCarbon)) {
            $mStart = $cursor->copy();
            if ($mStart->lt($startCarbon)) $mStart = $startCarbon->copy();
            $mEnd = $cursor->copy()->endOfMonth();
            if ($mEnd->gt($endCarbon)) $mEnd = $endCarbon->copy();

            $mTotals = $this->accountNetTotals($company, $incomeExpenseIds, $mStart->format('Y-m-d'), $mEnd->format('Y-m-d'));
            $monthlyData[] = [
                'month' => $cursor->format('M Y'),
                'revenue' => round($sumByType($incomeIds, $mTotals, 'income'), 2),
                'expenses' => round($sumByType($expenseIds, $mTotals, 'expenses'), 2),
            ];
            $cursor->addMonth();
        }

        $cashFlowTrend = [];
        $cursor = $startCarbon->copy()->startOfMonth();
        while ($cursor->lte($endCarbon)) {
            $mStart = $cursor->copy();
            if ($mStart->lt($startCarbon)) $mStart = $startCarbon->copy();
            $mEnd = $cursor->copy()->endOfMonth();
            if ($mEnd->gt($endCarbon)) $mEnd = $endCarbon->copy();

            $cf = $this->buildCashFlow($company, $mStart->format('Y-m-d'), $mEnd->format('Y-m-d'));
            $cashFlowTrend[] = [
                'month' => $cursor->format('M Y'),
                'operating' => round($cf['netOperating'], 2),
                'investing' => round($cf['netInvesting'], 2),
                'financing' => round($cf['netFinancing'], 2),
            ];
            $cursor->addMonth();
        }

        $bs = $this->buildBalanceSheet($company, $endDate);

        $bsComposition = [
            ['label' => 'Current Assets', 'value' => round(abs($bs['totalCurrentAssets']), 2)],
            ['label' => 'Non-Current Assets', 'value' => round(abs($bs['totalNonCurrentAssets']), 2)],
            ['label' => 'Current Liabilities', 'value' => round(abs($bs['totalCurrentLiabilities']), 2)],
            ['label' => 'Non-Current Liabilities', 'value' => round(abs($bs['totalNonCurrentLiabilities']), 2)],
            ['label' => 'Equity', 'value' => round(abs($bs['totalEquity']), 2)],
        ];

        $arAging = ['current' => 0, 'days_31_60' => 0, 'days_61_90' => 0, 'days_91_plus' => 0];
        $ageService = new \App\Services\AgeAnalysisService();
        $ageRows = $ageService->generate($company, $endDate);
        foreach ($ageRows as $row) {
            $arAging['current'] += (float) $row->current_amount;
            $arAging['days_31_60'] += (float) $row->days_31_60;
            $arAging['days_61_90'] += (float) $row->days_61_90;
            $arAging['days_91_plus'] += (float) $row->days_91_plus;
        }
        $arAging = array_map(fn ($v) => round($v, 2), $arAging);

        $recentTransactions = $company->transactions()
            ->where('status', 'posted')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->with('journalLines')
            ->latest('transaction_date')
            ->latest('id')
            ->take(10)
            ->get()
            ->map(function ($t) use ($cashIds) {
                $cashLines = $t->journalLines->whereIn('chart_of_account_id', $cashIds);
                $netCash = $cashLines->where('type', 'debit')->sum('amount')
                    - $cashLines->where('type', 'credit')->sum('amount');

                return [
                    'id' => $t->id,
                    'date' => $t->transaction_date->format('d M Y'),
                    'description' => $t->description,
                    'reference' => $t->reference,
                    'amount' => round($t->journalLines->where('type', 'debit')->sum('amount'), 2),
                    'cash_flow' => abs($netCash) < 0.01 ? 'none' : ($netCash > 0 ? 'inflow' : 'outflow'),
                ];
            });

        return view('companies.dashboard', compact(
            'company', 'startDate', 'endDate',
            'revenue', 'expenses', 'netProfit', 'cashPosition',
            'priorRevenue', 'priorExpenses', 'priorNetProfit',
            'monthlyData', 'cashFlowTrend', 'bsComposition', 'arAging',
            'recentTransactions',
        ));
    }

    public function toggleStatus(Company $company): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $company->update(['status' => $company->isActive() ? 'inactive' : 'active']);

        return redirect()->route('dashboard')->with('success', '"' . $company->registered_name . '" marked as ' . ($company->isActive() ? 'Active' : 'Inactive') . '.');
    }

    public function updateProfile(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'registered_name'          => ['required', 'string', 'max:255'],
            'company_type'             => ['required', 'in:' . implode(',', array_keys(Company::companyTypes()))],
            'registration_number'      => ['nullable', 'string', 'max:50'],
            'financial_year_end_month' => ['required', 'integer', 'between:1,12'],
            'industry'                 => ['nullable', 'in:' . implode(',', array_keys(Company::industries()))],
            'income_tax_number'        => ['nullable', 'string', 'max:20'],
            'vat_number'               => ['nullable', 'string', 'max:10'],
            'paye_number'              => ['nullable', 'string', 'max:20'],
            'uif_number'               => ['nullable', 'string', 'max:20'],
            'sdl_number'               => ['nullable', 'string', 'max:20'],
            'address_line_1'           => ['nullable', 'string', 'max:255'],
            'address_line_2'           => ['nullable', 'string', 'max:255'],
            'city'                     => ['nullable', 'string', 'max:100'],
            'province'                 => ['nullable', 'in:' . implode(',', array_keys(Company::saProvinces()))],
            'postal_code'              => ['nullable', 'string', 'max:10'],
            'bank_name'                => ['nullable', 'string', 'max:100'],
            'bank_account_number'      => ['nullable', 'string', 'max:20'],
            'bank_account_type'        => ['nullable', 'in:current,savings,cheque'],
            'bank_branch_code'         => ['nullable', 'string', 'max:6'],
            'logo'                     => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'corporate_tax_rate'       => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if ($request->boolean('remove_logo') && $company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
            $validated['logo_path'] = null;
        } elseif ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        unset($validated['logo']);
        $company->update($validated);

        return redirect()
            ->route('companies.show', $company)
            ->with('success', 'Company profile updated.');
    }

    public function chartOfAccounts(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $startDate = $request->input('start_date', $this->fyStartDate($company));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        $accountIds = $company->chartOfAccounts()->pluck('id');

        $totals = $startDate
            ? $this->accountNetTotals($company, $accountIds, $startDate, $endDate)
            : $this->accountNetTotalsCumulative($company, $accountIds, $endDate);

        // Accounts that have any journal lines cannot be safely deleted (their
        // transactions would lose a leg via the cascade), regardless of balance.
        $accountsWithLines = DB::table('journal_lines')
            ->whereIn('chart_of_account_id', $accountIds)
            ->distinct()
            ->pluck('chart_of_account_id')
            ->flip();
        $parentIds = $company->chartOfAccounts()->whereNotNull('parent_id')->pluck('parent_id')->unique()->flip();

        // Compute running balance per account
        $all = $company->chartOfAccounts()
            ->orderBy('account_code')
            ->get()
            ->map(function ($account) use ($totals, $startDate, $accountsWithLines, $parentIds) {
                $row = $totals->get($account->id);
                $debits  = $row ? (float) $row->total_debits : 0.0;
                $credits = $row ? (float) $row->total_credits : 0.0;

                // When a start_date is set we show activity for the period only (no opening balance carry-in).
                // When no start_date, opening balance is always included (cumulative view).
                $opening = $startDate ? 0.0 : (float) $account->opening_balance;

                // Assets & expenses: normal debit balance; liabilities, equity & income: normal credit balance
                $account->balance = in_array($account->account_type, ['assets', 'expenses'])
                    ? $opening + ($debits - $credits)
                    : $opening + ($credits - $debits);

                // Deletable only when it has no transactions, no sub-accounts, and no opening balance.
                $account->deletable = ! $accountsWithLines->has($account->id)
                    && ! $parentIds->has($account->id)
                    && (float) $account->opening_balance == 0.0;

                return $account;
            });

        // Attach children to their parents then group top-level accounts by type
        $childrenByParent = $all->whereNotNull('parent_id')->groupBy('parent_id');

        $chartOfAccounts = $all->whereNull('parent_id')
            ->map(function ($account) use ($childrenByParent) {
                $items = $childrenByParent->get($account->id, collect())->sortBy('account_code')->values();
                $account->items = $items;

                return $account;
            })
            ->groupBy('account_type');

        $defaultStartDate = $this->fyStartDate($company);

        return view('companies.chart-of-accounts', compact('company', 'chartOfAccounts', 'startDate', 'endDate', 'defaultStartDate'));
    }

    public function chartOfAccountsExport(Company $company, Request $request): Response
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $format    = $request->input('format', 'csv');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        $accountIds = $company->chartOfAccounts()->pluck('id');

        $totals = $startDate
            ? $this->accountNetTotals($company, $accountIds, $startDate, $endDate)
            : $this->accountNetTotalsCumulative($company, $accountIds, $endDate);

        $accounts = $company->chartOfAccounts()
            ->orderBy('account_code')
            ->get()
            ->map(function ($account) use ($totals, $startDate) {
                $row     = $totals->get($account->id);
                $debits  = $row ? (float) $row->total_debits : 0.0;
                $credits = $row ? (float) $row->total_credits : 0.0;
                $opening = $startDate ? 0.0 : (float) $account->opening_balance;

                $account->balance = in_array($account->account_type, ['assets', 'expenses'])
                    ? $opening + ($debits - $credits)
                    : $opening + ($credits - $debits);

                return $account;
            });

        $periodLabel = $startDate
            ? "From {$startDate} to {$endDate}"
            : "Cumulative to {$endDate}";

        $baseName = implode('-', array_filter([
            $company->slug,
            'chart-of-accounts',
            $startDate ? str_replace('-', '', $startDate) : null,
            str_replace('-', '', $endDate),
        ]));

        return match ($format) {
            'xlsx'  => Excel::download(
                new ChartOfAccountsExport($accounts, $company->registered_name, $periodLabel),
                $baseName . '.xlsx',
                \Maatwebsite\Excel\Excel::XLSX,
            ),
            'ods'   => Excel::download(
                new ChartOfAccountsExport($accounts, $company->registered_name, $periodLabel),
                $baseName . '.ods',
                \Maatwebsite\Excel\Excel::ODS,
            ),
            'pdf'   => Pdf::loadView('pdf.chart-of-accounts', compact('company', 'accounts', 'periodLabel'))
                ->setPaper('a4', 'potrait')
                ->download($baseName . '.pdf'),
            default => response()->streamDownload(function () use ($accounts, $periodLabel, $company) {
                $handle = fopen('php://output', 'w');

                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv($handle, ['Company', $company->registered_name]);
                fputcsv($handle, ['Period', $periodLabel]);
                fputcsv($handle, ['Exported', now()->format('d M Y H:i')]);
                fputcsv($handle, []);
                fputcsv($handle, ['Account Code', 'Account Name', 'Type', 'Category', 'Role', 'Description', 'Is Active', 'Balance (ZAR)']);

                $typeOrder = ['assets', 'liabilities', 'equity', 'income', 'expenses'];

                foreach ($typeOrder as $type) {
                    $group = $accounts->where('account_type', $type)->sortBy('account_code');
                    if ($group->isEmpty()) {
                        continue;
                    }

                    fputcsv($handle, []);
                    fputcsv($handle, [strtoupper($type), '', '', '', '', '', '', '']);

                    foreach ($group as $account) {
                        fputcsv($handle, [
                            $account->account_code,
                            $account->account_name,
                            $account->account_type,
                            $account->category ?? '',
                            $account->parent_id ? 'Item' : 'Group',
                            $account->description ?? '',
                            $account->is_active ? 'Yes' : 'No',
                            number_format($account->balance, 2, '.', ''),
                        ]);
                    }
                }

                fclose($handle);
            }, $baseName . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']),
        };
    }

    public function storeChartOfAccount(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'account_name' => ['required', 'string', 'max:255'],
            'account_type' => ['required', 'in:assets,liabilities,equity,income,expenses'],
            'expense_class' => ['nullable', 'in:cost_of_sales,operating,finance,tax'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        // For expenses, the IFRS classification (cost of sales / operating / finance /
        // tax) determines which account-code band the statement of profit or loss
        // groups the account under. Default to operating expenses — cost of sales is
        // reserved for direct costs of generating revenue and must be chosen explicitly.
        $expenseClass = $validated['account_type'] === 'expenses'
            ? ($validated['expense_class'] ?? 'operating')
            : null;

        $code = $this->nextParentAccountCode($company, $validated['account_type'], $expenseClass);

        $company->chartOfAccounts()->create([
            'account_code' => $code,
            'account_name' => $validated['account_name'],
            'account_type' => $validated['account_type'],
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_contra' => $request->boolean('is_contra'),
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        return redirect()
            ->route('companies.chart-of-accounts', $company)
            ->with('success', 'Account "' . $validated['account_name'] . '" created successfully.');
    }

    public function storeChartOfAccountItem(Company $company, ChartOfAccount $account, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($account->company_id === $company->id, 403);

        $validated = $request->validate([
            'account_name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'opening_balance' => ['nullable', 'numeric'],
        ]);

        $code = $this->nextChildAccountCode($company, $account);

        $company->chartOfAccounts()->create([
            'account_code' => $code,
            'account_name' => $validated['account_name'],
            'account_type' => $account->account_type,
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
            'parent_id' => $account->id,
            'parent_code' => $account->account_code,
            'is_contra' => $request->boolean('is_contra'),
            'opening_balance' => $validated['opening_balance'] ?? 0,
            'is_active' => true,
        ]);

        return redirect()
            ->route('companies.chart-of-accounts', $company)
            ->with('success', 'Child account created under "' . $account->account_name . '".');
    }

    public function toggleChartOfAccountContra(Company $company, ChartOfAccount $account, Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($account->company_id === $company->id, 403);

        $account->update(['is_contra' => $request->boolean('is_contra')]);

        return response()->json(['success' => true, 'is_contra' => $account->is_contra]);
    }

    public function toggleChartOfAccountShowSeparately(Company $company, ChartOfAccount $account, Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($account->company_id === $company->id, 403);

        $account->update(['show_separately' => $request->boolean('show_separately')]);

        return response()->json(['success' => true, 'show_separately' => $account->show_separately]);
    }

    public function toggleChartOfAccountPpe(Company $company, ChartOfAccount $account, Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($account->company_id === $company->id, 403);

        $account->update(['is_ppe' => $request->boolean('is_ppe')]);

        return response()->json(['success' => true, 'is_ppe' => $account->is_ppe]);
    }

    public function toggleChartOfAccountIntangible(Company $company, ChartOfAccount $account, Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($account->company_id === $company->id, 403);

        $account->update(['is_intangible' => $request->boolean('is_intangible')]);

        return response()->json(['success' => true, 'is_intangible' => $account->is_intangible]);
    }

    public function toggleChartOfAccountInventory(Company $company, ChartOfAccount $account, Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($account->company_id === $company->id, 403);

        $account->update(['is_inventory' => $request->boolean('is_inventory')]);

        return response()->json(['success' => true, 'is_inventory' => $account->is_inventory]);
    }

    public function toggleChartOfAccountInvestmentProperty(Company $company, ChartOfAccount $account, Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($account->company_id === $company->id, 403);

        $account->update(['is_investment_property' => $request->boolean('is_investment_property')]);

        return response()->json(['success' => true, 'is_investment_property' => $account->is_investment_property]);
    }

    public function toggleChartOfAccountBiologicalAsset(Company $company, ChartOfAccount $account, Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($account->company_id === $company->id, 403);

        $account->update(['is_biological_asset' => $request->boolean('is_biological_asset')]);

        return response()->json(['success' => true, 'is_biological_asset' => $account->is_biological_asset]);
    }

    public function toggleChartOfAccountLeaseAsset(Company $company, ChartOfAccount $account, Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($account->company_id === $company->id, 403);

        $account->update(['is_lease_asset' => $request->boolean('is_lease_asset')]);

        return response()->json(['success' => true, 'is_lease_asset' => $account->is_lease_asset]);
    }

    public function toggleChartOfAccountCash(Company $company, ChartOfAccount $account, Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($account->company_id === $company->id, 403);

        $account->update(['is_cash' => $request->boolean('is_cash')]);

        return response()->json(['success' => true, 'is_cash' => $account->is_cash]);
    }

    public function toggleChartOfAccountOci(Company $company, ChartOfAccount $account, Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($account->company_id === $company->id, 403);

        $account->update(['is_oci' => $request->boolean('is_oci')]);

        return response()->json(['success' => true, 'is_oci' => $account->is_oci]);
    }

    public function destroyChartOfAccount(Company $company, ChartOfAccount $account): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($account->company_id === $company->id, 403);

        if ($company->chartOfAccounts()->where('parent_id', $account->id)->exists()) {
            return back()->with('error', 'Delete the sub-accounts first before deleting "' . $account->account_name . '".');
        }

        $hasLines = DB::table('journal_lines')->where('chart_of_account_id', $account->id)->exists();
        if ($hasLines || (float) $account->opening_balance != 0.0) {
            return back()->with('error', 'Only accounts with a zero balance and no transactions can be deleted.');
        }

        $name = $account->account_name;
        $account->delete();

        return redirect()
            ->route('companies.chart-of-accounts', $company)
            ->with('success', 'Account "' . $name . '" deleted.');
    }

    public function updateChartOfAccount(Company $company, ChartOfAccount $account, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($account->company_id === $company->id, 403);

        $validated = $request->validate([
            'account_name' => ['required', 'string', 'max:255'],
        ]);

        $account->update([
            'account_name' => $validated['account_name'],
            'is_contra'    => $request->boolean('is_contra'),
        ]);

        return redirect()
            ->route('companies.chart-of-accounts', $company)
            ->with('success', 'Account "' . $validated['account_name'] . '" updated.');
    }

    private function nextParentAccountCode(Company $company, string $type, ?string $expenseClass = null): string
    {
        return app(\App\Services\ChartOfAccountCodeResolver::class)->nextTopLevelCode($company, $type, $expenseClass);
    }

    private function nextChildAccountCode(Company $company, ChartOfAccount $parent): string
    {
        return app(\App\Services\ChartOfAccountCodeResolver::class)->nextChildCode($company, $parent);
    }

    public function editOpeningBalances(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $this->ensureFinancialPeriods($company);

        $periods = $company->financialPeriods()->get();
        $selectedId = (int) $request->input('period', $periods->last()->id);
        $period = $periods->firstWhere('id', $selectedId) ?? $periods->last();

        $openingMap = $period->openingBalances()->pluck('amount', 'chart_of_account_id');

        $all = $company->chartOfAccounts()->orderBy('account_code')->get();

        // Parent (group) accounts are not editable here — their opening balance is
        // the roll-up of their children's opening balances. Only leaf accounts get
        // an editable input.
        $childOpeningByParent = $all
            ->whereNotNull('parent_id')
            ->groupBy('parent_id')
            ->map(fn ($children) => $children->sum(fn ($c) => (float) ($openingMap[$c->id] ?? 0)));

        $closing = $this->periodClosingBalances($company, $period);

        // Roll up closing balances for parent accounts
        $childClosingByParent = $all
            ->whereNotNull('parent_id')
            ->groupBy('parent_id')
            ->map(fn ($children) => $children->sum(fn ($c) => (float) ($closing[$c->id] ?? 0)));

        $accounts = $all
            ->map(function ($account) use ($openingMap, $childOpeningByParent, $closing, $childClosingByParent) {
                $account->is_parent = $childOpeningByParent->has($account->id);
                $account->period_opening = $account->is_parent
                    ? (float) $childOpeningByParent->get($account->id)
                    : (float) ($openingMap[$account->id] ?? 0);
                $account->period_closing = $account->is_parent
                    ? (float) $childClosingByParent->get($account->id, 0)
                    : (float) ($closing[$account->id] ?? 0);

                return $account;
            })
            ->groupBy('account_type');

        return view('companies.opening-balances', compact('company', 'accounts', 'periods', 'period'));
    }

    public function updateOpeningBalances(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $this->ensureFinancialPeriods($company);

        $validated = $request->validate([
            'period_id' => ['required', 'integer'],
            'balances' => ['required', 'array'],
            'balances.*' => ['required', 'numeric'],
        ]);

        $period = $company->financialPeriods()->findOrFail($validated['period_id']);
        $earliestPeriodId = $company->financialPeriods()->first()->id;
        $accountsById = $company->chartOfAccounts()->get(['id'])->keyBy('id');

        foreach ($validated['balances'] as $accountId => $balance) {
            if (! $accountsById->has((int) $accountId)) {
                continue; // ignore anything not belonging to this company
            }

            $period->openingBalances()->updateOrCreate(
                ['chart_of_account_id' => (int) $accountId],
                ['amount' => (float) $balance],
            );

            // Keep the legacy single-column opening balance in sync for the earliest
            // period, so companies/queries that have not adopted periods still work.
            if ($period->id === $earliestPeriodId) {
                $company->chartOfAccounts()->where('id', (int) $accountId)->update(['opening_balance' => (float) $balance]);
            }
        }

        return redirect()
            ->route('companies.opening-balances.edit', [$company, 'period' => $period->id])
            ->with('success', 'Opening balances updated for ' . $period->label . '.');
    }

    /**
     * Open the next financial year. Each account's opening balance defaults to the
     * prior period's closing balance carried forward (balance-sheet accounts only;
     * income/expense accounts reset to zero as they close to retained earnings).
     * The defaults are editable afterwards on the opening-balances screen.
     */
    public function openNextPeriod(Company $company): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $this->ensureFinancialPeriods($company);

        $last = $company->financialPeriods()->get()->last();
        $startStr = Carbon::parse($last->end_date)->addDay()->format('Y-m-d');
        $endStr = Carbon::parse($startStr)->addYear()->subDay()->format('Y-m-d');

        if ($company->financialPeriods()->whereDate('start_date', $startStr)->exists()) {
            return redirect()
                ->route('companies.opening-balances.edit', $company)
                ->with('error', 'The next financial period already exists.');
        }

        $closing = $this->periodClosingBalances($company, $last);

        $period = $company->financialPeriods()->create([
            'label' => $this->periodLabel($startStr, $endStr),
            'start_date' => $startStr,
            'end_date' => $endStr,
        ]);

        foreach ($company->chartOfAccounts()->get(['id', 'account_type']) as $account) {
            $carry = in_array($account->account_type, ['assets', 'liabilities', 'equity'], true)
                ? ($closing[$account->id] ?? 0.0)
                : 0.0;

            $period->openingBalances()->create([
                'chart_of_account_id' => $account->id,
                'amount' => round($carry, 2),
            ]);
        }

        return redirect()
            ->route('companies.opening-balances.edit', [$company, 'period' => $period->id])
            ->with('success', 'Opened ' . $period->label . ' with balances brought forward. Review and adjust as needed.');
    }

    /**
     * Ensure the company has at least one financial period. The first period spans
     * the financial year containing today and is seeded from the legacy
     * chart_of_accounts.opening_balance column.
     */
    private function ensureFinancialPeriods(Company $company): void
    {
        if ($company->financialPeriods()->exists()) {
            return;
        }

        [$start, $end] = $company->financialYearBounds(now()->toDateString());

        $period = $company->financialPeriods()->create([
            'label' => $this->periodLabel($start, $end),
            'start_date' => $start,
            'end_date' => $end,
        ]);

        foreach ($company->chartOfAccounts()->get(['id', 'opening_balance']) as $account) {
            $period->openingBalances()->create([
                'chart_of_account_id' => $account->id,
                'amount' => (float) $account->opening_balance,
            ]);
        }
    }

    private function periodLabel(string $start, string $end): string
    {
        return Carbon::parse($start)->format('d M Y') . ' – ' . Carbon::parse($end)->format('d M Y');
    }

    /**
     * Signed closing balance per account for a period:
     *   opening (for the period) + posted movements within the period.
     *
     * @return array<int,float> [chart_of_account_id => signed closing balance]
     */
    private function periodClosingBalances(Company $company, FinancialPeriod $period): array
    {
        $accounts = $company->chartOfAccounts()->get(['id', 'account_type']);
        $openings = $period->openingBalances()->pluck('amount', 'chart_of_account_id');
        $totals = $this->accountNetTotals(
            $company,
            $accounts->pluck('id'),
            $period->start_date->toDateString(),
            $period->end_date->toDateString(),
        );

        $closing = [];
        foreach ($accounts as $account) {
            $row = $totals->get($account->id);
            $debits = $row ? (float) $row->total_debits : 0.0;
            $credits = $row ? (float) $row->total_credits : 0.0;
            $opening = (float) ($openings[$account->id] ?? 0);

            $closing[$account->id] = in_array($account->account_type, ['assets', 'expenses'], true)
                ? $opening + ($debits - $credits)
                : $opening + ($credits - $debits);
        }

        return $closing;
    }

    public function transactions(Company $company, Request $request): View|\Illuminate\Http\RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $sessionKey = "tx_filters_{$company->id}";

        if ($request->query()) {
            if ($request->boolean('reset')) {
                $request->session()->forget($sessionKey);

                return redirect()->route('companies.transactions', $company);
            }

            $filters = [
                'start_date'  => $request->input('start_date'),
                'end_date'    => $request->input('end_date'),
                'status'      => $request->input('status'),
                'account_id'  => $request->input('account_id'),
                'description' => trim((string) $request->input('description', '')),
                'amount'      => str_replace(',', '.', trim((string) $request->input('amount', ''))),
            ];

            $request->session()->put($sessionKey, $filters);
        } else {
            $filters = $request->session()->get($sessionKey, [
                'start_date'  => null,
                'end_date'    => null,
                'status'      => null,
                'account_id'  => null,
                'description' => '',
                'amount'      => '',
            ]);
        }

        $startDate   = $filters['start_date'];
        $endDate     = $filters['end_date'];
        $status      = $filters['status'];
        $accountId   = $filters['account_id'];
        $description = $filters['description'];
        $amount      = $filters['amount'];

        // ── Full-text via Meilisearch, structured filters on DB ───────
        $usedMeili = false;

        $query = $company->transactions()
            ->with(['journalLines.account', 'reversal', 'reversalOf', 'correction', 'corrects'])
            ->orderBy('transaction_date')
            ->orderBy('id');

        if ($description !== '') {
            try {
                $meiliFilters = ['company_id = ' . $company->id];
                if ($startDate) {
                    $meiliFilters[] = 'transaction_date_timestamp >= ' . \Carbon\Carbon::parse($startDate)->startOfDay()->timestamp;
                }
                if ($endDate) {
                    $meiliFilters[] = 'transaction_date_timestamp <= ' . \Carbon\Carbon::parse($endDate)->endOfDay()->timestamp;
                }
                if ($status && in_array($status, ['draft', 'posted'], true)) {
                    $meiliFilters[] = 'status = "' . $status . '"';
                }

                $searchIds = \App\Models\Transaction::search($description)
                    ->options(['filter' => implode(' AND ', $meiliFilters)])
                    ->take(500)
                    ->keys()
                    ->all();

                $query->whereIn('id', $searchIds);
                $usedMeili = true;

                // Status filters Meilisearch can't handle (relation-based)
                if ($status === 'reversed') {
                    $query->whereHas('reversal');
                } elseif ($status === 'reversal') {
                    $query->whereNotNull('reversal_of_id');
                } elseif ($status === 'correction') {
                    $query->whereNotNull('corrects_id');
                }
            } catch (\Throwable) {
                // Meilisearch down — fall back to DB LIKE
                $needle = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $description) . '%';
                $query->where(function ($q) use ($needle) {
                    $q->where('description', 'like', $needle)
                      ->orWhere('reference', 'like', $needle)
                      ->orWhereHas('journalLines', fn($j) => $j->where('description', 'like', $needle));
                });
            }
        }

        if (! $usedMeili) {
            if ($startDate) {
                $query->whereDate('transaction_date', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('transaction_date', '<=', $endDate);
            }
            if ($status === 'reversed') {
                $query->whereHas('reversal');
            } elseif ($status === 'reversal') {
                $query->whereNotNull('reversal_of_id');
            } elseif ($status === 'correction') {
                $query->whereNotNull('corrects_id');
            } elseif ($status) {
                $query->where('status', $status);
            }
        }

        // Account and amount filters always applied on DB
        if ($accountId) {
            $query->whereHas('journalLines', fn($q) => $q->where('chart_of_account_id', $accountId));
        }
        if ($amount !== '' && is_numeric($amount)) {
            $amountValue = (int) round((float) $amount * 100);
            $query->whereHas('journalLines', fn($q) => $q->whereRaw('CAST(amount * 100 AS INTEGER) = ?', [$amountValue]));
        }

        $transactions = $query->paginate(50)->appends(array_filter($filters, fn ($v) => $v !== null && $v !== ''));
        $accounts     = $company->chartOfAccounts()->orderBy('account_code')->get();
        $postableAccounts = $company->chartOfAccounts()->postable()->orderBy('account_code')->get();

        return view('companies.transactions', compact('company', 'transactions', 'accounts', 'postableAccounts', 'startDate', 'endDate', 'status', 'accountId', 'description', 'amount'));
    }

    public function transactionsSearch(Company $company, Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $q      = trim((string) $request->input('q', ''));
        $amount = $request->input('amount');   // numeric, optional

        $hasText   = $q !== '';
        $hasAmount = $amount !== null && $amount !== '';

        if (! $hasText && ! $hasAmount) {
            return response()->json(['hits' => []]);
        }

        try {
            $client = new \Meilisearch\Client(config('scout.meilisearch.host'), config('scout.meilisearch.key') ?: null);
            $index  = $client->index('transactions');

            // Build filter
            $filters = ['company_id = ' . $company->id];
            if ($hasAmount) {
                $val   = (float) $amount;
                $delta = max($val * 0.001, 0.005); // ±0.1% or half-cent tolerance
                $filters[] = 'total_debit >= ' . ($val - $delta) . ' AND total_debit <= ' . ($val + $delta);
            }
            $filterStr = implode(' AND ', $filters);

            $params = [
                'filter'                => $filterStr,
                'limit'                 => 8,
                'attributesToHighlight' => ['description', 'reference', 'line_text', 'notes'],
                'highlightPreTag'       => '<mark>',
                'highlightPostTag'      => '</mark>',
                'attributesToCrop'      => ['line_text', 'notes'],
                'cropLength'            => 20,
                'attributesToRetrieve'  => ['id', 'description', 'reference', 'status', 'transaction_date', 'total_debit'],
            ];

            if ($hasAmount && ! $hasText) {
                // Pure amount filter — sort closest match first, use empty string query
                $params['sort'] = ['total_debit:asc'];
            }

            $results = $index->search($hasText ? $q : '', $params);

            $hits = collect($results->getHits())->map(function (array $hit) use ($hasAmount) {
                $formatted = $hit['formatted'] ?? [];
                $amountStr = 'R ' . number_format((float) ($hit['total_debit'] ?? 0), 2);
                return [
                    'id'               => $hit['id'],
                    'description'      => $formatted['description'] ?? $hit['description'] ?? '',
                    'reference'        => $formatted['reference'] ?? $hit['reference'] ?? '',
                    'line_text'        => $formatted['line_text'] ?? '',
                    'notes'            => $formatted['notes'] ?? '',
                    'status'           => $hit['status'] ?? '',
                    'transaction_date' => $hit['transaction_date'] ?? '',
                    'total_debit'      => $hit['total_debit'] ?? 0,
                    'amount_display'   => $amountStr,
                    'amount_highlight' => $hasAmount,
                ];
            });

            return response()->json(['hits' => $hits]);
        } catch (\Throwable $e) {
            return response()->json(['hits' => [], 'error' => $e->getMessage()]);
        }
    }

    public function customersSearch(Company $company, Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $q = trim((string) $request->input('q', ''));

        if ($q === '') {
            return response()->json(['hits' => []]);
        }

        try {
            $client = new \Meilisearch\Client(config('scout.meilisearch.host'), config('scout.meilisearch.key') ?: null);
            $index  = $client->index('customers');

            $results = $index->search($q, [
                'filter'               => 'company_id = ' . $company->id,
                'limit'                => 10,
                'attributesToRetrieve' => ['id', 'name', 'contact_name', 'email', 'phone', 'address'],
            ]);

            return response()->json(['hits' => $results->getHits()]);
        } catch (\Throwable) {
            // Fallback to DB LIKE
            $needle = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $hits   = $company->customers()
                ->where('is_active', true)
                ->where(fn($query) => $query
                    ->where('name', 'like', $needle)
                    ->orWhere('email', 'like', $needle)
                    ->orWhere('contact_name', 'like', $needle))
                ->limit(10)
                ->get(['id', 'name', 'contact_name', 'email', 'phone', 'address']);

            return response()->json(['hits' => $hits]);
        }
    }

    public function inventorySearch(Company $company, Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $q = trim((string) $request->input('q', ''));

        if ($q === '') {
            return response()->json(['hits' => []]);
        }

        try {
            $client = new \Meilisearch\Client(config('scout.meilisearch.host'), config('scout.meilisearch.key') ?: null);
            $index  = $client->index('inventory_items');

            $results = $index->search($q, [
                'filter'               => ['company_id = ' . $company->id, 'is_active = true'],
                'limit'                => 10,
                'attributesToRetrieve' => ['id', 'name', 'description', 'sku', 'unit_price', 'tax_rate', 'is_service', 'quantity_on_hand'],
            ]);

            return response()->json(['hits' => $results->getHits()]);
        } catch (\Throwable) {
            $needle = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $hits   = $company->inventoryItems()
                ->where('is_active', true)
                ->where(fn($query) => $query
                    ->where('name', 'like', $needle)
                    ->orWhere('description', 'like', $needle)
                    ->orWhere('sku', 'like', $needle))
                ->limit(10)
                ->get(['id', 'name', 'description', 'sku', 'unit_price', 'tax_rate', 'is_service', 'quantity_on_hand']);

            return response()->json(['hits' => $hits]);
        }
    }

    public function transactionsExport(Company $company, Request $request): Response
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $format      = $request->input('format', 'csv');
        $startDate   = $request->input('start_date');
        $endDate     = $request->input('end_date');
        $status      = $request->input('status');
        $accountId   = $request->input('account_id');
        $description = trim((string) $request->input('description', ''));
        $amount      = str_replace(',', '.', trim((string) $request->input('amount', '')));
        $rawIds      = $request->input('transaction_ids');
        $selectedIds = collect(is_array($rawIds) ? $rawIds : explode(',', (string) $rawIds))
            ->map(fn($v) => (int) $v)
            ->filter(fn($v) => $v > 0)
            ->unique()
            ->values()
            ->all();

        $query = $company->transactions()
            ->with('journalLines.account', 'journalLines.customer')
            ->orderBy('transaction_date')
            ->orderBy('id');

        if (!empty($selectedIds)) {
            $query->whereIn('id', $selectedIds);
        }

        if ($startDate) {
            $query->whereDate('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('transaction_date', '<=', $endDate);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($accountId) {
            $query->whereHas('journalLines', fn($q) => $q->where('chart_of_account_id', $accountId));
        }

        if ($description !== '') {
            $needle = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $description) . '%';
            $query->where(function ($q) use ($needle) {
                $q->where('description', 'like', $needle)
                  ->orWhere('reference', 'like', $needle)
                  ->orWhereHas('journalLines', fn($j) => $j->where('description', 'like', $needle));
            });
        }

        if ($amount !== '' && is_numeric($amount)) {
            $amountValue = (int) round((float) $amount * 100);
            $query->whereHas('journalLines', fn($q) => $q->whereRaw('CAST(amount * 100 AS INTEGER) = ?', [$amountValue]));
        }

        $transactions = $query->get();

        $periodLabel = match (true) {
            (bool) $startDate && (bool) $endDate => "From {$startDate} to {$endDate}",
            (bool) $startDate                    => "From {$startDate}",
            (bool) $endDate                      => "Up to {$endDate}",
            default                              => 'All transactions',
        };

        $baseName = implode('-', array_filter([
            $company->slug,
            'transactions',
            $startDate ? str_replace('-', '', $startDate) : null,
            $endDate   ? str_replace('-', '', $endDate)   : null,
        ]));

        return match ($format) {
            'xlsx' => Excel::download(
                new \App\Exports\TransactionsExport($transactions, $company->registered_name, $periodLabel),
                $baseName . '.xlsx',
                \Maatwebsite\Excel\Excel::XLSX,
            ),
            'ods' => Excel::download(
                new \App\Exports\TransactionsExport($transactions, $company->registered_name, $periodLabel),
                $baseName . '.ods',
                \Maatwebsite\Excel\Excel::ODS,
            ),
            'pdf' => Pdf::loadView('pdf.transactions', compact('company', 'transactions', 'periodLabel'))
                ->setPaper('a4', 'potrait')
                ->download($baseName . '.pdf'),
            default => response()->streamDownload(function () use ($transactions, $periodLabel, $company) {
                $handle = fopen('php://output', 'w');
                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv($handle, [$company->registered_name]);
                fputcsv($handle, ['Transactions Journal']);
                fputcsv($handle, [$periodLabel]);
                fputcsv($handle, ['Exported: ' . now()->format('d M Y H:i')]);
                fputcsv($handle, []);
                fputcsv($handle, [
                    'Journal No.', 'Date', 'Customer ID', 'Customer Name',
                    'Reference', 'Transaction Type', 'Account Dr', 'Account Cr',
                    'Debit (R)', 'Credit (R)', 'Running Balance (R)', 'Posted By', 'Narration',
                ]);

                $totalDebits = 0.0;
                $totalCredits = 0.0;
                $seq = 1;

                foreach ($transactions as $tx) {
                    $lines        = $tx->journalLines;
                    $debitLines   = $lines->where('type', 'debit');
                    $creditLines  = $lines->where('type', 'credit');
                    $debitTotal   = (float) $debitLines->sum('amount');
                    $creditTotal  = (float) $creditLines->sum('amount');

                    $drAccount    = $debitLines->first()?->account;
                    $crAccount    = $creditLines->first()?->account;
                    $customerLine = $lines->first(fn($l) => $l->customer_id !== null);
                    $customer     = $customerLine?->customer;

                    fputcsv($handle, [
                        'JNL-' . str_pad($seq, 3, '0', STR_PAD_LEFT),
                        $tx->transaction_date->format('Y-m-d'),
                        $customer?->id ?? '',
                        $customer?->name ?? '',
                        $tx->reference ?? '',
                        $tx->description,
                        $drAccount ? $drAccount->account_code . ' ' . $drAccount->account_name : '',
                        $crAccount ? $crAccount->account_code . ' ' . $crAccount->account_name : '',
                        number_format($debitTotal, 2, '.', ''),
                        number_format($creditTotal, 2, '.', ''),
                        number_format($debitTotal - $creditTotal, 2, '.', ''),
                        '',
                        $tx->notes ?? $lines->first()?->description ?? '',
                    ]);

                    $totalDebits  += $debitTotal;
                    $totalCredits += $creditTotal;
                    $seq++;
                }

                fputcsv($handle, [
                    'JOURNAL TOTALS', '', '', '', '', '', '', '',
                    number_format($totalDebits, 2, '.', ''),
                    number_format($totalCredits, 2, '.', ''),
                    '', '', '',
                ]);

                fclose($handle);
            }, $baseName . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']),
        };
    }

    public function incomeStatement(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $startDate = $request->input('start_date', $this->fyStartDate($company));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $rounding = in_array((int) $request->input('rounding', 1), [1, 1000, 1000000]) ? (int) $request->input('rounding', 1) : 1;
        $compare = $request->boolean('compare');

        ['income' => $incomeAccounts, 'expense' => $expenseAccounts] =
            $this->buildProfitOrLossRows($company, $startDate, $endDate, $compare);

        $totalRevenue = $incomeAccounts->sum('net_amount');
        $totalExpenses = $expenseAccounts->sum('net_amount');
        $netIncome = $totalRevenue - $totalExpenses;
        $totalRevenuePrior = $compare ? $incomeAccounts->sum('prior_net_amount') : 0.0;
        $totalExpensesPrior = $compare ? $expenseAccounts->sum('prior_net_amount') : 0.0;
        $netIncomePrior = $compare ? $totalRevenuePrior - $totalExpensesPrior : 0.0;

        $noteRefs   = $this->noteRefs($company);
        $priorStart = Carbon::parse($startDate)->subYear()->format('Y-m-d');
        $priorEnd   = Carbon::parse($endDate)->subYear()->format('Y-m-d');
        $ociAccounts = $this->buildOciAccounts($company, $startDate, $endDate, $compare, $priorStart, $priorEnd);

        return view('companies.reports.income-statement', compact(
            'company',
            'incomeAccounts',
            'expenseAccounts',
            'totalRevenue',
            'totalExpenses',
            'netIncome',
            'totalRevenuePrior',
            'totalExpensesPrior',
            'netIncomePrior',
            'startDate',
            'endDate',
            'rounding',
            'compare',
            'noteRefs',
            'ociAccounts',
        ));
    }

    public function incomeStatementPdf(Company $company, Request $request): Response
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $startDate = $request->input('start_date', $this->fyStartDate($company));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $rounding = in_array((int) $request->input('rounding', 1), [1, 1000, 1000000]) ? (int) $request->input('rounding', 1) : 1;
        $compare = $request->boolean('compare');

        ['income' => $incomeAccounts, 'expense' => $expenseAccounts] =
            $this->buildProfitOrLossRows($company, $startDate, $endDate, $compare);

        $totalRevenue = $incomeAccounts->sum('net_amount');
        $totalExpenses = $expenseAccounts->sum('net_amount');
        $netIncome = $totalRevenue - $totalExpenses;
        $totalRevenuePrior = $compare ? $incomeAccounts->sum('prior_net_amount') : 0.0;
        $totalExpensesPrior = $compare ? $expenseAccounts->sum('prior_net_amount') : 0.0;
        $netIncomePrior = $compare ? $totalRevenuePrior - $totalExpensesPrior : 0.0;

        $noteRefs    = $this->noteRefs($company);
        $priorStart  = Carbon::parse($startDate)->subYear()->format('Y-m-d');
        $priorEnd    = Carbon::parse($endDate)->subYear()->format('Y-m-d');
        $ociAccounts = $this->buildOciAccounts($company, $startDate, $endDate, $compare, $priorStart, $priorEnd);

        $pdf = Pdf::loadView('pdf.income-statement', compact(
            'company',
            'incomeAccounts',
            'expenseAccounts',
            'totalRevenue',
            'totalExpenses',
            'netIncome',
            'totalRevenuePrior',
            'totalExpensesPrior',
            'netIncomePrior',
            'startDate',
            'endDate',
            'rounding',
            'compare',
            'noteRefs',
            'ociAccounts',
        ))->setPaper('a4', 'potrait');

        return $pdf->download($company->slug . '-income-statement.pdf');
    }

    /**
     * Build the income/expense line collections for the statement of profit or
     * loss. Each child (sub) account is rolled into its parent's displayed total
     * unless it is flagged to be shown separately — in which case it is presented
     * on its own line and excluded from the parent's rolled-up balance. Parent
     * (group) accounts and standalone accounts are always shown.
     *
     * @return array{income:Collection,expense:Collection}
     */
    private function buildProfitOrLossRows(Company $company, string $startDate, string $endDate, bool $compare): array
    {
        $accountIds = $company->chartOfAccounts()->whereIn('account_type', ['income', 'expenses'])->where('is_oci', false)->where('is_contra', false)->pluck('id');
        $totals = $this->accountNetTotals($company, $accountIds, $startDate, $endDate);
        $priorTotals = $compare
            ? $this->accountNetTotals(
                $company,
                $accountIds,
                Carbon::parse($startDate)->subYear()->format('Y-m-d'),
                Carbon::parse($endDate)->subYear()->format('Y-m-d'),
            )
            : collect();

        $all = $company->chartOfAccounts()
            ->whereIn('account_type', ['income', 'expenses'])
            ->where('is_active', true)
            ->where('is_oci', false)
            ->where('is_contra', false)
            ->orderBy('account_code')
            ->get()
            ->map(function ($account) use ($totals, $priorTotals, $compare) {
                $row = $totals->get($account->id);
                $debits = $row ? (float) $row->total_debits : 0.0;
                $credits = $row ? (float) $row->total_credits : 0.0;
                $account->net_amount = $account->account_type === 'income'
                    ? $credits - $debits
                    : $debits - $credits;

                if ($compare) {
                    $priorRow = $priorTotals->get($account->id);
                    $priorDebits = $priorRow ? (float) $priorRow->total_debits : 0.0;
                    $priorCredits = $priorRow ? (float) $priorRow->total_credits : 0.0;
                    $account->prior_net_amount = $account->account_type === 'income'
                        ? $priorCredits - $priorDebits
                        : $priorDebits - $priorCredits;
                }

                return $account;
            });

        $childrenByParent = $all->whereNotNull('parent_id')->groupBy('parent_id');
        $display = collect();

        foreach ($all as $account) {
            // Child accounts: only surface when flagged to show separately.
            if ($account->parent_id !== null) {
                if ($account->show_separately) {
                    $account->is_separate_child = true;
                    $display->push($account);
                }
                continue;
            }

            // Parent (group) accounts roll up children not shown separately.
            $children = $childrenByParent->get($account->id, collect());
            if ($children->isNotEmpty()) {
                $rolled = $children->where('show_separately', false);
                $account->net_amount = (float) $account->net_amount + (float) $rolled->sum('net_amount');
                if ($compare) {
                    $account->prior_net_amount = (float) ($account->prior_net_amount ?? 0) + (float) $rolled->sum('prior_net_amount');
                }
            }

            $display->push($account);
        }

        return [
            'income' => $display->where('account_type', 'income')->values(),
            'expense' => $display->where('account_type', 'expenses')->values(),
        ];
    }

    public function cashFlow(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $startDate = $request->input('start_date', $this->fyStartDate($company));
        $endDate = $request->input('end_date', $this->fyEndDate($company));
        $rounding = in_array((int) $request->input('rounding', 1), [1, 1000, 1000000]) ? (int) $request->input('rounding', 1) : 1;
        $compare = $request->boolean('compare');

        $cf = $this->manualCashFlowViewData($company, $startDate, $endDate, $compare);

        return view('companies.reports.cash-flow', compact(
            'company',
            'cf',
            'startDate',
            'endDate',
            'rounding',
            'compare',
        ));
    }

    public function cashFlowPdf(Company $company, Request $request): Response
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $startDate = $request->input('start_date', $this->fyStartDate($company));
        $endDate = $request->input('end_date', $this->fyEndDate($company));
        $rounding = in_array((int) $request->input('rounding', 1), [1, 1000, 1000000]) ? (int) $request->input('rounding', 1) : 1;
        $compare = true;

        $cf = $this->manualCashFlowViewData($company, $startDate, $endDate, $compare);

        $pdf = Pdf::loadView('pdf.cash-flow', compact(
            'company',
            'cf',
            'startDate',
            'endDate',
            'rounding',
            'compare',
        ))->setPaper('a4', 'potrait');

        return $pdf->download($company->slug . '-cash-flow.pdf');
    }

    public function editCashFlowManual(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $startDate   = $request->input('start_date', $this->fyStartDate($company));
        $endDate     = $request->input('end_date', $this->fyEndDate($company));
        $priorStart  = Carbon::parse($startDate)->subYear()->format('Y-m-d');
        $priorEnd    = Carbon::parse($endDate)->subYear()->format('Y-m-d');

        $entries = DB::table('cash_flow_manual_entries')
            ->where('company_id', $company->id)
            ->where('period_start', $startDate)
            ->where('period_end', $endDate)
            ->orderBy('sort_order')
            ->get();

        // Show prior-year input column when the prior period has no saved entries yet
        $priorCount = DB::table('cash_flow_manual_entries')
            ->where('company_id', $company->id)
            ->where('period_start', $priorStart)
            ->where('period_end', $priorEnd)
            ->count();

        $showPriorInputs = $priorCount === 0;

        // Load prior entries for pre-population (may be empty)
        $priorEntries = $showPriorInputs
            ? DB::table('cash_flow_manual_entries')
                ->where('company_id', $company->id)
                ->where('period_start', $priorStart)
                ->where('period_end', $priorEnd)
                ->orderBy('sort_order')
                ->get()
            : collect();

        $get      = fn ($key) => $entries->firstWhere('line_key', $key);
        $getPrior = fn ($key) => $priorEntries->firstWhere('line_key', $key);

        $operatingFixed = [
            'receipts'   => ['label' => 'Cash receipts from customers',           'entry' => $get('receipts'),   'prior' => $getPrior('receipts')],
            'payments'   => ['label' => 'Cash paid to suppliers and employees',   'entry' => $get('payments'),   'prior' => $getPrior('payments')],
            'interest'   => ['label' => 'Finance costs paid',                     'entry' => $get('interest'),   'prior' => $getPrior('interest')],
            'tax'        => ['label' => 'Tax paid',                               'entry' => $get('tax'),        'prior' => $getPrior('tax')],
            'cash_begin' => ['label' => 'Cash and cash equivalents at beginning', 'entry' => $get('cash_begin'), 'prior' => $getPrior('cash_begin')],
            'cash_end'   => ['label' => 'Cash and cash equivalents at end',       'entry' => $get('cash_end'),   'prior' => $getPrior('cash_end')],
        ];

        $priorDynByName = fn (string $section) => $priorEntries
            ->filter(fn ($e) => $e->section === $section && $e->line_key === null)
            ->keyBy('line_name');

        $investingLines = $entries
            ->filter(fn ($e) => $e->section === 'investing' && $e->line_key === null)
            ->map(fn ($e) => ['entry' => $e, 'priorAmount' => (float) ($priorDynByName('investing')->get($e->line_name)?->current_amount ?? 0)])
            ->values();

        $financingLines = $entries
            ->filter(fn ($e) => $e->section === 'financing' && $e->line_key === null)
            ->map(fn ($e) => ['entry' => $e, 'priorAmount' => (float) ($priorDynByName('financing')->get($e->line_name)?->current_amount ?? 0)])
            ->values();

        // Register-derived investing suggestions (PPE, inventory, investment property, biological assets)
        $registerInvestingSuggestions = $this->registerInvestingLines($company, $startDate, $endDate, $showPriorInputs);
        $existingInvNames = $investingLines->map(fn ($r) => strtolower($r['entry']->line_name))->all();
        $registerInvestingSuggestions = array_values(array_filter(
            $registerInvestingSuggestions,
            fn ($s) => ! in_array(strtolower($s['name']), $existingInvNames)
        ));

        // Register-derived financing suggestions (lease payments)
        $registerFinancingSuggestions = $this->registerFinancingLines($company, $startDate, $endDate, $showPriorInputs);
        $existingFinNames = $financingLines->map(fn ($r) => strtolower($r['entry']->line_name))->all();
        $registerFinancingSuggestions = array_values(array_filter(
            $registerFinancingSuggestions,
            fn ($s) => ! in_array(strtolower($s['name']), $existingFinNames)
        ));

        return view('companies.cash-flow-manual', compact(
            'company', 'startDate', 'endDate', 'priorStart', 'priorEnd',
            'showPriorInputs', 'operatingFixed', 'investingLines', 'financingLines',
            'registerInvestingSuggestions', 'registerFinancingSuggestions',
        ));
    }

    public function saveCashFlowManual(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $startDate = $request->input('period_start');
        $endDate   = $request->input('period_end');

        DB::table('cash_flow_manual_entries')
            ->where('company_id', $company->id)
            ->where('period_start', $startDate)
            ->where('period_end', $endDate)
            ->delete();

        $fixedLabels = [
            'receipts'   => 'Cash receipts from customers',
            'payments'   => 'Cash paid to suppliers and employees',
            'interest'   => 'Finance costs paid',
            'tax'        => 'Tax paid',
            'cash_begin' => 'Cash and cash equivalents at beginning',
            'cash_end'   => 'Cash and cash equivalents at end',
        ];

        $rows = [];
        $sort = 0;
        $now  = now();

        foreach ($fixedLabels as $key => $label) {
            $op = $request->input("operating.{$key}", []);
            $rows[] = [
                'company_id'     => $company->id,
                'period_start'   => $startDate,
                'period_end'     => $endDate,
                'section'        => 'operating',
                'line_key'       => $key,
                'line_name'      => $label,
                'current_amount' => (float) ($op['cur'] ?? 0),
                'prior_amount'   => 0,
                'sort_order'     => $sort++,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        foreach (['investing', 'financing'] as $section) {
            foreach ($request->input($section, []) as $line) {
                $name = trim($line['name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $rows[] = [
                    'company_id'     => $company->id,
                    'period_start'   => $startDate,
                    'period_end'     => $endDate,
                    'section'        => $section,
                    'line_key'       => null,
                    'line_name'      => $name,
                    'current_amount' => (float) ($line['cur'] ?? 0),
                    'prior_amount'   => 0,
                    'sort_order'     => $sort++,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }
        }

        if ($rows) {
            DB::table('cash_flow_manual_entries')->insert($rows);
        }

        // Save prior-year entries into their own period when inline inputs were shown
        if ($request->boolean('show_prior')) {
            $priorStart = $request->input('prior_period_start');
            $priorEnd   = $request->input('prior_period_end');

            DB::table('cash_flow_manual_entries')
                ->where('company_id', $company->id)
                ->where('period_start', $priorStart)
                ->where('period_end', $priorEnd)
                ->delete();

            $priorRows = [];
            $priorSort = 0;

            foreach ($fixedLabels as $key => $label) {
                $op = $request->input("operating.{$key}", []);
                $priorRows[] = [
                    'company_id'     => $company->id,
                    'period_start'   => $priorStart,
                    'period_end'     => $priorEnd,
                    'section'        => 'operating',
                    'line_key'       => $key,
                    'line_name'      => $label,
                    'current_amount' => (float) ($op['pri'] ?? 0),
                    'prior_amount'   => 0,
                    'sort_order'     => $priorSort++,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }

            foreach (['investing', 'financing'] as $section) {
                foreach ($request->input($section, []) as $line) {
                    $name = trim($line['name'] ?? '');
                    if ($name === '') {
                        continue;
                    }
                    $priorRows[] = [
                        'company_id'     => $company->id,
                        'period_start'   => $priorStart,
                        'period_end'     => $priorEnd,
                        'section'        => $section,
                        'line_key'       => null,
                        'line_name'      => $name,
                        'current_amount' => (float) ($line['pri'] ?? 0),
                        'prior_amount'   => 0,
                        'sort_order'     => $priorSort++,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                }
            }

            if ($priorRows) {
                DB::table('cash_flow_manual_entries')->insert($priorRows);
            }
        }

        return redirect()
            ->route('companies.reports.cash-flow', [$company, 'start_date' => $startDate, 'end_date' => $endDate])
            ->with('success', 'Cash flow statement saved.');
    }

    public function balanceSheet(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));
        $rounding = in_array((int) $request->input('rounding', 1), [1, 1000, 1000000]) ? (int) $request->input('rounding', 1) : 1;
        $compare = $request->boolean('compare');

        $data = $this->buildBalanceSheet($company, $asOfDate);

        if ($compare) {
            $priorAsOfDate = $request->input('compare_date') ?: Carbon::parse($asOfDate)->subYear()->format('Y-m-d');
            $priorData = $this->buildBalanceSheet($company, $priorAsOfDate);
            $data = array_merge($data, $this->mergeBalanceSheetComparatives($data, $priorData, $priorAsOfDate));
        } else {
            $data['compare'] = false;
            $data['priorAsOfDate'] = null;
        }

        $noteRefs = $this->noteRefs($company);

        return view('companies.reports.balance-sheet', array_merge($data, compact('company', 'asOfDate', 'rounding', 'compare', 'noteRefs')));
    }

    public function balanceSheetPdf(Company $company, Request $request): Response
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));
        $rounding = in_array((int) $request->input('rounding', 1), [1, 1000, 1000000]) ? (int) $request->input('rounding', 1) : 1;
        $compare = $request->boolean('compare');

        $data = $this->buildBalanceSheet($company, $asOfDate);

        if ($compare) {
            $priorAsOfDate = $request->input('compare_date') ?: Carbon::parse($asOfDate)->subYear()->format('Y-m-d');
            $priorData = $this->buildBalanceSheet($company, $priorAsOfDate);
            $data = array_merge($data, $this->mergeBalanceSheetComparatives($data, $priorData, $priorAsOfDate));
        } else {
            $data['compare'] = false;
            $data['priorAsOfDate'] = null;
        }

        $noteRefs = $this->noteRefs($company);

        $pdf = Pdf::loadView('pdf.balance-sheet', array_merge($data, compact('company', 'asOfDate', 'rounding', 'compare', 'noteRefs')))
            ->setPaper('a4', 'potrait');

        return $pdf->download($company->slug . '-balance-sheet.pdf');
    }

    /**
     * Resolve active note references for a company, keyed by stable slug, so the
     * "Note(s)" column on the statements can show a number that links to the note.
     * Seeds the standard IFRS notes on first use if none exist yet.
     *
     * @return array<string, array{n:int, id:int}>
     */
    private function noteRefs(Company $company): array
    {
        // Seed if the company has no notes at all yet.
        if (!$company->financialStatementNotes()->where('is_active', true)->exists()) {
            (new \App\Services\FinancialStatementNotesSeeder())->seed($company);
        }

        // Only notes toggled ON appear in the AFS; number them sequentially 1, 2, 3…
        // in sort_order so that gaps left by excluded notes are closed.
        $notes = $company->financialStatementNotes()
            ->where('is_active', true)
            ->where('include_in_afs', true)
            ->orderBy('sort_order')
            ->get(['id', 'slug']);

        return $notes->values()->mapWithKeys(fn ($n, $i) => [
            $n->slug => ['n' => $i + 1, 'id' => $n->id],
        ])->all();
    }

    public function editAfsDetails(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $afs = $company->afsSetting()->firstOrNew([]);

        $yearEndMonth = $company->financial_year_end_month ?? 12;
        $fyStartMonth = ($yearEndMonth % 12) + 1;
        $today = now();
        $fyStartYear  = $today->month >= $fyStartMonth ? $today->year : $today->year - 1;

        $startDate = \Carbon\Carbon::create($fyStartYear, $fyStartMonth, 1)->format('Y-m-d');
        $endDate   = $today->format('Y-m-d');

        return view('companies.afs-details', compact('company', 'afs', 'startDate', 'endDate'));
    }

    public function updateAfsDetails(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'country_of_incorporation'   => ['nullable', 'string', 'max:120'],
            'nature_of_business'         => ['nullable', 'string', 'max:255'],
            'directors'                  => ['nullable', 'string', 'max:2000'],
            'registered_office'          => ['nullable', 'string', 'max:1000'],
            'business_address'           => ['nullable', 'string', 'max:1000'],
            'postal_address'             => ['nullable', 'string', 'max:1000'],
            'practitioner_name'          => ['nullable', 'string', 'max:255'],
            'practitioner_qualification' => ['nullable', 'string', 'max:255'],
            'practitioner_membership'    => ['nullable', 'string', 'max:255'],
            'practitioner_contact'       => ['nullable', 'string', 'max:2000'],
            'compilation_directors'      => ['nullable', 'string', 'max:255'],
            'approval_date'              => ['nullable', 'date'],
            'level_of_assurance'         => ['nullable', 'string', 'max:1000'],
        ]);

        // Directors are entered one per line; store as a clean list.
        $directors = collect(preg_split('/\r\n|\r|\n/', (string) ($validated['directors'] ?? '')))
            ->map(fn ($d) => trim($d))
            ->filter()
            ->values()
            ->all();
        $validated['directors'] = $directors;

        $company->afsSetting()->updateOrCreate(['company_id' => $company->id], $validated);

        return redirect()
            ->route('companies.afs-details.edit', $company)
            ->with('success', 'AFS details saved.');
    }

    public function editCashFlowMapping(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $accounts = $company->chartOfAccounts()
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get()
            ->groupBy('account_type');

        return view('companies.cash-flow-mapping', compact('company', 'accounts'));
    }

    public function updateCashFlowMapping(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'categories'   => ['nullable', 'array'],
            'categories.*' => ['nullable', 'in:operating,investing,financing'],
        ]);

        $ids = $company->chartOfAccounts()->pluck('id')->all();
        foreach (($validated['categories'] ?? []) as $accountId => $category) {
            if (! in_array((int) $accountId, $ids, true)) {
                continue;
            }
            $company->chartOfAccounts()
                ->where('id', (int) $accountId)
                ->update(['cash_flow_category' => $category ?: null]);
        }

        return redirect()
            ->route('companies.cash-flow-mapping.edit', $company)
            ->with('success', 'Cash flow categories saved.');
    }

    /**
     * Download the full set of Annual Financial Statements as a single bound PDF
     * matching the standard IFRS-for-SMEs compiled-statements layout.
     */
    public function afsBundlePdf(Company $company, Request $request): Response
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $endDate   = $request->input('end_date', $this->fyEndDate($company));
        $startDate = $request->input('start_date', $this->fyStartDate($company));
        // AFS are always presented with a prior-year comparative.
        $compare = true;
        $rounding = 1;

        $priorEnd = Carbon::parse($endDate)->subYear()->format('Y-m-d');
        $priorStart = Carbon::parse($startDate)->subYear()->format('Y-m-d');

        // ── Statement of Financial Position ──────────────────────────────
        $asOfDate = $endDate;
        $priorAsOfDate = $priorEnd;
        $bs = $this->buildBalanceSheet($company, $asOfDate);
        $bsPrior = $this->buildBalanceSheet($company, $priorAsOfDate);
        $bs = array_merge($bs, $this->mergeBalanceSheetComparatives($bs, $bsPrior, $priorAsOfDate));

        // ── Profit or loss accounts (drives SOCI, detailed I/S, cash flow) ─
        $pl = $this->profitOrLossAccounts($company, $startDate, $endDate, $priorStart, $priorEnd);
        $incomeAccounts = $pl->where('account_type', 'income')->values();
        $expenseAccounts = $pl->where('account_type', 'expenses')->values();

        $totalRevenue = (float) $incomeAccounts->sum('net_amount');
        $totalExpenses = (float) $expenseAccounts->sum('net_amount');
        $netIncome = $totalRevenue - $totalExpenses;
        $totalRevenuePrior = (float) $incomeAccounts->sum('prior_net_amount');
        $totalExpensesPrior = (float) $expenseAccounts->sum('prior_net_amount');
        $netIncomePrior = $totalRevenuePrior - $totalExpensesPrior;

        // Direct-method cash flow (manual entries).
        $cf = $this->manualCashFlowViewData($company, $startDate, $endDate, true);

        // ── Statement of Changes in Equity figures ───────────────────────
        $cumPl      = $this->cumulativeNetIncome($company, $endDate);
        $cumPlPrior = $this->cumulativeNetIncome($company, $priorEnd);
        $equityMovement = $this->buildEquityMovement($company, $bs, $netIncome, $netIncomePrior, $cumPl, $cumPlPrior);

        // ── Notes ────────────────────────────────────────────────────────
        $noteRefs     = $this->noteRefs($company);
        $notes        = $company->financialStatementNotes()->where('is_active', true)->where('include_in_afs', true)->orderBy('sort_order')->get();
        $afs          = $company->afsSetting()->firstOrNew([]);
        $ppeService          = app(\App\Services\PpeMovementService::class);
        $intService          = app(\App\Services\IntangibleMovementService::class);
        $ppeMovements        = $ppeService->build($company, $startDate, $endDate);
        $intangibleMovements = $intService->build($company, $startDate, $endDate);
        $inventoryMovements  = app(\App\Services\InventoryMovementService::class)->build($company, $startDate, $endDate);
        $ociAccounts         = $this->buildOciAccounts($company, $startDate, $endDate, true, $priorStart, $priorEnd);

        // Revaluation surplus for SOCE (from PPE + intangible registers)
        $ppePrior  = $ppeService->build($company, $priorStart, $priorEnd);
        $intPrior  = $intService->build($company, $priorStart, $priorEnd);
        $revalSurplus = [
            'open_prior'  => collect($ppePrior)->sum('rev_surplus_opening') + collect($intPrior)->sum('rev_surplus_opening'),
            'close_prior' => collect($ppePrior)->sum('rev_surplus_closing') + collect($intPrior)->sum('rev_surplus_closing'),
            'open_cur'    => collect($ppeMovements)->sum('rev_surplus_opening') + collect($intangibleMovements)->sum('rev_surplus_opening'),
            'close_cur'   => collect($ppeMovements)->sum('rev_surplus_closing') + collect($intangibleMovements)->sum('rev_surplus_closing'),
        ];
        $revalSurplus['movement_prior'] = round($revalSurplus['close_prior'] - $revalSurplus['open_prior'], 2);
        $revalSurplus['movement_cur']   = round($revalSurplus['close_cur']   - $revalSurplus['open_cur'], 2);

        $equityDetails      = $this->equityMovementDetails($company, $startDate, $endDate);
        $equityDetailsPrior = $this->equityMovementDetails($company, $priorStart, $priorEnd);

        // Per-note figures (accounts linked to each note), keyed by note id.
        $noteFigureService = app(\App\Services\NoteFigureService::class);
        $noteFigures = $notes->mapWithKeys(fn ($n) => [
            $n->id => $noteFigureService->figuresFor($company, $n, $startDate, $endDate),
        ])->all();

        $pdf = Pdf::loadView('pdf.afs-bundle', array_merge($bs, compact(
            'company',
            'afs',
            'startDate',
            'endDate',
            'asOfDate',
            'priorAsOfDate',
            'rounding',
            'compare',
            'noteRefs',
            'notes',
            'incomeAccounts',
            'expenseAccounts',
            'totalRevenue',
            'totalExpenses',
            'netIncome',
            'totalRevenuePrior',
            'totalExpensesPrior',
            'netIncomePrior',
            'cf',
            'equityMovement',
            'revalSurplus',
            'equityDetails',
            'equityDetailsPrior',
            'ppeMovements',
            'intangibleMovements',
            'inventoryMovements',
            'ociAccounts',
            'noteFigures',
        )))->setPaper('a4', 'potrait');

        return $pdf->download($company->slug . '-annual-financial-statements.pdf');
    }

    /**
     * Map income & expense accounts to current/prior net amounts for a period.
     */
    private function profitOrLossAccounts(Company $company, string $start, string $end, string $priorStart, string $priorEnd): \Illuminate\Support\Collection
    {
        $accountIds = $company->chartOfAccounts()->whereIn('account_type', ['income', 'expenses'])->where('is_contra', false)->where('is_oci', false)->pluck('id');
        $totals = $this->accountNetTotals($company, $accountIds, $start, $end);
        $priorTotals = $this->accountNetTotals($company, $accountIds, $priorStart, $priorEnd);

        return $company->chartOfAccounts()
            ->whereIn('account_type', ['income', 'expenses'])
            ->where('is_active', true)
            ->where('is_contra', false)
            ->where('is_oci', false)
            ->orderBy('account_code')
            ->get()
            ->map(function ($account) use ($totals, $priorTotals) {
                $row = $totals->get($account->id);
                $debits = $row ? (float) $row->total_debits : 0.0;
                $credits = $row ? (float) $row->total_credits : 0.0;
                $account->net_amount = $account->account_type === 'income' ? $credits - $debits : $debits - $credits;

                $priorRow = $priorTotals->get($account->id);
                $pd = $priorRow ? (float) $priorRow->total_debits : 0.0;
                $pc = $priorRow ? (float) $priorRow->total_credits : 0.0;
                $account->prior_net_amount = $account->account_type === 'income' ? $pc - $pd : $pd - $pc;

                return $account;
            })
            ->values();
    }

    /**
     * Sum the share-capital portion of the equity accounts (by name match).
     * $prior selects the prior-year balance.
     */
    private function equitySubset($equityAccounts, bool $shareCapital, bool $prior): float
    {
        // Use account code ranges instead of keyword matching:
        //   3001000–3001999 = Share Capital / Owner's Equity
        //   3002000+        = Retained Earnings, Reserves, and other equity
        $sum = 0.0;
        foreach ($equityAccounts as $account) {
            $rows = $account->items->isNotEmpty() ? $account->items : collect([$account]);
            foreach ($rows as $row) {
                $code    = (int) $row->account_code;
                $isShare = $code >= 3001000 && $code < 3002000;
                if ($isShare !== $shareCapital) {
                    continue;
                }
                $bal = $prior
                    ? (float) ($row->prior_balance ?? $row->prior_groupBalance ?? 0)
                    : (float) ($row->balance ?? $row->groupBalance ?? 0);
                $sum += $bal;
            }
        }
        return $sum;
    }

    /**
     * Statement of Changes in Equity movements. Splits equity into share capital
     * (codes 3001000–3001999) and retained income, and derives opening/closing balances,
     * profit and share issues for the current and prior year.
     *
     * @param  array  $bs  Result of buildBalanceSheet (merged with prior comparatives).
     * @return array<string,float>
     */
    /**
     * Cumulative net income from inception up to a date (all income − all expenses).
     */
    private function cumulativeNetIncome(Company $company, string $asOfDate): float
    {
        $plIds = $company->chartOfAccounts()
            ->whereIn('account_type', ['income', 'expenses'])
            ->where('is_contra', false)
            ->where('is_oci', false)
            ->pluck('id');

        if ($plIds->isEmpty()) {
            return 0.0;
        }

        $ctx = $company->openingContext($asOfDate);

        $rows = $ctx['start']
            ? $this->accountNetTotals($company, $plIds, $ctx['start'], $asOfDate)
            : $this->accountNetTotalsCumulative($company, $plIds, $asOfDate);

        $accounts = $company->chartOfAccounts()->whereIn('id', $plIds)->get()->keyBy('id');
        $total = 0.0;

        foreach ($rows as $accountId => $row) {
            $account = $accounts->get($accountId);
            if (! $account) continue;
            $debits  = (float) $row->total_debits;
            $credits = (float) $row->total_credits;
            $opening = $ctx['amounts'] !== null
                ? (float) ($ctx['amounts'][$accountId] ?? 0)
                : (float) $account->opening_balance;

            if ($account->account_type === 'income') {
                $total += $opening + ($credits - $debits);
            } else {
                $total -= $opening + ($debits - $credits);
            }
        }

        return $total;
    }

    private function buildEquityMovement(Company $company, array $bs, float $netIncome, float $netIncomePrior, float $cumulativeNetIncome = 0.0, float $cumulativeNetIncomePrior = 0.0): array
    {
        $shareCapitalNow   = $this->equitySubset($bs['equityAccounts'], true, false);
        $shareCapitalPrior = $this->equitySubset($bs['equityAccounts'], true, true);

        // GL equity balances exclude undistributed P&L (income/expense accounts
        // aren't closed to retained earnings until a year-end journal is posted).
        // Add cumulative net income so the SOCE reflects the true retained earnings.
        $retainedGlNow   = $this->equitySubset($bs['equityAccounts'], false, false);
        $retainedGlPrior = $this->equitySubset($bs['equityAccounts'], false, true);

        $retainedNow   = $retainedGlNow   + $cumulativeNetIncome;
        $retainedPrior = $retainedGlPrior  + $cumulativeNetIncomePrior;

        return [
            'share_open_prior'    => $shareCapitalPrior,
            'retained_open_prior' => $retainedPrior - $netIncomePrior,
            'profit_prior'        => $netIncomePrior,
            'share_open'          => $shareCapitalPrior,
            'retained_open'       => $retainedPrior,
            'profit_current'      => $netIncome,
            'share_issue'         => $shareCapitalNow - $shareCapitalPrior,
            'share_close'         => $shareCapitalNow,
            'retained_close'      => $retainedNow,
        ];
    }

    /**
     * Analyse journal entries hitting equity accounts (retained earnings, reserves)
     * during a period and break them into named line items for the SOCE.
     *
     * Returns an array of [{name, amount}] for movements that are NOT already
     * captured by profit/loss, OCI, revaluation surplus, or share issues.
     */
    private function equityMovementDetails(Company $company, string $start, string $end): array
    {
        $equityAccountIds = $company->chartOfAccounts()
            ->where('account_type', 'equity')
            ->where('is_oci', false)
            ->get();

        // Share capital accounts (3001000-3001999) are handled separately
        $retainedIds = $equityAccountIds->filter(fn ($a) => (int) $a->account_code >= 3002000)->pluck('id')->all();

        if (empty($retainedIds)) {
            return [];
        }

        // Get all journal lines hitting retained/reserve equity accounts in the period
        $lines = \App\Models\JournalLine::whereIn('chart_of_account_id', $retainedIds)
            ->whereHas('transaction', fn ($q) => $q
                ->where('company_id', $company->id)
                ->whereBetween('date', [$start, $end])
            )
            ->with(['transaction:id,description,reference', 'chartOfAccount:id,account_name,account_code'])
            ->get();

        if ($lines->isEmpty()) {
            return [];
        }

        // Classify each line by its nature using account name and transaction description
        $categories = [
            'dividends'      => 0.0,
            'prior_period'   => 0.0,
            'transfers'      => 0.0,
            'contributions'  => 0.0,
            'other'          => 0.0,
        ];

        foreach ($lines as $line) {
            $amount = (float) $line->credit - (float) $line->debit;
            $accName  = strtolower($line->chartOfAccount->account_name ?? '');
            $txnDesc  = strtolower($line->transaction->description ?? '');
            $combined = $accName . ' ' . $txnDesc;

            if (str_contains($combined, 'dividend')) {
                $categories['dividends'] += $amount;
            } elseif (str_contains($combined, 'prior period') || str_contains($combined, 'error correction') || str_contains($combined, 'ias 8') || str_contains($combined, 'restatement')) {
                $categories['prior_period'] += $amount;
            } elseif (str_contains($combined, 'transfer') || str_contains($combined, 'reserve')) {
                $categories['transfers'] += $amount;
            } elseif (str_contains($combined, 'contribution') || str_contains($combined, 'capital') || str_contains($combined, 'owner')) {
                $categories['contributions'] += $amount;
            } else {
                $categories['other'] += $amount;
            }
        }

        $result = [];
        $labels = [
            'dividends'     => 'Dividends declared',
            'prior_period'  => 'Prior period error adjustment',
            'transfers'     => 'Transfers to/from reserves',
            'contributions' => 'Owner contributions',
            'other'         => 'Other equity movements',
        ];

        foreach ($categories as $key => $amount) {
            if (round($amount, 2) != 0) {
                $result[] = ['name' => $labels[$key], 'amount' => round($amount, 2)];
            }
        }

        return $result;
    }

    /**
     * Assemble the SOCE view data (equity movement + OCI) for a period, always
     * with a prior-year comparative.
     *
     * @return array<string,mixed>
     */
    private function changesInEquityData(Company $company, string $startDate, string $endDate): array
    {
        $priorStart = Carbon::parse($startDate)->subYear()->format('Y-m-d');
        $priorEnd   = Carbon::parse($endDate)->subYear()->format('Y-m-d');

        $bs      = $this->buildBalanceSheet($company, $endDate);
        $bsPrior = $this->buildBalanceSheet($company, $priorEnd);
        $bs      = array_merge($bs, $this->mergeBalanceSheetComparatives($bs, $bsPrior, $priorEnd));

        $pl = $this->profitOrLossAccounts($company, $startDate, $endDate, $priorStart, $priorEnd);
        $netIncome      = (float) $pl->where('account_type', 'income')->sum('net_amount')
            - (float) $pl->where('account_type', 'expenses')->sum('net_amount');
        $netIncomePrior = (float) $pl->where('account_type', 'income')->sum('prior_net_amount')
            - (float) $pl->where('account_type', 'expenses')->sum('prior_net_amount');

        $cumPl      = $this->cumulativeNetIncome($company, $endDate);
        $cumPlPrior = $this->cumulativeNetIncome($company, $priorEnd);
        $equityMovement = $this->buildEquityMovement($company, $bs, $netIncome, $netIncomePrior, $cumPl, $cumPlPrior);
        $ociAccounts    = $this->buildOciAccounts($company, $startDate, $endDate, true, $priorStart, $priorEnd);

        // Revaluation surplus from PPE (IAS 16) and intangible (IAS 38) registers
        $ppeService = app(\App\Services\PpeMovementService::class);
        $intService = app(\App\Services\IntangibleMovementService::class);

        $ppeMov    = $ppeService->build($company, $startDate, $endDate);
        $intMov    = $intService->build($company, $startDate, $endDate);
        $ppePrior  = $ppeService->build($company, $priorStart, $priorEnd);
        $intPrior  = $intService->build($company, $priorStart, $priorEnd);

        $revalSurplus = [
            'open_prior'  => collect($ppePrior)->sum('rev_surplus_opening') + collect($intPrior)->sum('rev_surplus_opening'),
            'close_prior' => collect($ppePrior)->sum('rev_surplus_closing') + collect($intPrior)->sum('rev_surplus_closing'),
            'open_cur'    => collect($ppeMov)->sum('rev_surplus_opening')   + collect($intMov)->sum('rev_surplus_opening'),
            'close_cur'   => collect($ppeMov)->sum('rev_surplus_closing')   + collect($intMov)->sum('rev_surplus_closing'),
        ];
        $revalSurplus['movement_prior'] = round($revalSurplus['close_prior'] - $revalSurplus['open_prior'], 2);
        $revalSurplus['movement_cur']   = round($revalSurplus['close_cur']   - $revalSurplus['open_cur'], 2);

        $equityDetails      = $this->equityMovementDetails($company, $startDate, $endDate);
        $equityDetailsPrior = $this->equityMovementDetails($company, $priorStart, $priorEnd);

        return compact('equityMovement', 'ociAccounts', 'revalSurplus', 'equityDetails', 'equityDetailsPrior');
    }

    public function changesInEquity(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $startDate = $request->input('start_date', $this->fyStartDate($company));
        $endDate   = $request->input('end_date', $this->fyEndDate($company));
        $rounding  = in_array((int) $request->input('rounding', 1), [1, 1000, 1000000]) ? (int) $request->input('rounding', 1) : 1;

        $data = $this->changesInEquityData($company, $startDate, $endDate);

        return view('companies.reports.changes-in-equity', array_merge($data, compact('company', 'startDate', 'endDate', 'rounding')));
    }

    public function changesInEquityPdf(Company $company, Request $request): Response
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $startDate = $request->input('start_date', $this->fyStartDate($company));
        $endDate   = $request->input('end_date', $this->fyEndDate($company));
        $rounding  = in_array((int) $request->input('rounding', 1), [1, 1000, 1000000]) ? (int) $request->input('rounding', 1) : 1;

        $data = $this->changesInEquityData($company, $startDate, $endDate);

        $pdf = Pdf::loadView('pdf.changes-in-equity', array_merge($data, compact('company', 'startDate', 'endDate', 'rounding')))
            ->setPaper('a4', 'potrait');

        return $pdf->download($company->slug . '-changes-in-equity.pdf');
    }

    private function buildOciAccounts(Company $company, string $start, string $end, bool $compare, string $priorStart = '', string $priorEnd = ''): \Illuminate\Support\Collection
    {
        $ids = $company->chartOfAccounts()
            ->where('is_oci', true)
            ->where('is_active', true)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return collect();
        }

        $totals      = $this->accountNetTotals($company, $ids, $start, $end);
        $priorTotals = ($compare && $priorStart)
            ? $this->accountNetTotals($company, $ids, $priorStart, $priorEnd)
            : collect();

        return $company->chartOfAccounts()
            ->whereIn('id', $ids->all())
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get()
            ->map(function ($account) use ($totals, $priorTotals, $compare) {
                $row = $totals->get($account->id);
                // Credits minus debits — positive = gain, negative = loss
                $account->oci_net = $row
                    ? (float) $row->total_credits - (float) $row->total_debits
                    : 0.0;
                if ($compare) {
                    $pr = $priorTotals->get($account->id);
                    $account->oci_net_prior = $pr
                        ? (float) $pr->total_credits - (float) $pr->total_debits
                        : 0.0;
                }
                return $account;
            });
    }

    public function addOciAccount(Company $company, Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        $validated = $request->validate(['account_id' => ['required', 'integer', 'exists:chart_of_accounts,id']]);
        $afs = $company->afsSetting()->firstOrCreate(['company_id' => $company->id]);
        $ids = array_values(array_unique(array_merge($afs->oci_account_ids ?? [], [(int) $validated['account_id']])));
        $afs->update(['oci_account_ids' => $ids]);
        return back()->with('success', 'Account added to Other Comprehensive Income.');
    }

    public function removeOciAccount(Company $company, int $accountId): \Illuminate\Http\RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        $afs = $company->afsSetting()->firstOrNew([]);
        $ids = array_values(array_filter($afs->oci_account_ids ?? [], fn($id) => (int) $id !== $accountId));
        $afs->fill(['oci_account_ids' => $ids])->save();
        return back()->with('success', 'Account removed from Other Comprehensive Income.');
    }

    public function generalLedger(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $startDate = $request->input('start_date', $this->fyStartDate($company));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $rounding = in_array((int) $request->input('rounding', 1), [1, 1000, 1000000]) ? (int) $request->input('rounding', 1) : 1;

        $ledger = $this->buildGeneralLedger($company, $startDate, $endDate);

        return view('companies.reports.general-ledger', compact(
            'company',
            'ledger',
            'startDate',
            'endDate',
            'rounding',
        ));
    }

    public function generalLedgerPdf(Company $company, Request $request): Response
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $startDate = $request->input('start_date', $this->fyStartDate($company));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $rounding = in_array((int) $request->input('rounding', 1), [1, 1000, 1000000]) ? (int) $request->input('rounding', 1) : 1;

        $ledger = $this->buildGeneralLedger($company, $startDate, $endDate);

        $pdf = Pdf::loadView('pdf.general-ledger', compact(
            'company',
            'ledger',
            'startDate',
            'endDate',
            'rounding',
        ))->setPaper('a4', 'portrait');

        return $pdf->download($company->slug . '-general-ledger.pdf');
    }

    public function ageAnalysis(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));

        $analyses = (new \App\Services\AgeAnalysisService())->generate($company, $asOfDate);
        $rates = \App\Models\EclRateSetting::forCompany($company);

        $totals = [
            'current_amount' => $analyses->sum('current_amount'),
            'days_31_60' => $analyses->sum('days_31_60'),
            'days_61_90' => $analyses->sum('days_61_90'),
            'days_91_plus' => $analyses->sum('days_91_plus'),
            'total_outstanding' => $analyses->sum('total_outstanding'),
            'ecl_current' => $analyses->sum('ecl_current'),
            'ecl_31_60' => $analyses->sum('ecl_31_60'),
            'ecl_61_90' => $analyses->sum('ecl_61_90'),
            'ecl_91_plus' => $analyses->sum('ecl_91_plus'),
            'total_ecl' => $analyses->sum('total_ecl'),
        ];

        return view('companies.reports.age-analysis', compact('company', 'analyses', 'rates', 'totals', 'asOfDate'));
    }

    public function updateEclRates(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'current_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'days_31_60_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'days_61_90_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'days_91_plus_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'as_of_date' => ['nullable', 'date'],
        ]);

        // Rates are submitted as percentages (e.g. 1.5 for 1.5%); store as fractions.
        $rates = \Illuminate\Support\Arr::except($validated, ['as_of_date']);
        $rates = array_map(fn ($rate) => round($rate / 100, 4), $rates);

        \App\Models\EclRateSetting::updateOrCreate(
            ['company_id' => $company->id],
            $rates,
        );

        return redirect()
            ->route('companies.reports.age-analysis', [$company, 'as_of_date' => $validated['as_of_date'] ?? now()->format('Y-m-d')])
            ->with('success', 'ECL provision matrix updated.');
    }

    public function trialBalance(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $startDate = $request->input('start_date', $this->fyStartDate($company));
        $endDate   = $request->input('end_date', now()->toDateString());
        $rounding  = in_array((int) $request->input('rounding', 1), [1, 1000, 1000000])
            ? (int) $request->input('rounding', 1)
            : 1;
        $accounts  = $this->loadTrialBalanceAccounts($company, $startDate, $endDate);

        return view('companies.reports.trial-balance', compact('company', 'accounts', 'startDate', 'endDate', 'rounding'));
    }

    public function trialBalancePdf(Company $company, Request $request): Response
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $startDate = $request->input('start_date', $this->fyStartDate($company));
        $endDate   = $request->input('end_date', now()->toDateString());
        $rounding  = in_array((int) $request->input('rounding', 1), [1, 1000, 1000000])
            ? (int) $request->input('rounding', 1)
            : 1;
        $accounts  = $this->loadTrialBalanceAccounts($company, $startDate, $endDate);

        $pdf = Pdf::loadView('pdf.trial-balance', compact('company', 'accounts', 'startDate', 'endDate', 'rounding'))
            ->setPaper('a4', 'portrait');

        return $pdf->download($company->slug . '-trial-balance.pdf');
    }

    public function exportRegisters(Company $company, Request $request)
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $asOfDate = $request->input('as_of_date', now()->toDateString());
        $register = $request->input('register', 'all');

        $sheets = [];

        if ($register === 'all' || $register === 'ppe') {
            $assets = $company->assets()->with('ppeClass')->whereNull('disposal_date')->orderBy('name')->get();
            $sheets[] = new RegisterExport('ppe', $assets, $company->registered_name, $asOfDate);
        }

        if ($register === 'all' || $register === 'intangible') {
            $intangibles = $company->intangibleAssets()->with('intangibleClass')->whereNull('disposal_date')->orderBy('name')->get();
            $sheets[] = new RegisterExport('intangible', $intangibles, $company->registered_name, $asOfDate);
        }

        if ($register === 'all' || $register === 'investment_property') {
            $ips = $company->investmentProperties()->with('investmentPropertyClass')->whereNull('disposal_date')->orderBy('name')->get();
            $sheets[] = new RegisterExport('investment_property', $ips, $company->registered_name, $asOfDate);
        }

        if ($register === 'all' || $register === 'held_for_sale') {
            $hfs = $company->assetsHeldForSale()->with('asset')->where('status', '!=', 'disposed')->orderBy('id')->get();
            $sheets[] = new RegisterExport('held_for_sale', $hfs, $company->registered_name, $asOfDate);
        }

        if ($register === 'all' || $register === 'biological') {
            $bio = $company->biologicalAssets()->with('biologicalAssetClass')->whereNull('disposal_date')->orderBy('name')->get();
            $sheets[] = new RegisterExport('biological', $bio, $company->registered_name, $asOfDate);
        }

        if ($register === 'all' || $register === 'lease') {
            $leases = $company->leases()->where('status', '!=', 'terminated')->orderBy('name')->get();
            $sheets[] = new RegisterExport('lease', $leases, $company->registered_name, $asOfDate);
        }

        if ($register === 'all' || $register === 'ecl') {
            $analyses = (new \App\Services\AgeAnalysisService)->generate($company, $asOfDate);
            $sheets[] = new RegisterExport('ecl', $analyses, $company->registered_name, $asOfDate);
        }

        $filename = $register === 'all'
            ? $company->slug . '-registers-' . $asOfDate . '.xlsx'
            : $company->slug . '-' . str_replace('_', '-', $register) . '-register-' . $asOfDate . '.xlsx';

        return Excel::download(new AllRegistersExport($sheets), $filename);
    }

    public function trialBalanceExcel(Company $company, Request $request)
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $startDate = $request->input('start_date', $this->fyStartDate($company));
        $endDate   = $request->input('end_date', now()->toDateString());
        $rounding  = in_array((int) $request->input('rounding', 1), [1, 1000, 1000000])
            ? (int) $request->input('rounding', 1)
            : 1;
        $accounts  = $this->loadTrialBalanceAccounts($company, $startDate, $endDate);

        $periodLabel = Carbon::parse($startDate)->format('d M Y') . ' – ' . Carbon::parse($endDate)->format('d M Y');

        return Excel::download(
            new TrialBalanceExport($accounts, $company->registered_name, $periodLabel, $rounding),
            $company->slug . '-trial-balance.xlsx',
        );
    }

    public function trialBalanceImport(Company $company, Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $import = new \App\Imports\TrialBalanceImport($company);

        \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

        $message = "Import complete — {$import->created} accounts created, {$import->updated} opening balances updated.";
        if ($import->skipped > 0) {
            $message .= " {$import->skipped} rows skipped.";
        }

        $session = $import->errors
            ? redirect()->back()->with('warning', $message)->with('import_errors', $import->errors)
            : redirect()->back()->with('success', $message);

        return $session;
    }

    /**
     * Load chart of accounts with aggregated posted debit/credit totals within the given date range.
     */
    private function loadTrialBalanceAccounts(Company $company, string $startDate, string $endDate): Collection
    {
        $debitSub = DB::table('journal_lines')
            ->join('transactions', 'transactions.id', '=', 'journal_lines.transaction_id')
            ->whereColumn('journal_lines.chart_of_account_id', 'chart_of_accounts.id')
            ->where('journal_lines.type', 'debit')
            ->whereIn('transactions.status', ['posted', 'reversed'])
            ->whereDate('transactions.transaction_date', '>=', $startDate)
            ->whereDate('transactions.transaction_date', '<=', $endDate)
            ->selectRaw('COALESCE(SUM(journal_lines.amount), 0)');

        $creditSub = DB::table('journal_lines')
            ->join('transactions', 'transactions.id', '=', 'journal_lines.transaction_id')
            ->whereColumn('journal_lines.chart_of_account_id', 'chart_of_accounts.id')
            ->where('journal_lines.type', 'credit')
            ->whereIn('transactions.status', ['posted', 'reversed'])
            ->whereDate('transactions.transaction_date', '>=', $startDate)
            ->whereDate('transactions.transaction_date', '<=', $endDate)
            ->selectRaw('COALESCE(SUM(journal_lines.amount), 0)');

        $accounts = $company->chartOfAccounts()
            ->select('chart_of_accounts.*')
            ->selectSub($debitSub, 'posted_debits')
            ->selectSub($creditSub, 'posted_credits')
            ->orderBy('account_code')
            ->get();

        // Period-aware opening: the trial-balance views render
        // opening_balance + movements within [startDate, endDate]. Override
        // opening_balance with the balance as at $startDate for the relevant
        // period (period opening + any movements earlier in the period), so the
        // opening column reflects the period rather than the legacy single column.
        $ctx = $company->openingContext($startDate);
        if ($ctx['amounts'] !== null) {
            $dayBefore = Carbon::parse($startDate)->subDay()->format('Y-m-d');
            $pre = $this->accountNetTotals($company, $accounts->pluck('id'), $ctx['start'], $dayBefore);

            foreach ($accounts as $account) {
                $row = $pre->get($account->id);
                $d = $row ? (float) $row->total_debits : 0.0;
                $c = $row ? (float) $row->total_credits : 0.0;
                $signedPre = in_array($account->account_type, ['assets', 'expenses'], true)
                    ? ($d - $c)
                    : ($c - $d);
                $account->opening_balance = (float) ($ctx['amounts'][$account->id] ?? 0) + $signedPre;
            }
        }

        return $accounts;
    }

    public function vatReturn(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless(! empty($company->vat_number), 404);

        $defaultStart = $this->fyStartDate($company);
        $defaultEnd = now()->format('Y-m-d');

        $startDate = $request->input('start_date', $defaultStart);
        $endDate = $request->input('end_date', $defaultEnd);

        // All VAT-tagged journal lines in the period on posted transactions
        $vatLines = $company->chartOfAccounts()
            ->with(['journalLines' => function ($q) use ($startDate, $endDate) {
                $q->where('is_vat_line', true)
                    ->whereHas('transaction', fn($t) => $t
                        ->whereIn('status', ['posted', 'reversed'])
                        ->whereBetween('transaction_date', [$startDate, $endDate]))
                    ->with(['transaction:id,transaction_date,description,reference']);
            }])
            ->get()
            ->flatMap(fn($account) => $account->journalLines->map(fn($line) => [
                'date' => $line->transaction->transaction_date,
                'description' => $line->transaction->description,
                'reference' => $line->transaction->reference,
                'type' => $line->type,       // debit = input VAT, credit = output VAT
                'amount' => (float) $line->amount,
                'vat_rate' => $line->vat_rate,
                'account' => $account->account_name,
            ]))
            ->sortBy('date')
            ->values();

        // Output VAT = credits (VAT collected from customers, owed to SARS)
        $outputVatLines = $vatLines->where('type', 'credit');
        $totalOutputVat = $outputVatLines->sum('amount');

        // Input VAT = debits (VAT paid on purchases, recoverable from SARS)
        $inputVatLines = $vatLines->where('type', 'debit');
        $totalInputVat = $inputVatLines->sum('amount');

        // Net VAT payable (positive = owed to SARS, negative = refund due)
        $netVatPayable = $totalOutputVat - $totalInputVat;

        return view('companies.reports.vat-return', compact(
            'company',
            'startDate',
            'endDate',
            'outputVatLines',
            'inputVatLines',
            'totalOutputVat',
            'totalInputVat',
            'netVatPayable',
        ));
    }

    public function vatReturnPdf(Company $company, Request $request): Response
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless(! empty($company->vat_number), 404);

        $startDate = $request->input('start_date', $this->fyStartDate($company));
        $endDate   = $request->input('end_date', now()->toDateString());

        $vatLines = $company->chartOfAccounts()
            ->with(['journalLines' => function ($q) use ($startDate, $endDate) {
                $q->where('is_vat_line', true)
                    ->whereHas('transaction', fn($t) => $t
                        ->whereIn('status', ['posted', 'reversed'])
                        ->whereBetween('transaction_date', [$startDate, $endDate]))
                    ->with(['transaction:id,transaction_date,description,reference']);
            }])
            ->get()
            ->flatMap(fn($account) => $account->journalLines->map(fn($line) => [
                'date'        => $line->transaction->transaction_date,
                'description' => $line->transaction->description,
                'reference'   => $line->transaction->reference,
                'type'        => $line->type,
                'amount'      => (float) $line->amount,
                'vat_rate'    => $line->vat_rate,
                'account'     => $account->account_name,
            ]))
            ->sortBy('date')
            ->values();

        $outputVatLines = $vatLines->where('type', 'credit');
        $totalOutputVat = $outputVatLines->sum('amount');
        $inputVatLines  = $vatLines->where('type', 'debit');
        $totalInputVat  = $inputVatLines->sum('amount');
        $netVatPayable  = $totalOutputVat - $totalInputVat;

        $pdf = Pdf::loadView('pdf.vat-return', compact(
            'company', 'startDate', 'endDate',
            'outputVatLines', 'inputVatLines',
            'totalOutputVat', 'totalInputVat', 'netVatPayable',
        ))->setPaper('a4', 'portrait');

        return $pdf->download($company->slug . '-vat201-' . $startDate . '.pdf');
    }

    private function fyStartDate(Company $company): string
    {
        $yearEndMonth = $company->financial_year_end_month ?? 12;
        $today = now();
        $fyStartMonth = ($yearEndMonth % 12) + 1;
        $fyStartYear = $today->month >= $fyStartMonth ? $today->year : $today->year - 1;

        return Carbon::create($fyStartYear, $fyStartMonth, 1)->format('Y-m-d');
    }

    private function fyEndDate(Company $company): string
    {
        return Carbon::parse($this->fyStartDate($company))->addYear()->subDay()->format('Y-m-d');
    }

    /**
     * Build a structured balance sheet split into Current / Non-Current sections
     * with parent → items hierarchy resolved.
     *
     * @return array{
     *   currentAssets: Collection,
     *   nonCurrentAssets: Collection,
     *   currentLiabilities: Collection,
     *   nonCurrentLiabilities: Collection,
     *   equityAccounts: Collection,
     *   totalCurrentAssets: float,
     *   totalNonCurrentAssets: float,
     *   totalAssets: float,
     *   totalCurrentLiabilities: float,
     *   totalNonCurrentLiabilities: float,
     *   totalLiabilities: float,
     *   totalEquity: float,
     * }
     */
    /**
     * Closing carrying amount (cost − accumulated depreciation) of the fixed asset
     * register as at a date: assets acquired on or before the date and not yet
     * disposed of by that date.
     */
    private function assetRegisterCarrying(Company $company, string $asOfDate): float
    {
        return (float) $company->assets()->get()
            ->filter(fn ($a) => $a->acquisition_date->toDateString() <= $asOfDate
                && (! $a->isDisposed() || $a->disposal_date->toDateString() > $asOfDate))
            ->sum(fn ($a) => $a->netBookValue($asOfDate));
    }

    private function intangibleRegisterCarrying(Company $company, string $asOfDate): float
    {
        return (float) $company->intangibleAssets()->get()
            ->filter(fn ($a) => $a->acquisition_date->toDateString() <= $asOfDate
                && (! $a->isDisposed() || $a->disposal_date->toDateString() > $asOfDate))
            ->sum(fn ($a) => $a->netBookValue($asOfDate));
    }

    private function inventoryRegisterCarrying(Company $company): float
    {
        return (float) $company->inventoryItems()
            ->where('is_service', false)
            ->get()
            ->sum(fn ($i) => $i->carryingAmount());
    }

    private function investmentPropertyRegisterCarrying(Company $company, string $asOfDate): float
    {
        return (float) $company->investmentProperties()->get()
            ->filter(fn ($ip) => $ip->acquisition_date->toDateString() <= $asOfDate
                && (! $ip->isDisposed() || $ip->disposal_date->toDateString() > $asOfDate))
            ->sum(fn ($ip) => $ip->carryingAmount($asOfDate));
    }

    private function biologicalAssetRegisterCarrying(Company $company, string $asOfDate): float
    {
        return (float) $company->biologicalAssets()->get()
            ->filter(fn ($ba) => $ba->acquisition_date->toDateString() <= $asOfDate
                && (! $ba->isDisposed() || $ba->disposal_date->toDateString() > $asOfDate))
            ->sum(fn ($ba) => $ba->carryingAmount());
    }

    private function leaseRouRegisterCarrying(Company $company, string $asOfDate): float
    {
        return (float) $company->leases()->get()
            ->filter(fn ($l) => ! $l->isExempt()
                && $l->commencement_date->toDateString() <= $asOfDate
                && (! $l->isTerminated() || $l->termination_date->toDateString() > $asOfDate))
            ->sum(fn ($l) => $l->rouNetBookValue($asOfDate));
    }

    private function leaseLiabilityRegisterCarrying(Company $company, string $asOfDate): float
    {
        return (float) $company->leases()->get()
            ->filter(fn ($l) => ! $l->isExempt()
                && $l->commencement_date->toDateString() <= $asOfDate
                && (! $l->isTerminated() || $l->termination_date->toDateString() > $asOfDate))
            ->sum(fn ($l) => $l->leaseLiabilityBalance($asOfDate));
    }

    private function buildBalanceSheet(Company $company, string $asOfDate): array
    {
        $accountIds = $company->chartOfAccounts()
            ->whereIn('account_type', ['assets', 'liabilities', 'equity'])
            ->where('is_contra', false)
            ->pluck('id');

        // Period-aware opening balances: sum only the movements within the period
        // that contains $asOfDate, on top of that period's opening balances. Falls
        // back to legacy cumulative + opening_balance column when no periods exist.
        $ctx = $company->openingContext($asOfDate);
        $totals = $ctx['start']
            ? $this->accountNetTotals($company, $accountIds, $ctx['start'], $asOfDate)
            : $this->accountNetTotalsCumulative($company, $accountIds, $asOfDate);

        $all = $company->chartOfAccounts()
            ->whereIn('account_type', ['assets', 'liabilities', 'equity'])
            ->where('is_active', true)
            ->where('is_contra', false)
            ->orderBy('account_code')
            ->get()
            ->map(function ($account) use ($totals, $ctx) {
                $row = $totals->get($account->id);
                $debits = $row ? (float) $row->total_debits : 0.0;
                $credits = $row ? (float) $row->total_credits : 0.0;
                $opening = $ctx['amounts'] !== null
                    ? (float) ($ctx['amounts'][$account->id] ?? 0)
                    : (float) $account->opening_balance;

                $account->balance = $account->account_type === 'assets'
                    ? $opening + ($debits - $credits)
                    : $opening + ($credits - $debits);

                return $account;
            });

        // Group children by their parent_id
        $childrenByParent = $all->whereNotNull('parent_id')->groupBy('parent_id');

        // Top-level accounts carry their children as an 'items' collection
        // and expose a 'groupBalance' = own balance + sum of children balances
        $topLevel = $all->whereNull('parent_id')->map(function ($account) use ($childrenByParent) {
            $items = $childrenByParent->get($account->id, collect());
            $account->items = $items;
            $account->groupBalance = $account->balance + $items->sum('balance');

            return $account;
        });

        // Classify by account code ranges (matching ChartOfAccountsAgent conventions).
        // Accounts flagged is_ppe or is_intangible are always non-current.
        $isNonCurrentAsset = fn($a) => (int) $a->account_code >= 1006000 || $a->is_ppe || $a->is_intangible || $a->is_investment_property || $a->is_biological_asset || $a->is_lease_asset;
        $currentAssets = $topLevel->where('account_type', 'assets')
            ->reject($isNonCurrentAsset)->values();
        $nonCurrentAssets = $topLevel->where('account_type', 'assets')
            ->filter($isNonCurrentAsset)->values();

        $currentLiabilities = $topLevel->where('account_type', 'liabilities')
            ->filter(fn($a) => (int) $a->account_code < 2006000)->values();
        $nonCurrentLiabilities = $topLevel->where('account_type', 'liabilities')
            ->filter(fn($a) => (int) $a->account_code >= 2006000)->values();

        $equityAccounts = $topLevel->where('account_type', 'equity')->values();

        $totalCurrentAssets = $currentAssets->sum('groupBalance');
        $totalNonCurrentAssets = $nonCurrentAssets->sum('groupBalance');
        $totalAssets = $totalCurrentAssets + $totalNonCurrentAssets;
        $totalCurrentLiabilities = $currentLiabilities->sum('groupBalance');
        $totalNonCurrentLiabilities = $nonCurrentLiabilities->sum('groupBalance');
        $totalLiabilities = $totalCurrentLiabilities + $totalNonCurrentLiabilities;
        $totalEquity = $equityAccounts->sum('groupBalance');

        // --- PPE from the fixed asset register ---
        // The Property, Plant & Equipment line is sourced from the asset register's
        // closing carrying amount (cost − accumulated depreciation) as at the report
        // date. Any chart-of-accounts account flagged as a PPE control account
        // (is_ppe) is suppressed from the normal non-current-asset rendering and its
        // ledger balance removed from the totals, so the single register-based PPE
        // line replaces it (avoiding double counting).
        $ppeAccountIds = $all->where('account_type', 'assets')
            ->where('is_ppe', true)
            ->pluck('id')
            ->all();
        $ppeGlBalance = (float) $all->whereIn('id', $ppeAccountIds)->sum('balance');
        $registerCarrying = $this->assetRegisterCarrying($company, $asOfDate);

        $ppeLinkedAccountIds = $ppeAccountIds;
        $ppeCarrying = (! empty($ppeAccountIds) || abs($registerCarrying) > 0.0001)
            ? [['name' => 'Property, Plant and Equipment', 'carrying' => $registerCarrying]]
            : [];

        // Swap the ledger PPE balance for the register carrying amount in the totals.
        $totalNonCurrentAssets = $totalNonCurrentAssets - $ppeGlBalance + $registerCarrying;

        // --- Intangible assets from the intangible register (IAS 38) ---
        $intangibleAccountIds = $all->where('account_type', 'assets')
            ->where('is_intangible', true)
            ->pluck('id')
            ->all();
        $intangibleGlBalance = (float) $all->whereIn('id', $intangibleAccountIds)->sum('balance');
        $intangibleRegisterCarrying = $this->intangibleRegisterCarrying($company, $asOfDate);

        $intangibleLinkedAccountIds = $intangibleAccountIds;
        $intangibleCarrying = (! empty($intangibleAccountIds) || abs($intangibleRegisterCarrying) > 0.0001)
            ? [['name' => 'Intangible Assets', 'carrying' => $intangibleRegisterCarrying]]
            : [];

        // Swap the ledger intangible balance for the register carrying amount.
        $totalNonCurrentAssets = $totalNonCurrentAssets - $intangibleGlBalance + $intangibleRegisterCarrying;

        // --- Inventory from the inventory register (IAS 2) ---
        $inventoryAccountIds = $all->where('account_type', 'assets')
            ->where('is_inventory', true)
            ->pluck('id')
            ->all();
        $inventoryGlBalance = (float) $all->whereIn('id', $inventoryAccountIds)->sum('balance');
        $inventoryRegisterCarrying = $this->inventoryRegisterCarrying($company);

        $inventoryLinkedAccountIds = $inventoryAccountIds;
        $inventoryCarrying = (! empty($inventoryAccountIds) || abs($inventoryRegisterCarrying) > 0.0001)
            ? [['name' => 'Inventories', 'carrying' => $inventoryRegisterCarrying]]
            : [];

        // Swap the ledger inventory balance for the register carrying amount.
        $totalCurrentAssets = $totalCurrentAssets - $inventoryGlBalance + $inventoryRegisterCarrying;

        // --- Investment properties from the register (IAS 40) ---
        $ipAccountIds = $all->where('account_type', 'assets')
            ->where('is_investment_property', true)
            ->pluck('id')->all();
        $ipGlBalance = (float) $all->whereIn('id', $ipAccountIds)->sum('balance');
        $ipRegisterCarrying = $this->investmentPropertyRegisterCarrying($company, $asOfDate);
        $ipLinkedAccountIds = $ipAccountIds;
        $ipCarrying = (! empty($ipAccountIds) || abs($ipRegisterCarrying) > 0.0001)
            ? [['name' => 'Investment Properties', 'carrying' => $ipRegisterCarrying]]
            : [];
        $totalNonCurrentAssets = $totalNonCurrentAssets - $ipGlBalance + $ipRegisterCarrying;

        // --- Biological assets from the register (IAS 41) ---
        $baAccountIds = $all->where('account_type', 'assets')
            ->where('is_biological_asset', true)
            ->pluck('id')->all();
        $baGlBalance = (float) $all->whereIn('id', $baAccountIds)->sum('balance');
        $baRegisterCarrying = $this->biologicalAssetRegisterCarrying($company, $asOfDate);
        $baLinkedAccountIds = $baAccountIds;
        $baCarrying = (! empty($baAccountIds) || abs($baRegisterCarrying) > 0.0001)
            ? [['name' => 'Biological Assets', 'carrying' => $baRegisterCarrying]]
            : [];
        $totalNonCurrentAssets = $totalNonCurrentAssets - $baGlBalance + $baRegisterCarrying;

        // --- Right-of-use assets from the lease register (IFRS 16) ---
        $rouAccountIds = $all->where('account_type', 'assets')
            ->where('is_lease_asset', true)
            ->pluck('id')->all();
        $rouGlBalance = (float) $all->whereIn('id', $rouAccountIds)->sum('balance');
        $rouRegisterCarrying = $this->leaseRouRegisterCarrying($company, $asOfDate);
        $rouLinkedAccountIds = $rouAccountIds;
        $rouCarrying = (! empty($rouAccountIds) || abs($rouRegisterCarrying) > 0.0001)
            ? [['name' => 'Right-of-Use Assets', 'carrying' => $rouRegisterCarrying]]
            : [];
        $totalNonCurrentAssets = $totalNonCurrentAssets - $rouGlBalance + $rouRegisterCarrying;

        // --- Lease liabilities from the lease register (IFRS 16) ---
        $leaseLiabAccountIds = $all->where('account_type', 'liabilities')
            ->where('is_lease_asset', true)
            ->pluck('id')->all();
        $leaseLiabGlBalance = (float) $all->whereIn('id', $leaseLiabAccountIds)->sum('balance');
        $leaseLiabRegisterCarrying = $this->leaseLiabilityRegisterCarrying($company, $asOfDate);
        $leaseLiabLinkedAccountIds = $leaseLiabAccountIds;
        $leaseLiabCarrying = (! empty($leaseLiabAccountIds) || abs($leaseLiabRegisterCarrying) > 0.0001)
            ? [['name' => 'Lease Liabilities', 'carrying' => $leaseLiabRegisterCarrying]]
            : [];
        $totalNonCurrentLiabilities = $totalNonCurrentLiabilities - $leaseLiabGlBalance + $leaseLiabRegisterCarrying;
        $totalLiabilities = $totalCurrentLiabilities + $totalNonCurrentLiabilities;

        $totalAssets = $totalCurrentAssets + $totalNonCurrentAssets;

        return compact(
            'currentAssets',
            'nonCurrentAssets',
            'currentLiabilities',
            'nonCurrentLiabilities',
            'equityAccounts',
            'totalCurrentAssets',
            'totalNonCurrentAssets',
            'totalAssets',
            'totalCurrentLiabilities',
            'totalNonCurrentLiabilities',
            'totalLiabilities',
            'totalEquity',
            'ppeCarrying',
            'ppeLinkedAccountIds',
            'intangibleCarrying',
            'intangibleLinkedAccountIds',
            'inventoryCarrying',
            'inventoryLinkedAccountIds',
            'ipCarrying',
            'ipLinkedAccountIds',
            'baCarrying',
            'baLinkedAccountIds',
            'rouCarrying',
            'rouLinkedAccountIds',
            'leaseLiabCarrying',
            'leaseLiabLinkedAccountIds',
        );
    }

    private function mergeBalanceSheetComparatives(array $current, array $prior, string $priorAsOfDate): array
    {
        $priorAccounts = collect($prior['currentAssets'])
            ->concat($prior['nonCurrentAssets'])
            ->concat($prior['currentLiabilities'])
            ->concat($prior['nonCurrentLiabilities'])
            ->concat($prior['equityAccounts'])
            ->keyBy('id');

        $priorItems = $priorAccounts->flatMap(function ($account) {
            return $account->items->mapWithKeys(fn($item) => [$item->id => $item]);
        });

        $attachComparatives = function ($accounts) use ($priorAccounts, $priorItems) {
            return $accounts->map(function ($account) use ($priorAccounts, $priorItems) {
                $priorAccount = $priorAccounts->get($account->id);
                $account->prior_balance = $priorAccount ? $priorAccount->balance : 0.0;
                $account->prior_groupBalance = $priorAccount ? $priorAccount->groupBalance : 0.0;
                $account->items = $account->items->map(function ($item) use ($priorItems) {
                    $priorItem = $priorItems->get($item->id);
                    $item->prior_balance = $priorItem ? $priorItem->balance : 0.0;

                    return $item;
                });

                return $account;
            });
        };

        return [
            'currentAssets' => $attachComparatives($current['currentAssets']),
            'nonCurrentAssets' => $attachComparatives($current['nonCurrentAssets']),
            'currentLiabilities' => $attachComparatives($current['currentLiabilities']),
            'nonCurrentLiabilities' => $attachComparatives($current['nonCurrentLiabilities']),
            'equityAccounts' => $attachComparatives($current['equityAccounts']),
            'totalCurrentAssetsPrior' => $prior['totalCurrentAssets'],
            'totalNonCurrentAssetsPrior' => $prior['totalNonCurrentAssets'],
            'totalAssetsPrior' => $prior['totalAssets'],
            'totalCurrentLiabilitiesPrior' => $prior['totalCurrentLiabilities'],
            'totalNonCurrentLiabilitiesPrior' => $prior['totalNonCurrentLiabilities'],
            'totalLiabilitiesPrior' => $prior['totalLiabilities'],
            'totalEquityPrior' => $prior['totalEquity'],
            'ppeCarryingPrior' => $prior['ppeCarrying'],
            'intangibleCarryingPrior' => $prior['intangibleCarrying'],
            'inventoryCarryingPrior' => $prior['inventoryCarrying'],
            'ipCarryingPrior' => $prior['ipCarrying'],
            'baCarryingPrior' => $prior['baCarrying'],
            'rouCarryingPrior' => $prior['rouCarrying'],
            'leaseLiabCarryingPrior' => $prior['leaseLiabCarrying'],
            'compare' => true,
            'priorAsOfDate' => $priorAsOfDate,
        ];
    }

    /**
     * Direct-method cash flow derived from the ledger. Every transaction that
     * touches a cash/bank account is analysed: the non-cash side of the entry
     * determines the activity (operating / investing / financing) and direction
     * (a non-cash credit is a source of cash, a non-cash debit is a use of cash).
     *
     * @return array{receipts:float,payments:float,cashGenerated:float,interest:float,
     *   tax:float,netOperating:float,investing:array<string,float>,netInvesting:float,
     *   financing:array<string,float>,netFinancing:float,netMovement:float,cashBegin:float,cashEnd:float}
     */
    private function manualCashFlowViewData(Company $company, string $start, string $end, bool $compare): array
    {
        $entries = DB::table('cash_flow_manual_entries')
            ->where('company_id', $company->id)
            ->where('period_start', $start)
            ->where('period_end', $end)
            ->orderBy('sort_order')
            ->get();

        // Prior year comparative: load the previous FY's own saved entries
        $priEntries = collect();
        if ($compare) {
            $priEntries = DB::table('cash_flow_manual_entries')
                ->where('company_id', $company->id)
                ->where('period_start', Carbon::parse($start)->subYear()->format('Y-m-d'))
                ->where('period_end', Carbon::parse($end)->subYear()->format('Y-m-d'))
                ->orderBy('sort_order')
                ->get();
        }

        $cur = fn ($key) => (float) ($entries->firstWhere('line_key', $key)?->current_amount ?? 0);
        $pri = fn ($key) => (float) ($priEntries->firstWhere('line_key', $key)?->current_amount ?? 0);

        // Merge dynamic lines from both periods so every line appears in the correct column
        $mergeDynamic = function (string $section) use ($entries, $priEntries): array {
            $curMap = $entries->filter(fn ($e) => $e->section === $section && $e->line_key === null)
                ->keyBy('line_name');
            $priMap = $priEntries->filter(fn ($e) => $e->section === $section && $e->line_key === null)
                ->keyBy('line_name');
            $names = array_values(array_unique(array_merge($curMap->keys()->all(), $priMap->keys()->all())));
            return array_map(fn ($n) => [
                'name' => $n,
                'cur'  => (float) ($curMap->get($n)?->current_amount ?? 0),
                'pri'  => (float) ($priMap->get($n)?->current_amount ?? 0),
            ], $names);
        };

        $manualInvesting = $mergeDynamic('investing');
        $manualFinancing = $mergeDynamic('financing');

        // Merge register-derived investing lines with manual entries.
        // Register lines whose name matches a manual line are skipped (manual wins).
        $registerInvesting = $this->registerInvestingLines($company, $start, $end, $compare);
        $manualInvNames = array_map(fn ($l) => strtolower($l['name']), $manualInvesting);
        $investingLines = $manualInvesting;
        foreach ($registerInvesting as $rl) {
            if (! in_array(strtolower($rl['name']), $manualInvNames)) {
                $investingLines[] = $rl;
            }
        }

        // Merge register-derived financing lines (lease payments) with manual entries.
        $registerFinancing = $this->registerFinancingLines($company, $start, $end, $compare);
        $manualFinNames = array_map(fn ($l) => strtolower($l['name']), $manualFinancing);
        $financingLines = $manualFinancing;
        foreach ($registerFinancing as $rl) {
            if (! in_array(strtolower($rl['name']), $manualFinNames)) {
                $financingLines[] = $rl;
            }
        }

        $receipts      = $cur('receipts');
        $payments      = $cur('payments');
        $cashGenerated = $receipts + $payments;
        $interest      = $cur('interest');
        $tax           = $cur('tax');
        $netOperating  = $cashGenerated + $interest + $tax;
        $netInvesting  = (float) array_sum(array_column($investingLines, 'cur'));
        $netFinancing  = (float) array_sum(array_column($financingLines, 'cur'));
        $netMovement   = $netOperating + $netInvesting + $netFinancing;

        $priReceipts = $pri('receipts');
        $priPayments = $pri('payments');
        $priCashGen  = $priReceipts + $priPayments;
        $priInterest = $pri('interest');
        $priTax      = $pri('tax');
        $priNetOp    = $priCashGen + $priInterest + $priTax;
        $priNetInv   = (float) array_sum(array_column($investingLines, 'pri'));
        $priNetFin   = (float) array_sum(array_column($financingLines, 'pri'));

        return [
            'receipts'       => $receipts,
            'payments'       => $payments,
            'cashGenerated'  => $cashGenerated,
            'interest'       => $interest,
            'tax'            => $tax,
            'netOperating'   => $netOperating,
            'investingLines' => $investingLines,
            'netInvesting'   => $netInvesting,
            'financingLines' => $financingLines,
            'netFinancing'   => $netFinancing,
            'netMovement'    => $netMovement,
            'cashBegin'      => $cur('cash_begin'),
            'cashEnd'        => $cur('cash_end'),
            'prior' => $compare ? [
                'receipts'      => $priReceipts,
                'payments'      => $priPayments,
                'cashGenerated' => $priCashGen,
                'interest'      => $priInterest,
                'tax'           => $priTax,
                'netOperating'  => $priNetOp,
                'netInvesting'  => $priNetInv,
                'netFinancing'  => $priNetFin,
                'netMovement'   => $priNetOp + $priNetInv + $priNetFin,
                'cashBegin'     => $pri('cash_begin'),
                'cashEnd'       => $pri('cash_end'),
            ] : null,
        ];
    }

    /**
     * Derive investing-activity lines from the PPE and inventory registers.
     *
     * @return array<int, array{name: string, cur: float, pri: float, source: string}>
     */
    private function registerInvestingLines(Company $company, string $start, string $end, bool $withPrior = false): array
    {
        // Cash/bank account IDs — flagged by the user via IAS 7 toggle on the chart of accounts.
        $cashAccountIds = $company->chartOfAccounts()
            ->where('is_cash', true)
            ->pluck('id')
            ->all();

        // Sum the net cash movement on journal lines that hit cash/bank accounts
        // for a given set of transaction IDs. Returns negative for outflows, positive for inflows.
        $cashFromTransactions = function (array $txnIds) use ($cashAccountIds): float {
            if (empty($txnIds) || empty($cashAccountIds)) return 0.0;
            return (float) DB::table('journal_lines')
                ->whereIn('transaction_id', $txnIds)
                ->whereIn('chart_of_account_id', $cashAccountIds)
                ->selectRaw("SUM(CASE WHEN type = 'debit' THEN amount ELSE -amount END) as net")
                ->value('net') ?? 0;
        };

        // Collect transaction IDs from register events by type and event category for a period.
        $eventTxns = function (string $register, array $purchaseTypes, array $disposalTypes, string $s, string $e) use ($company): array {
            $purchase = [];
            $disposal = [];

            if ($register === 'asset') {
                $query = \App\Models\AssetEvent::whereHas('asset', fn ($q) => $q->where('company_id', $company->id))
                    ->whereNotNull('transaction_id')
                    ->where('transaction_id', '!=', '')
                    ->whereBetween('event_date', [$s, $e]);
                $purchase = (clone $query)->whereIn('event_type', $purchaseTypes)->pluck('transaction_id')->all();
                $disposal = (clone $query)->whereIn('event_type', $disposalTypes)->pluck('transaction_id')->all();
            } elseif ($register === 'intangible') {
                $query = \App\Models\IntangibleAssetEvent::whereHas('intangibleAsset', fn ($q) => $q->where('company_id', $company->id))
                    ->whereNotNull('transaction_id')
                    ->where('transaction_id', '!=', '')
                    ->whereBetween('event_date', [$s, $e]);
                $purchase = (clone $query)->whereIn('event_type', $purchaseTypes)->pluck('transaction_id')->all();
                $disposal = (clone $query)->whereIn('event_type', $disposalTypes)->pluck('transaction_id')->all();
            } elseif ($register === 'investment_property') {
                $query = \App\Models\InvestmentPropertyEvent::whereHas('investmentProperty', fn ($q) => $q->where('company_id', $company->id))
                    ->whereNotNull('transaction_id')
                    ->where('transaction_id', '!=', '')
                    ->whereBetween('event_date', [$s, $e]);
                $purchase = (clone $query)->whereIn('event_type', $purchaseTypes)->pluck('transaction_id')->all();
                $disposal = (clone $query)->whereIn('event_type', $disposalTypes)->pluck('transaction_id')->all();
            } elseif ($register === 'biological_asset') {
                $query = \App\Models\BiologicalAssetEvent::whereHas('biologicalAsset', fn ($q) => $q->where('company_id', $company->id))
                    ->whereNotNull('transaction_id')
                    ->where('transaction_id', '!=', '')
                    ->whereBetween('event_date', [$s, $e]);
                $purchase = (clone $query)->whereIn('event_type', $purchaseTypes)->pluck('transaction_id')->all();
                $disposal = (clone $query)->whereIn('event_type', $disposalTypes)->pluck('transaction_id')->all();
            } elseif ($register === 'inventory') {
                $query = \App\Models\InventoryMovement::where('company_id', $company->id)
                    ->whereNotNull('transaction_id')
                    ->where('transaction_id', '!=', '')
                    ->whereBetween('moved_at', [$s, $e]);
                $purchase = (clone $query)->whereIn('action', $purchaseTypes)->pluck('transaction_id')->all();
                $disposal = (clone $query)->whereIn('action', $disposalTypes)->pluck('transaction_id')->all();
            }

            return ['purchase' => array_unique($purchase), 'disposal' => array_unique($disposal)];
        };

        // Define register configurations: [register_key, label_purchase, label_disposal, purchase_event_types, disposal_event_types]
        $registers = [
            ['asset', 'Purchase of property, plant and equipment', 'Proceeds from disposal of PPE',
                ['acquisition', 'capitalisation'], ['disposal']],
            ['intangible', 'Purchase of intangible assets', 'Proceeds from disposal of intangible assets',
                ['acquisition', 'capitalisation'], ['disposal']],
            ['investment_property', 'Purchase of investment properties', 'Proceeds from disposal of investment properties',
                ['acquisition', 'capitalisation'], ['disposal']],
            ['biological_asset', 'Purchase of biological assets', 'Proceeds from disposal of biological assets',
                ['acquisition'], ['disposal']],
            ['inventory', 'Purchase of inventory', null,
                ['receive', 'adjustment_in'], ['sale', 'adjustment_out']],
        ];

        $lines = [];

        foreach ($registers as [$regKey, $purchaseLabel, $disposalLabel, $purchaseTypes, $disposalTypes]) {
            $cur = $eventTxns($regKey, $purchaseTypes, $disposalTypes, $start, $end);
            $curPurchaseCash = $cashFromTransactions($cur['purchase']);
            $curDisposalCash = $cashFromTransactions($cur['disposal']);

            $priPurchaseCash = 0.0;
            $priDisposalCash = 0.0;

            if ($withPrior) {
                $priStart = \Carbon\Carbon::parse($start)->subYear()->format('Y-m-d');
                $priEnd   = \Carbon\Carbon::parse($end)->subYear()->format('Y-m-d');
                $pri = $eventTxns($regKey, $purchaseTypes, $disposalTypes, $priStart, $priEnd);
                $priPurchaseCash = $cashFromTransactions($pri['purchase']);
                $priDisposalCash = $cashFromTransactions($pri['disposal']);
            }

            if ($curPurchaseCash != 0 || $priPurchaseCash != 0) {
                $lines[] = [
                    'name'   => $purchaseLabel,
                    'cur'    => $curPurchaseCash,
                    'pri'    => $priPurchaseCash,
                    'source' => 'register',
                ];
            }

            if ($disposalLabel && ($curDisposalCash != 0 || $priDisposalCash != 0)) {
                $lines[] = [
                    'name'   => $disposalLabel,
                    'cur'    => $curDisposalCash,
                    'pri'    => $priDisposalCash,
                    'source' => 'register',
                ];
            }
        }

        return $lines;
    }

    private function registerFinancingLines(Company $company, string $start, string $end, bool $withPrior = false): array
    {
        $leasePayments = (float) $company->leases()
            ->where('is_short_term', false)
            ->where('is_low_value', false)
            ->whereNotNull('commencement_date')
            ->where('commencement_date', '<=', $end)
            ->get()
            ->sum(function ($lease) use ($start, $end) {
                $from = max($lease->commencement_date->toDateString(), $start);
                $months = \Carbon\Carbon::parse($from)->diffInMonths(\Carbon\Carbon::parse($end));
                return $months * (float) $lease->monthly_payment;
            });

        $priLeasePay = 0.0;

        if ($withPrior) {
            $priStart = \Carbon\Carbon::parse($start)->subYear()->format('Y-m-d');
            $priEnd   = \Carbon\Carbon::parse($end)->subYear()->format('Y-m-d');

            $priLeasePay = (float) $company->leases()
                ->where('is_short_term', false)
                ->where('is_low_value', false)
                ->whereNotNull('commencement_date')
                ->where('commencement_date', '<=', $priEnd)
                ->get()
                ->sum(function ($lease) use ($priStart, $priEnd) {
                    $from = max($lease->commencement_date->toDateString(), $priStart);
                    $months = \Carbon\Carbon::parse($from)->diffInMonths(\Carbon\Carbon::parse($priEnd));
                    return $months * (float) $lease->monthly_payment;
                });
        }

        $lines = [];

        if ($leasePayments != 0 || $priLeasePay != 0) {
            $lines[] = [
                'name'   => 'Lease payments',
                'cur'    => -abs($leasePayments),
                'pri'    => -abs($priLeasePay),
                'source' => 'register',
            ];
        }

        return $lines;
    }

    private function buildCashFlow(Company $company, string $start, string $end): array
    {
        $accounts = $company->chartOfAccounts()->get(['id', 'account_code', 'account_name', 'account_type', 'opening_balance', 'parent_id', 'cash_flow_category', 'is_cash']);
        $accById = $accounts->keyBy('id');

        // Cash & cash equivalents = asset accounts flagged with is_cash (IAS 7).
        $cashAccounts = $accounts->filter(fn ($a) => $a->account_type === 'assets' && $a->is_cash);
        $cashIds = $cashAccounts->pluck('id')->all();

        // Category resolution: an explicit mapping on the account (or its parent)
        // always wins; otherwise fall back to the account-type / code-range heuristic.
        $classify = function ($acc) use ($accById) {
            $explicit = $acc->cash_flow_category;
            if (! $explicit && $acc->parent_id && $accById->get($acc->parent_id)) {
                $explicit = $accById->get($acc->parent_id)->cash_flow_category;
            }
            if (in_array($explicit, \App\Models\ChartOfAccount::CASH_FLOW_CATEGORIES, true)) {
                return $explicit;
            }

            $code = (int) $acc->account_code;
            return match (true) {
                $acc->account_type === 'income' => 'operating',
                $acc->account_type === 'expenses' => 'operating',
                $acc->account_type === 'assets' => $code >= 1006000 ? 'investing' : 'operating',
                $acc->account_type === 'liabilities' => $code >= 2006000 ? 'financing' : 'operating',
                $acc->account_type === 'equity' => 'financing',
                default => 'operating',
            };
        };

        $receipts = 0.0; $payments = 0.0; $interest = 0.0; $tax = 0.0;
        $investing = []; $financing = [];

        if (! empty($cashIds)) {
            $txIds = DB::table('journal_lines')
                ->join('transactions', 'journal_lines.transaction_id', '=', 'transactions.id')
                ->whereIn('journal_lines.chart_of_account_id', $cashIds)
                ->where('transactions.company_id', $company->id)
                ->whereIn('transactions.status', ['posted', 'reversed'])
                ->whereBetween('transactions.transaction_date', [$start, $end])
                ->distinct()
                ->pluck('transactions.id');

            $lines = DB::table('journal_lines')
                ->whereIn('transaction_id', $txIds)
                ->whereNotIn('chart_of_account_id', $cashIds)
                ->select('chart_of_account_id', 'type', 'amount')
                ->get();

            foreach ($lines as $l) {
                $acc = $accById->get($l->chart_of_account_id);
                if (! $acc) {
                    continue;
                }
                // A non-cash credit financed a cash inflow; a non-cash debit used cash.
                $effect = $l->type === 'credit' ? (float) $l->amount : -(float) $l->amount;
                switch ($classify($acc)) {
                    case 'interest': $interest += $effect; break;
                    case 'tax':      $tax += $effect; break;
                    case 'investing': $investing[$acc->account_name] = ($investing[$acc->account_name] ?? 0) + $effect; break;
                    case 'financing': $financing[$acc->account_name] = ($financing[$acc->account_name] ?? 0) + $effect; break;
                    default:
                        if ($effect >= 0) { $receipts += $effect; } else { $payments += $effect; }
                }
            }
        }

        $cashGenerated = $receipts + $payments;
        $netOperating = $cashGenerated + $interest + $tax;
        $netInvesting = array_sum($investing);
        $netFinancing = array_sum($financing);
        $netMovement = $netOperating + $netInvesting + $netFinancing;

        $cashEnd = $this->cashBalanceAsOf($company, $cashAccounts, $end);
        $cashBegin = $this->cashBalanceAsOf($company, $cashAccounts, Carbon::parse($start)->subDay()->format('Y-m-d'));

        return compact('receipts', 'payments', 'cashGenerated', 'interest', 'tax', 'netOperating',
            'investing', 'netInvesting', 'financing', 'netFinancing', 'netMovement', 'cashBegin', 'cashEnd');
    }

    private function cashBalanceAsOf(Company $company, $cashAccounts, string $asOf): float
    {
        $ids = $cashAccounts->pluck('id')->all();
        if (empty($ids)) {
            return 0.0;
        }
        $ctx = $company->openingContext($asOf);
        $totals = $ctx['start']
            ? $this->accountNetTotals($company, $ids, $ctx['start'], $asOf)
            : $this->accountNetTotalsCumulative($company, $ids, $asOf);
        $bal = 0.0;
        foreach ($cashAccounts as $a) {
            $row = $totals->get($a->id);
            $d = $row ? (float) $row->total_debits : 0.0;
            $c = $row ? (float) $row->total_credits : 0.0;
            $opening = $ctx['amounts'] !== null
                ? (float) ($ctx['amounts'][$a->id] ?? 0)
                : (float) $a->opening_balance;
            $bal += $opening + ($d - $c);
        }
        return $bal;
    }

    /**
     * Assemble the render-ready cash-flow structure (current + prior comparative)
     * consumed by the socf partial and the PDF.
     */
    private function cashFlowViewData(Company $company, string $start, string $end, bool $compare, ?string $priorStart = null, ?string $priorEnd = null): array
    {
        $cur = $this->buildCashFlow($company, $start, $end);
        $pri = $compare ? $this->buildCashFlow($company, $priorStart, $priorEnd) : null;

        $mergeLines = function (string $key) use ($cur, $pri) {
            $names = array_values(array_unique(array_merge(array_keys($cur[$key]), $pri ? array_keys($pri[$key]) : [])));
            return array_map(fn ($n) => ['name' => $n, 'cur' => $cur[$key][$n] ?? 0.0, 'pri' => $pri[$key][$n] ?? 0.0], $names);
        };

        return [
            'receipts' => $cur['receipts'], 'payments' => $cur['payments'], 'cashGenerated' => $cur['cashGenerated'],
            'interest' => $cur['interest'], 'tax' => $cur['tax'], 'netOperating' => $cur['netOperating'],
            'investingLines' => $mergeLines('investing'), 'netInvesting' => $cur['netInvesting'],
            'financingLines' => $mergeLines('financing'), 'netFinancing' => $cur['netFinancing'],
            'netMovement' => $cur['netMovement'], 'cashBegin' => $cur['cashBegin'], 'cashEnd' => $cur['cashEnd'],
            'prior' => $pri ? [
                'receipts' => $pri['receipts'], 'payments' => $pri['payments'], 'cashGenerated' => $pri['cashGenerated'],
                'interest' => $pri['interest'], 'tax' => $pri['tax'], 'netOperating' => $pri['netOperating'],
                'netInvesting' => $pri['netInvesting'], 'netFinancing' => $pri['netFinancing'],
                'netMovement' => $pri['netMovement'], 'cashBegin' => $pri['cashBegin'], 'cashEnd' => $pri['cashEnd'],
            ] : null,
        ];
    }

    private function accountNetTotals(Company $company, mixed $accountIds, string $startDate, string $endDate): Collection
    {
        return DB::table('journal_lines')
            ->join('transactions', 'journal_lines.transaction_id', '=', 'transactions.id')
            ->whereIn('journal_lines.chart_of_account_id', $accountIds)
            ->where('transactions.company_id', $company->id)
            ->whereIn('transactions.status', ['posted', 'reversed'])
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->select([
                'journal_lines.chart_of_account_id',
                DB::raw("SUM(CASE WHEN journal_lines.type = 'debit' THEN journal_lines.amount ELSE 0 END) as total_debits"),
                DB::raw("SUM(CASE WHEN journal_lines.type = 'credit' THEN journal_lines.amount ELSE 0 END) as total_credits"),
            ])
            ->groupBy('journal_lines.chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');
    }

    private function accountNetTotalsCumulative(Company $company, mixed $accountIds, string $asOfDate): Collection
    {
        return DB::table('journal_lines')
            ->join('transactions', 'journal_lines.transaction_id', '=', 'transactions.id')
            ->whereIn('journal_lines.chart_of_account_id', $accountIds)
            ->where('transactions.company_id', $company->id)
            ->whereIn('transactions.status', ['posted', 'reversed'])
            ->where('transactions.transaction_date', '<=', $asOfDate)
            ->select([
                'journal_lines.chart_of_account_id',
                DB::raw("SUM(CASE WHEN journal_lines.type = 'debit' THEN journal_lines.amount ELSE 0 END) as total_debits"),
                DB::raw("SUM(CASE WHEN journal_lines.type = 'credit' THEN journal_lines.amount ELSE 0 END) as total_credits"),
            ])
            ->groupBy('journal_lines.chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');
    }

    private function buildGeneralLedger(Company $company, string $startDate, string $endDate): Collection
    {
        $activeAccounts = $company->chartOfAccounts()
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $allIds = $activeAccounts->pluck('id');

        // Period-aware opening base: when periods exist, the opening balance at the
        // report start is the period's opening plus movements earlier in the period;
        // otherwise it is the legacy opening_balance column plus all prior movements.
        $ctx = $company->openingContext($startDate);
        $preStart = $ctx['start'];

        $preQuery = DB::table('journal_lines')
            ->join('transactions', 'journal_lines.transaction_id', '=', 'transactions.id')
            ->whereIn('journal_lines.chart_of_account_id', $allIds)
            ->where('transactions.company_id', $company->id)
            ->whereIn('transactions.status', ['posted', 'reversed'])
            ->where('transactions.transaction_date', '<', $startDate);

        if ($preStart) {
            $preQuery->where('transactions.transaction_date', '>=', $preStart);
        }

        $preMovements = $preQuery
            ->select([
                'journal_lines.chart_of_account_id',
                DB::raw("SUM(CASE WHEN journal_lines.type = 'debit' THEN journal_lines.amount ELSE 0 END) as total_debits"),
                DB::raw("SUM(CASE WHEN journal_lines.type = 'credit' THEN journal_lines.amount ELSE 0 END) as total_credits"),
            ])
            ->groupBy('journal_lines.chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');

        $periodLines = DB::table('journal_lines')
            ->join('transactions', 'journal_lines.transaction_id', '=', 'transactions.id')
            ->whereIn('journal_lines.chart_of_account_id', $allIds)
            ->where('transactions.company_id', $company->id)
            ->whereIn('transactions.status', ['posted', 'reversed'])
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->select([
                'journal_lines.chart_of_account_id',
                'transactions.transaction_date',
                'transactions.reference',
                'transactions.description',
                'journal_lines.type',
                'journal_lines.amount',
            ])
            ->orderBy('transactions.transaction_date')
            ->orderBy('transactions.id')
            ->get()
            ->groupBy('chart_of_account_id');

        $debitNormal = ['assets', 'expenses'];

        return $activeAccounts
            ->filter(fn($account) => $periodLines->has($account->id))
            ->map(function ($account) use ($preMovements, $periodLines, $debitNormal, $ctx) {
                $pre = $preMovements->get($account->id);
                $preDebits = $pre ? (float) $pre->total_debits : 0.0;
                $preCredits = $pre ? (float) $pre->total_credits : 0.0;
                $isDebitNorm = in_array($account->account_type, $debitNormal);
                $opening = $ctx['amounts'] !== null
                    ? (float) ($ctx['amounts'][$account->id] ?? 0)
                    : (float) $account->opening_balance;

                $openingBalance = $isDebitNorm
                    ? $opening + ($preDebits - $preCredits)
                    : $opening + ($preCredits - $preDebits);

                $lines = $periodLines->get($account->id, collect());
                $runningBalance = $openingBalance;
                $periodDebits = 0.0;
                $periodCredits = 0.0;

                $linesWithBalance = $lines->map(function ($line) use (&$runningBalance, &$periodDebits, &$periodCredits, $isDebitNorm) {
                    $amount = (float) $line->amount;

                    if ($line->type === 'debit') {
                        $periodDebits += $amount;
                        $runningBalance += $isDebitNorm ? $amount : -$amount;
                    } else {
                        $periodCredits += $amount;
                        $runningBalance += $isDebitNorm ? -$amount : $amount;
                    }

                    return (object) [
                        'date' => $line->transaction_date,
                        'reference' => $line->reference,
                        'description' => $line->description,
                        'debit' => $line->type === 'debit' ? $amount : null,
                        'credit' => $line->type === 'credit' ? $amount : null,
                        'running_balance' => $runningBalance,
                    ];
                });

                return (object) [
                    'account' => $account,
                    'opening_balance' => $openingBalance,
                    'closing_balance' => $runningBalance,
                    'period_debits' => $periodDebits,
                    'period_credits' => $periodCredits,
                    'lines' => $linesWithBalance,
                ];
            })
            ->values();
    }
}
