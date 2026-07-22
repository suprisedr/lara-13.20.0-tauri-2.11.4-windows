<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\GroupElimination;
use App\Models\GroupEliminationLine;
use App\Models\SubsidiaryOwnershipEvent;
use App\Services\ConsolidationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function __construct(private ConsolidationService $consolidation)
    {
    }

    /** Subsidiaries register (index). */
    public function structure(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $subsidiaries = $company->subsidiaries()->orderBy('registered_name')->get();

        $available = Company::where('user_id', auth()->id())
            ->where('id', '!=', $company->id)
            ->whereNull('parent_company_id')
            ->where('is_consolidation_parent', false)
            ->orderBy('registered_name')
            ->get();

        $consolidation = $this->consolidation;
        $asOfDate = now()->toDateString();

        return view('companies.group.structure', compact('company', 'subsidiaries', 'available', 'asOfDate'));
    }

    /** Subsidiary profile (show). */
    public function showSubsidiary(Company $company, Company $subsidiary): View
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($subsidiary->parent_company_id === $company->id, 404);

        $events = $subsidiary->ownershipEventsAsSubsidiary()
            ->where('parent_company_id', $company->id)
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->get();

        $eliminations = $company->groupEliminations()->with('lines')->get();

        $available = Company::where('user_id', auth()->id())
            ->where('id', '!=', $company->id)
            ->where('is_consolidation_parent', false)
            ->orderBy('registered_name')
            ->get();

        $isDisposed = $subsidiary->control_lost_date !== null;
        $ownership = (float) ($subsidiary->group_ownership_percentage ?? 0);

        return view('companies.group.show', compact(
            'company', 'subsidiary', 'events', 'eliminations',
            'available', 'isDisposed', 'ownership',
        ));
    }

    /** Flag this company as a group parent. */
    public function enable(Company $company): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $company->update(['is_consolidation_parent' => true]);

        return redirect()->route('companies.group.structure', $company)
            ->with('success', 'Group consolidation enabled. Add subsidiaries to build the group.');
    }

    /** Attach an existing company as an investment/associate/JV/subsidiary. */
    public function addSubsidiary(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $data = $request->validate([
            'subsidiary_id' => 'required|integer|exists:companies,id',
            'group_ownership_percentage' => 'required|numeric|min:0.01|max:100',
            'is_joint_venture' => 'nullable|boolean',
            'acquisition_date' => 'nullable|date',
            'investment_cost' => 'nullable|numeric|min:0',
            'equity_at_acquisition' => 'nullable|numeric',
        ]);

        $subsidiary = Company::where('user_id', auth()->id())
            ->whereNull('parent_company_id')
            ->where('is_consolidation_parent', false)
            ->findOrFail($data['subsidiary_id']);

        abort_if($subsidiary->id === $company->id, 422);

        $isJv = (bool) ($data['is_joint_venture'] ?? false);
        $relType = Company::classifyRelationship((float) $data['group_ownership_percentage'], $isJv);

        $subsidiary->update([
            'parent_company_id' => $company->id,
            'group_ownership_percentage' => $data['group_ownership_percentage'],
            'is_joint_venture' => $isJv,
            'relationship_type' => $relType,
            'acquisition_date' => $data['acquisition_date'] ?? null,
            'investment_cost' => $data['investment_cost'] ?? null,
            'equity_at_acquisition' => $data['equity_at_acquisition'] ?? null,
        ]);

        $company->update(['is_consolidation_parent' => true]);

        $label = Company::RELATIONSHIP_TYPES[$relType]['label'] ?? $relType;
        return redirect()->route('companies.group.structure', $company)
            ->with('success', $subsidiary->registered_name . ' added as ' . lcfirst($label) . '.');
    }

    /** Update an existing investment's terms. */
    public function updateSubsidiary(Company $company, Company $subsidiary, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($subsidiary->parent_company_id === $company->id, 404);

        $data = $request->validate([
            'group_ownership_percentage' => 'required|numeric|min:0.01|max:100',
            'is_joint_venture' => 'nullable|boolean',
            'acquisition_date' => 'nullable|date',
            'investment_cost' => 'nullable|numeric|min:0',
            'equity_at_acquisition' => 'nullable|numeric',
        ]);

        $isJv = (bool) ($data['is_joint_venture'] ?? $subsidiary->is_joint_venture);
        $data['is_joint_venture'] = $isJv;
        $data['relationship_type'] = Company::classifyRelationship((float) $data['group_ownership_percentage'], $isJv);

        $subsidiary->update($data);

        return redirect()->route('companies.group.subsidiaries.show', [$company, $subsidiary])
            ->with('success', $subsidiary->registered_name . ' updated.');
    }

    /** Detach a subsidiary from the group. */
    public function removeSubsidiary(Company $company, Company $subsidiary): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($subsidiary->parent_company_id === $company->id, 404);

        $subsidiary->update([
            'parent_company_id' => null,
            'group_ownership_percentage' => null,
            'acquisition_date' => null,
            'investment_cost' => null,
            'equity_at_acquisition' => null,
        ]);

        return redirect()->route('companies.group.structure', $company)
            ->with('success', $subsidiary->registered_name . ' removed from the group.');
    }

    /** Consolidated statement of financial position. */
    public function balanceSheet(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($company->isGroupParent(), 404);

        $asOfDate = $request->input('as_of_date', now()->toDateString());
        $rounding = $this->rounding($request);
        $data = $this->consolidation->balanceSheet($company, $asOfDate);

        return view('companies.group.balance-sheet', array_merge($data, compact('company', 'rounding')));
    }

    /** Consolidated statement of profit or loss. */
    public function incomeStatement(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($company->isGroupParent(), 404);

        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $rounding = $this->rounding($request);
        $data = $this->consolidation->incomeStatement($company, $startDate, $endDate);

        return view('companies.group.income-statement', array_merge($data, compact('company', 'rounding')));
    }

    /** Manage manual intragroup elimination journals. */
    public function eliminations(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $eliminations = $company->groupEliminations()->with('lines')->get();

        return view('companies.group.eliminations', compact('company', 'eliminations'));
    }

    public function storeElimination(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $data = $request->validate([
            'elimination_date' => 'required|date',
            'type' => 'required|in:' . implode(',', array_keys(GroupElimination::TYPES)),
            'description' => 'required|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.bucket' => 'required|in:' . implode(',', array_keys(GroupEliminationLine::BUCKETS)),
            'lines.*.label' => 'nullable|string|max:255',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($company, $data) {
            $elimination = $company->groupEliminations()->create([
                'elimination_date' => $data['elimination_date'],
                'type' => $data['type'],
                'description' => $data['description'],
            ]);

            foreach ($data['lines'] as $line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);
                if ($debit == 0 && $credit == 0) {
                    continue;
                }
                $elimination->lines()->create([
                    'bucket' => $line['bucket'],
                    'label' => $line['label'] ?? null,
                    'debit' => $debit,
                    'credit' => $credit,
                ]);
            }
        });

        return redirect()->route('companies.group.eliminations', $company)
            ->with('success', 'Elimination journal recorded.');
    }

    public function destroyElimination(Company $company, GroupElimination $elimination): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($elimination->company_id === $company->id, 404);

        $elimination->delete();

        return redirect()->route('companies.group.eliminations', $company)
            ->with('success', 'Elimination journal removed.');
    }

    /** Ownership-change events (acquisition, increase, decrease, disposal). */
    public function ownershipEvents(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $events = $company->ownershipEvents()->with('subsidiary')->get();
        $subsidiaries = $company->subsidiaries()->orderBy('registered_name')->get();

        // Companies that could be acquired (not the parent, not already linked here).
        $available = Company::where('user_id', auth()->id())
            ->where('id', '!=', $company->id)
            ->where('is_consolidation_parent', false)
            ->orderBy('registered_name')
            ->get();

        return view('companies.group.ownership-events', compact('company', 'events', 'subsidiaries', 'available'));
    }

    public function storeOwnershipEvent(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $data = $request->validate([
            'subsidiary_company_id' => 'required|integer|exists:companies,id',
            'event_date' => 'required|date',
            'type' => 'required|in:' . implode(',', array_keys(SubsidiaryOwnershipEvent::TYPES)),
            'ownership_before' => 'required|numeric|min:0|max:100',
            'ownership_after' => 'required|numeric|min:0|max:100',
            'consideration' => 'nullable|numeric|min:0',
            'equity_at_event' => 'nullable|numeric',
            'fair_value_previously_held' => 'nullable|numeric|min:0',
            'carrying_previously_held' => 'nullable|numeric|min:0',
            'fair_value_retained' => 'nullable|numeric|min:0',
            'goodwill_derecognised' => 'nullable|numeric',
            'notes' => 'nullable|string|max:255',
        ]);

        $subsidiary = Company::where('user_id', auth()->id())->findOrFail($data['subsidiary_company_id']);
        abort_if($subsidiary->id === $company->id, 422);

        DB::transaction(function () use ($company, $subsidiary, $data) {
            $company->ownershipEvents()->create(array_merge($data, [
                'subsidiary_company_id' => $subsidiary->id,
                'consideration' => $data['consideration'] ?? 0,
            ]));

            $company->update(['is_consolidation_parent' => true]);
            $this->syncSubsidiaryState($company, $subsidiary);
        });

        return redirect()->route('companies.group.subsidiaries.show', [$company, $subsidiary])
            ->with('success', 'Ownership event recorded for ' . $subsidiary->registered_name . '.');
    }

    public function destroyOwnershipEvent(Company $company, SubsidiaryOwnershipEvent $event): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($event->parent_company_id === $company->id, 404);

        $subsidiary = $event->subsidiary;

        DB::transaction(function () use ($company, $event, $subsidiary) {
            $event->delete();
            if ($subsidiary) {
                $this->syncSubsidiaryState($company, $subsidiary);
            }
        });

        return redirect()->route('companies.group.structure', $company)
            ->with('success', 'Ownership event removed.');
    }

    /**
     * Recompute a subsidiary's denormalised group state by replaying its
     * ownership events in date order. Keeps the snapshot columns (used for
     * eliminations and quick reads) consistent with the event ledger.
     */
    private function syncSubsidiaryState(Company $parent, Company $subsidiary): void
    {
        $events = $subsidiary->ownershipEventsAsSubsidiary()
            ->where('parent_company_id', $parent->id)
            ->orderBy('event_date')->orderBy('id')
            ->get();

        if ($events->isEmpty()) {
            return;
        }

        $investment = 0.0;
        $controlAcquired = null;
        $controlLost = null;
        $equityAtAcquisition = null;
        $ownership = 0.0;

        foreach ($events as $e) {
            switch ($e->type) {
                case 'acquisition':
                    $controlAcquired = $e->event_date;
                    $controlLost = null;
                    $equityAtAcquisition = $e->equity_at_event;
                    $investment += (float) $e->consideration + (float) ($e->fair_value_previously_held ?? 0);
                    break;
                case 'increase':
                    $investment += (float) $e->consideration;
                    break;
                case 'decrease':
                    if ((float) $e->ownership_before > 0) {
                        $investment *= (float) $e->ownership_after / (float) $e->ownership_before;
                    }
                    break;
                case 'disposal':
                    $controlLost = $e->event_date;
                    $investment = (float) ($e->fair_value_retained ?? 0);
                    break;
            }
            $ownership = (float) $e->ownership_after;
        }

        $subsidiary->update([
            'parent_company_id' => $parent->id,
            'group_ownership_percentage' => $ownership,
            'relationship_type' => Company::classifyRelationship($ownership, (bool) $subsidiary->is_joint_venture),
            'investment_cost' => $investment,
            'equity_at_acquisition' => $equityAtAcquisition,
            'control_acquired_date' => $controlAcquired,
            'control_lost_date' => $controlLost,
            'acquisition_date' => $controlAcquired,
        ]);
    }

    private function rounding(Request $request): int
    {
        return in_array((int) $request->input('rounding', 1), [1, 1000, 1000000])
            ? (int) $request->input('rounding', 1)
            : 1;
    }
}
