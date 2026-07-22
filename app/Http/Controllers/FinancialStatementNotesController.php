<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Company;
use App\Models\FinancialStatementNote;
use App\Models\IntangibleClass;
use App\Models\NoteAccountLink;
use App\Models\PpeClass;
use App\Models\PpeClassAccountLink;
use App\Services\FinancialStatementNotesSeeder;
use App\Services\IntangibleMovementService;
use App\Services\InventoryMovementService;
use App\Services\NoteFigureService;
use App\Services\PpeMovementService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinancialStatementNotesController extends Controller
{
    public function __construct(
        private readonly FinancialStatementNotesSeeder $seeder,
        private readonly PpeMovementService $ppeMovements,
        private readonly IntangibleMovementService $intangibleMovements,
        private readonly InventoryMovementService $inventoryMovements,
        private readonly NoteFigureService $noteFigures,
    ) {}

    public function index(Company $company): RedirectResponse
    {
        $this->authorize($company);

        $this->seeder->seed($company);

        $first = $company->financialStatementNotes()->orderBy('sort_order')->first();

        return redirect()->route('companies.notes-to-afs.show', [$company, $first]);
    }

    public function show(Company $company, FinancialStatementNote $note, Request $request): View
    {
        $this->authorize($company);
        $this->ensureNoteBelongsToCompany($note, $company);

        $this->seeder->seed($company);
        $note->refresh();

        $notes = $company->financialStatementNotes()->orderBy('sort_order')->get();

        $startDate = $request->input('start_date', $this->fyStartDate($company));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        // Accounts available for linking figures to a note (all notes).
        $availableAccounts = $company->chartOfAccounts()
            ->where('is_active', true)
            ->postable()
            ->orderBy('account_code')
            ->get();

        $noteLinks   = $note->accountLinks()->with('account')->get();
        $noteFigures = $this->noteFigures->figuresFor($company, $note, $startDate, $endDate);

        $data = [
            'company'           => $company,
            'notes'             => $notes,
            'note'              => $note,
            'startDate'         => $startDate,
            'endDate'           => $endDate,
            'availableAccounts' => $availableAccounts,
            'noteLinks'         => $noteLinks,
            'noteFigures'       => $noteFigures,
        ];

        if ($note->isPpe()) {
            $ppeClasses = $company->ppeClasses()->get();

            $movements = $this->ppeMovements->build($company, $startDate, $endDate);

            $assets = $company->assets()
                ->with('ppeClass')
                ->orderBy('acquisition_date')
                ->orderBy('name')
                ->get();

            $data += compact('ppeClasses', 'movements', 'assets');
        }

        if ($note->isIntangible()) {
            $intangibleClasses = $company->intangibleClasses()->get();

            $intangibleMovements = $this->intangibleMovements->build($company, $startDate, $endDate);

            $data += compact('intangibleClasses', 'intangibleMovements');
        }

        if ($note->isInventory()) {
            $inventoryMovements = $this->inventoryMovements->build($company, $startDate, $endDate);

            $data += compact('inventoryMovements');
        }

        return view('companies.notes-to-afs.show', $data);
    }

    public function updateText(Company $company, FinancialStatementNote $note, Request $request): RedirectResponse
    {
        $this->authorize($company);
        $this->ensureNoteBelongsToCompany($note, $company);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body'  => ['nullable', 'string'],
        ]);

        $note->update($validated);

        return redirect()
            ->route('companies.notes-to-afs.show', [$company, $note])
            ->with('success', 'Note saved.');
    }

    public function toggleIncludeInAfs(Company $company, FinancialStatementNote $note): RedirectResponse
    {
        $this->authorize($company);
        $this->ensureNoteBelongsToCompany($note, $company);

        $note->update(['include_in_afs' => ! $note->include_in_afs]);

        return redirect()->route('companies.notes-to-afs.show', [$company, $note]);
    }

    public function storeNoteLine(Company $company, FinancialStatementNote $note, Request $request): RedirectResponse
    {
        $this->authorize($company);
        $this->ensureNoteBelongsToCompany($note, $company);

        $validated = $request->validate([
            'chart_of_account_id' => [
                'required',
                'integer',
                Rule::exists('chart_of_accounts', 'id')->where('company_id', $company->id),
            ],
            'sign'          => ['required', 'in:1,-1'],
            'balance_point' => ['nullable', 'in:closing,opening'],
            'label'         => ['nullable', 'string', 'max:255'],
        ]);

        $maxSort = (int) $note->accountLinks()->max('sort_order');

        $balancePoint = $validated['balance_point'] ?? 'closing';

        NoteAccountLink::updateOrCreate(
            [
                'financial_statement_note_id' => $note->id,
                'chart_of_account_id'         => $validated['chart_of_account_id'],
                'balance_point'               => $balancePoint,
            ],
            [
                'sign'       => (int) $validated['sign'],
                'label'      => ($validated['label'] ?? null) ?: null,
                'sort_order' => $maxSort + 1,
            ],
        );

        return redirect()
            ->route('companies.notes-to-afs.show', [$company, $note])
            ->with('success', 'Account added to the note.');
    }

    public function destroyNoteLine(Company $company, FinancialStatementNote $note, NoteAccountLink $line): RedirectResponse
    {
        $this->authorize($company);
        $this->ensureNoteBelongsToCompany($note, $company);
        abort_unless($line->financial_statement_note_id === $note->id, 404);

        $line->delete();

        return redirect()
            ->route('companies.notes-to-afs.show', [$company, $note])
            ->with('success', 'Account removed from the note.');
    }

    public function storePpeClass(Company $company, Request $request): RedirectResponse
    {
        $this->authorize($company);

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:120'],
            'useful_life_years'   => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'depreciation_method' => ['nullable', 'string', 'max:30'],
        ]);

        $maxSort = (int) $company->ppeClasses()->max('sort_order');

        $company->ppeClasses()->create($validated + ['sort_order' => $maxSort + 1]);

        return $this->backToPpe($company)->with('success', 'PPE class added.');
    }

    public function updatePpeClass(Company $company, PpeClass $ppeClass, Request $request): RedirectResponse
    {
        $this->authorize($company);
        $this->ensurePpeClassBelongsToCompany($ppeClass, $company);

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:120'],
            'useful_life_years'   => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'depreciation_method' => ['nullable', 'string', 'max:30'],
        ]);

        $ppeClass->update($validated);

        return $this->backToPpe($company)->with('success', 'PPE class updated.');
    }

    public function destroyPpeClass(Company $company, PpeClass $ppeClass): RedirectResponse
    {
        $this->authorize($company);
        $this->ensurePpeClassBelongsToCompany($ppeClass, $company);

        $ppeClass->delete();

        return $this->backToPpe($company)->with('success', 'PPE class removed.');
    }

    /**
     * Sync the chart-of-accounts links for a PPE class.
     *
     * Accepts a flat map of role => chart_of_account_id (or empty for "no link").
     * Replaces existing PpeClassAccountLink rows for any role provided.
     * Empty/null values clear that role's link.
     */
    public function updatePpeClassLinks(Company $company, PpeClass $ppeClass, Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize($company);
        $this->ensurePpeClassBelongsToCompany($ppeClass, $company);

        $roles = array_keys(PpeClassAccountLink::roles());

        $rules = [];
        foreach ($roles as $role) {
            $rules["links.$role"] = [
                'nullable',
                Rule::exists('chart_of_accounts', 'id')->where('company_id', $company->id),
            ];
        }
        $data = $request->validate($rules);

        // Only touch roles that were explicitly present in the payload.
        // An empty/null value means "clear this role"; an absent key means "leave alone".
        $payloadLinks = $request->input('links', []);
        foreach ($roles as $role) {
            if (! array_key_exists($role, $payloadLinks)) {
                continue;
            }

            $accountId = $payloadLinks[$role] !== '' ? $payloadLinks[$role] : null;

            if ($accountId) {
                PpeClassAccountLink::updateOrCreate(
                    ['ppe_class_id' => $ppeClass->id, 'role' => $role],
                    ['chart_of_account_id' => (int) $accountId],
                );
            } else {
                PpeClassAccountLink::where('ppe_class_id', $ppeClass->id)
                    ->where('role', $role)
                    ->delete();
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true])->setStatusCode(200);
        }

        return $this->backToPpe($company)->with('success', "Linked accounts updated for {$ppeClass->name}.");
    }

    public function storeAsset(Company $company, Request $request): RedirectResponse
    {
        $this->authorize($company);

        $validated = $this->validateAsset($company, $request);

        $company->assets()->create($validated);

        return $this->backToPpe($company)->with('success', 'Asset added.');
    }

    public function updateAsset(Company $company, Asset $asset, Request $request): RedirectResponse
    {
        $this->authorize($company);
        $this->ensureAssetBelongsToCompany($asset, $company);

        $validated = $this->validateAsset($company, $request);

        $asset->update($validated);

        return $this->backToPpe($company)->with('success', 'Asset updated.');
    }

    public function destroyAsset(Company $company, Asset $asset): RedirectResponse
    {
        $this->authorize($company);
        $this->ensureAssetBelongsToCompany($asset, $company);

        $asset->delete();

        return $this->backToPpe($company)->with('success', 'Asset removed.');
    }

    /** @return array<string, mixed> */
    private function validateAsset(Company $company, Request $request): array
    {
        return $request->validate([
            'ppe_class_id' => [
                'nullable',
                'integer',
                Rule::exists('ppe_classes', 'id')->where('company_id', $company->id),
            ],
            'name'                => ['required', 'string', 'max:150'],
            'asset_tag'           => ['nullable', 'string', 'max:60'],
            'location'            => ['nullable', 'string', 'max:120'],
            'acquisition_date'    => ['required', 'date'],
            'cost'                => ['required', 'numeric', 'min:0'],
            'residual_value'      => ['nullable', 'numeric', 'min:0'],
            'useful_life_years'   => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'depreciation_method' => ['nullable', 'string', 'max:30'],
            'disposal_date'       => ['nullable', 'date'],
            'disposal_proceeds'   => ['nullable', 'numeric', 'min:0'],
            'notes'               => ['nullable', 'string'],
        ]);
    }

    private function ensureAssetBelongsToCompany(Asset $asset, Company $company): void
    {
        abort_unless($asset->company_id === $company->id, 404);
    }

    private function authorize(Company $company): void
    {
        abort_unless($company->user_id === auth()->id(), 403);
    }

    private function ensureNoteBelongsToCompany(FinancialStatementNote $note, Company $company): void
    {
        abort_unless($note->company_id === $company->id, 404);
    }

    private function ensurePpeClassBelongsToCompany(PpeClass $class, Company $company): void
    {
        abort_unless($class->company_id === $company->id, 404);
    }

    public function storeIntangibleClass(Company $company, Request $request): RedirectResponse
    {
        $this->authorize($company);

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:120'],
            'useful_life_years'   => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'amortisation_method' => ['nullable', 'string', 'max:30'],
            'indefinite_life'     => ['nullable'],
        ]);

        $validated['indefinite_life'] = ! empty($validated['indefinite_life']);
        $maxSort = (int) $company->intangibleClasses()->max('sort_order');
        $company->intangibleClasses()->create($validated + ['sort_order' => $maxSort + 1]);

        return $this->backToIntangible($company)->with('success', 'Intangible class added.');
    }

    public function updateIntangibleClass(Company $company, IntangibleClass $intangibleClass, Request $request): RedirectResponse
    {
        $this->authorize($company);
        abort_unless($intangibleClass->company_id === $company->id, 404);

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:120'],
            'useful_life_years'   => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'amortisation_method' => ['nullable', 'string', 'max:30'],
            'indefinite_life'     => ['nullable'],
        ]);

        $validated['indefinite_life'] = ! empty($validated['indefinite_life']);
        $intangibleClass->update($validated);

        return $this->backToIntangible($company)->with('success', 'Intangible class updated.');
    }

    public function destroyIntangibleClass(Company $company, IntangibleClass $intangibleClass): RedirectResponse
    {
        $this->authorize($company);
        abort_unless($intangibleClass->company_id === $company->id, 404);

        $intangibleClass->delete();

        return $this->backToIntangible($company)->with('success', 'Intangible class removed.');
    }

    private function backToPpe(Company $company): RedirectResponse
    {
        $ppeNote = $company->financialStatementNotes()
            ->where('kind', FinancialStatementNote::KIND_PPE)
            ->first();

        return $ppeNote
            ? redirect()->route('companies.notes-to-afs.show', [$company, $ppeNote])
            : redirect()->route('companies.notes-to-afs.index', $company);
    }

    private function backToIntangible(Company $company): RedirectResponse
    {
        $note = $company->financialStatementNotes()
            ->where('kind', FinancialStatementNote::KIND_INTANGIBLE)
            ->first();

        return $note
            ? redirect()->route('companies.notes-to-afs.show', [$company, $note])
            : redirect()->route('companies.notes-to-afs.index', $company);
    }

    private function fyStartDate(Company $company): string
    {
        $yearEndMonth = $company->financial_year_end_month ?? 12;
        $today = now();
        $fyStartMonth = ($yearEndMonth % 12) + 1;
        $fyStartYear = $today->month >= $fyStartMonth ? $today->year : $today->year - 1;

        return Carbon::create($fyStartYear, $fyStartMonth, 1)->format('Y-m-d');
    }
}
