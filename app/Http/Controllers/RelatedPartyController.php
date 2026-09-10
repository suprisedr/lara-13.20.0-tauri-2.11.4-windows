<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\RelatedParty;
use App\Models\RelatedPartyTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RelatedPartyController extends Controller
{
    public function index(Company $company): View
    {
        $this->authorizeCompany($company);

        $parties = $company->relatedParties()
            ->withCount('relatedPartyTransactions')
            ->orderBy('name')
            ->get();

        return view('companies.related-parties.index', compact('company', 'parties'));
    }

    public function show(Company $company, RelatedParty $relatedParty): View
    {
        $this->authorizeCompany($company);
        abort_unless($relatedParty->company_id === $company->id, 404);

        $relatedParty->load('relatedPartyTransactions');

        return view('companies.related-parties.show', compact('company', 'relatedParty'));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);

        $data = $request->validate([
            'name'              => ['required', 'string', 'max:150'],
            'relationship_type' => ['required', 'string', Rule::in(array_keys(RelatedParty::TYPES))],
            'description'       => ['nullable', 'string'],
            'contact_person'    => ['nullable', 'string', 'max:150'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $data['is_active'] ?? true;

        $company->relatedParties()->create($data);

        return redirect()->route('companies.related-parties.index', $company)
            ->with('success', 'Related party added.');
    }

    public function update(Company $company, RelatedParty $relatedParty, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($relatedParty->company_id === $company->id, 404);

        $data = $request->validate([
            'name'              => ['required', 'string', 'max:150'],
            'relationship_type' => ['required', 'string', Rule::in(array_keys(RelatedParty::TYPES))],
            'description'       => ['nullable', 'string'],
            'contact_person'    => ['nullable', 'string', 'max:150'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->has('is_active');

        $relatedParty->update($data);

        return redirect()->route('companies.related-parties.show', [$company, $relatedParty])
            ->with('success', 'Related party updated.');
    }

    public function destroy(Company $company, RelatedParty $relatedParty): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($relatedParty->company_id === $company->id, 404);

        $relatedParty->delete();

        return redirect()->route('companies.related-parties.index', $company)
            ->with('success', 'Related party removed.');
    }

    public function storeTransaction(Company $company, RelatedParty $relatedParty, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($relatedParty->company_id === $company->id, 404);

        $data = $request->validate([
            'transaction_date'      => ['required', 'date'],
            'transaction_type'      => ['required', 'string', Rule::in(array_keys(RelatedPartyTransaction::TRANSACTION_TYPES))],
            'amount'                => ['required', 'numeric', 'min:0'],
            'description'           => ['nullable', 'string'],
            'outstanding_balance'   => ['nullable', 'numeric'],
            'terms_and_conditions'  => ['nullable', 'string'],
            'is_arm_length'         => ['nullable', 'boolean'],
            'transaction_id'        => ['nullable', 'integer', Rule::exists('transactions', 'id')->where('company_id', $company->id)],
        ]);

        $data['is_arm_length']    = $request->has('is_arm_length');
        $data['company_id']       = $company->id;
        $data['related_party_id'] = $relatedParty->id;

        RelatedPartyTransaction::create($data);

        return redirect()->route('companies.related-parties.show', [$company, $relatedParty])
            ->with('success', 'Related party transaction recorded.');
    }

    public function destroyTransaction(Company $company, RelatedParty $relatedParty, RelatedPartyTransaction $relatedPartyTransaction): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($relatedParty->company_id === $company->id, 404);
        abort_unless($relatedPartyTransaction->related_party_id === $relatedParty->id, 404);

        $relatedPartyTransaction->delete();

        return redirect()->route('companies.related-parties.show', [$company, $relatedParty])
            ->with('success', 'Transaction removed.');
    }

    private function authorizeCompany(Company $company): void
    {
        abort_unless($company->user_id === auth()->id(), 403);
    }
}
