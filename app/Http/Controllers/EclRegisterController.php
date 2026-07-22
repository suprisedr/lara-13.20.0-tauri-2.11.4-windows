<?php

namespace App\Http\Controllers;

use App\Jobs\PostEclProvisionJob;
use App\Models\Company;
use App\Services\RoadRunnerEclPostingDispatcher;
use App\Models\CustomerAgeAnalysis;
use App\Models\EclRateSetting;
use App\Services\AgeAnalysisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EclRegisterController extends Controller
{
    public function index(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));
        $rates    = EclRateSetting::forCompany($company);

        // Generate fresh analysis for the requested date
        $analyses = (new AgeAnalysisService)->generate($company, $asOfDate);

        $totals = [
            'current_amount'    => $analyses->sum('current_amount'),
            'days_31_60'        => $analyses->sum('days_31_60'),
            'days_61_90'        => $analyses->sum('days_61_90'),
            'days_91_plus'      => $analyses->sum('days_91_plus'),
            'total_outstanding' => $analyses->sum('total_outstanding'),
            'ecl_current'       => $analyses->sum('ecl_current'),
            'ecl_31_60'         => $analyses->sum('ecl_31_60'),
            'ecl_61_90'         => $analyses->sum('ecl_61_90'),
            'ecl_91_plus'       => $analyses->sum('ecl_91_plus'),
            'total_ecl'         => $analyses->sum('total_ecl'),
        ];

        // Historical snapshots: distinct dates with totals
        $snapshots = CustomerAgeAnalysis::where('company_id', $company->id)
            ->select('as_of_date')
            ->selectRaw('SUM(total_outstanding) as total_outstanding')
            ->selectRaw('SUM(total_ecl) as total_ecl')
            ->selectRaw('COUNT(DISTINCT customer_id) as customer_count')
            ->groupBy('as_of_date')
            ->orderByDesc('as_of_date')
            ->limit(24)
            ->get();

        // Current balance on the allowance account (if exists)
        $allowanceBalance = $this->getAllowanceBalance($company, $asOfDate);

        // Check if a provision journal has already been posted for this date
        $provisionPosted = $company->transactions()
            ->where('source_document', 'ecl_provision:' . $asOfDate)
            ->exists();

        return view('companies.ecl-register.index', compact(
            'company', 'analyses', 'rates', 'totals', 'asOfDate', 'snapshots', 'allowanceBalance', 'provisionPosted'
        ));
    }

    public function customers(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));
        $rates    = EclRateSetting::forCompany($company);
        $analyses = (new AgeAnalysisService)->generate($company, $asOfDate);

        $totals = [
            'current_amount'    => $analyses->sum('current_amount'),
            'days_31_60'        => $analyses->sum('days_31_60'),
            'days_61_90'        => $analyses->sum('days_61_90'),
            'days_91_plus'      => $analyses->sum('days_91_plus'),
            'total_outstanding' => $analyses->sum('total_outstanding'),
            'ecl_current'       => $analyses->sum('ecl_current'),
            'ecl_31_60'         => $analyses->sum('ecl_31_60'),
            'ecl_61_90'         => $analyses->sum('ecl_61_90'),
            'ecl_91_plus'       => $analyses->sum('ecl_91_plus'),
            'total_ecl'         => $analyses->sum('total_ecl'),
        ];

        return view('companies.ecl-register.customers', compact(
            'company', 'analyses', 'rates', 'totals', 'asOfDate'
        ));
    }

    public function show(Company $company, string $date): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $rates    = EclRateSetting::forCompany($company);
        $analyses = CustomerAgeAnalysis::where('company_id', $company->id)
            ->where('as_of_date', $date)
            ->with('customer')
            ->orderBy('customer_id')
            ->get();

        if ($analyses->isEmpty()) {
            $analyses = (new AgeAnalysisService)->generate($company, $date);
        }

        $totals = [
            'current_amount'    => $analyses->sum('current_amount'),
            'days_31_60'        => $analyses->sum('days_31_60'),
            'days_61_90'        => $analyses->sum('days_61_90'),
            'days_91_plus'      => $analyses->sum('days_91_plus'),
            'total_outstanding' => $analyses->sum('total_outstanding'),
            'ecl_current'       => $analyses->sum('ecl_current'),
            'ecl_31_60'         => $analyses->sum('ecl_31_60'),
            'ecl_61_90'         => $analyses->sum('ecl_61_90'),
            'ecl_91_plus'       => $analyses->sum('ecl_91_plus'),
            'total_ecl'         => $analyses->sum('total_ecl'),
        ];

        $allowanceBalance = $this->getAllowanceBalance($company, $date);

        return view('companies.ecl-register.show', compact(
            'company', 'analyses', 'rates', 'totals', 'date', 'allowanceBalance'
        ));
    }

    public function updateRates(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'current_rate'      => ['required', 'numeric', 'min:0', 'max:100'],
            'days_31_60_rate'   => ['required', 'numeric', 'min:0', 'max:100'],
            'days_61_90_rate'   => ['required', 'numeric', 'min:0', 'max:100'],
            'days_91_plus_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'as_of_date'        => ['nullable', 'date'],
        ]);

        $rates = \Illuminate\Support\Arr::except($validated, ['as_of_date']);
        $rates = array_map(fn ($rate) => round($rate / 100, 4), $rates);

        EclRateSetting::updateOrCreate(
            ['company_id' => $company->id],
            $rates,
        );

        return redirect()
            ->route('companies.ecl-register.index', [$company, 'as_of_date' => $validated['as_of_date'] ?? now()->format('Y-m-d')])
            ->with('success', 'ECL provision matrix rates updated.');
    }

    public function postProvision(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'as_of_date' => ['required', 'date'],
        ]);

        $asOfDate = $validated['as_of_date'];

        app(RoadRunnerEclPostingDispatcher::class)->dispatch($company->id, auth()->id(), $asOfDate);

        return redirect()
            ->route('companies.ecl-register.index', [$company, 'as_of_date' => $asOfDate])
            ->with('success', 'ECL provision is being posted by AI in the background. Refresh in a moment to see the transaction.');
    }

    private function getAllowanceBalance(Company $company, string $asOfDate): float
    {
        $account = $company->chartOfAccounts()
            ->where('is_contra', true)
            ->where(fn ($q) =>
                $q->where('account_name', 'like', '%allowance%')
                  ->orWhere('account_name', 'like', '%provision%doubtful%')
                  ->orWhere('account_name', 'like', '%credit loss%')
            )
            ->first();

        if (!$account) return 0.0;

        $credits = (float) $account->journalLines()
            ->whereHas('transaction', fn ($q) => $q->where('transaction_date', '<=', $asOfDate))
            ->where('type', 'credit')
            ->sum('amount');

        $debits = (float) $account->journalLines()
            ->whereHas('transaction', fn ($q) => $q->where('transaction_date', '<=', $asOfDate))
            ->where('type', 'debit')
            ->sum('amount');

        return round($credits - $debits, 2);
    }
}
