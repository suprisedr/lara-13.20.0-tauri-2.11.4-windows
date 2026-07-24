<?php

namespace App\Http\Controllers;

use App\Events\CompanyOnboarded;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function step1(): View
    {
        return view('onboarding.step1');
    }

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'registered_name'          => ['required', 'string', 'max:255'],
            'company_type'             => ['required', 'string', 'in:' . implode(',', array_keys(Company::companyTypes()))],
            'registration_number'      => ['nullable', 'string', 'regex:/^\d{4}\/\d{6}\/\d{2}$/'],
            'financial_year_end_month' => ['required', 'integer', 'between:1,12'],
            'address_line_1'           => ['required', 'string', 'max:255'],
            'address_line_2'           => ['nullable', 'string', 'max:255'],
            'city'                     => ['required', 'string', 'max:100'],
            'province'                 => ['required', 'string', 'in:' . implode(',', array_keys(Company::saProvinces()))],
            'postal_code'              => ['required', 'string', 'max:10'],
        ]);

        $company = $request->user()->companies()->create([
            ...$validated,
            'onboarding_step' => 2,
        ]);

        return redirect()->route('onboarding.step2', $company);
    }

    public function step2(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        return view('onboarding.step2', compact('company'));
    }

    public function storeStep2(Request $request, Company $company): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'income_tax_number' => ['nullable', 'string', 'digits:10'],
            'vat_number'        => ['nullable', 'string', 'digits:10'],
            'paye_number'       => ['nullable', 'string', 'max:20'],
            'uif_number'        => ['nullable', 'string', 'max:20'],
            'sdl_number'        => ['nullable', 'string', 'max:20'],
        ]);

        $company->update([...$validated, 'onboarding_step' => 3]);

        return redirect()->route('onboarding.step3', $company);
    }

    public function step3(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $industries = Company::industries();

        return view('onboarding.step3', compact('company', 'industries'));
    }

    public function storeStep3(Request $request, Company $company): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $industries = Company::industries();

        $validated = $request->validate([
            'industry'               => ['required', 'string', 'in:' . implode(',', array_keys($industries))],
            'bank_name'              => ['nullable', 'string', 'max:100'],
            'bank_account_number'    => ['nullable', 'string', 'max:20'],
            'bank_account_type'      => ['nullable', 'string', 'in:current,savings,cheque'],
            'bank_branch_code'       => ['nullable', 'string', 'max:6'],
        ]);

        $company->update([
            ...$validated,
            'onboarding_completed_at' => now(),
        ]);

        app(\App\Services\RoadRunnerChartOfAccountsDispatcher::class)->dispatch($company->id);

        return redirect()->route('dashboard')->with('company_onboarded', $company->registered_name);
    }

    public function cancel(Company $company): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_if($company->onboarding_completed_at, 403);

        $company->delete();

        return redirect()->route('dashboard');
    }
}
