<?php

namespace App\Http\Controllers;

use App\Events\ActionUpdated;
use App\Models\Company;
use App\Models\CompanyAction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyActionController extends Controller
{
    public function index(Company $company, Request $request): View
    {
        $this->authorize($company);

        $tab = $request->input('tab', 'open');

        $query = $company->actions();

        if ($tab === 'resolved') {
            $query->resolved()->orderByDesc('resolved_at');
        } else {
            $query->open()
                ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")
                ->orderByDesc('created_at');
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        $items = $query->paginate(50);

        $open = $tab === 'open' ? $items : $company->actions()->open()->paginate(0);
        $resolved = $tab === 'resolved' ? $items : $company->actions()->resolved()->paginate(0);

        return view('companies.actions.index', compact('company', 'open', 'resolved'));
    }

    public function store(Company $company, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize($company);

        $validated = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'body'     => ['nullable', 'string'],
            'priority' => ['required', 'in:low,medium,high'],
        ]);

        $action = $company->actions()->create(array_merge($validated, [
            'source' => CompanyAction::SOURCE_MANUAL,
        ]));

        ActionUpdated::dispatch($company->id, 'created', [
            'id'         => $action->id,
            'title'      => $action->title,
            'body'       => $action->body,
            'priority'   => $action->priority,
            'source'     => $action->source,
            'created_at' => $action->created_at->format('g:i A'),
            'date_label' => $action->created_at->isToday() ? 'Today' : $action->created_at->format('d M Y'),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'action'  => [
                    'id'         => $action->id,
                    'title'      => $action->title,
                    'body'       => $action->body,
                    'priority'   => $action->priority,
                    'source'     => $action->source,
                    'created_at' => $action->created_at->format('g:i A'),
                    'date_label' => $action->created_at->isToday() ? 'Today' : $action->created_at->format('d M Y'),
                ],
            ]);
        }

        return redirect()->route('companies.actions.index', $company)
            ->with('success', 'Action added.');
    }

    public function resolve(Company $company, CompanyAction $action): JsonResponse
    {
        $this->authorize($company);
        abort_unless($action->company_id === $company->id, 404);

        $action->update(['resolved_at' => now()]);

        ActionUpdated::dispatch($company->id, 'resolved', ['id' => $action->id]);

        return response()->json(['success' => true]);
    }

    public function reopen(Company $company, CompanyAction $action): JsonResponse
    {
        $this->authorize($company);
        abort_unless($action->company_id === $company->id, 404);

        $action->update(['resolved_at' => null]);

        ActionUpdated::dispatch($company->id, 'reopened', [
            'id'         => $action->id,
            'title'      => $action->title,
            'body'       => $action->body,
            'priority'   => $action->priority,
            'source'     => $action->source,
            'created_at' => $action->created_at->format('g:i A'),
            'date_label' => $action->created_at->isToday() ? 'Today' : $action->created_at->format('d M Y'),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(Company $company, CompanyAction $action, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize($company);
        abort_unless($action->company_id === $company->id, 404);

        $actionId = $action->id;
        $action->delete();

        ActionUpdated::dispatch($company->id, 'deleted', ['id' => $actionId]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('companies.actions.index', $company)
            ->with('success', 'Action removed.');
    }

    public function export(Company $company, Request $request)
    {
        $this->authorize($company);

        $tab    = $request->input('tab', 'all');
        $format = $request->input('format', 'xlsx');

        $query = $company->actions()->orderByDesc('created_at');

        if ($tab === 'open') {
            $query->open();
        } elseif ($tab === 'resolved') {
            $query->resolved();
        }

        $actions  = $query->get();
        $tabLabel = match ($tab) {
            'open'     => 'Open',
            'resolved' => 'Resolved',
            default    => 'All',
        };

        $baseName = $company->slug . '-actions-' . now()->format('Ymd');

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($actions, $company) {
                $handle = fopen('php://output', 'w');
                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv($handle, [$company->registered_name]);
                fputcsv($handle, ['Company Actions Export']);
                fputcsv($handle, ['Exported: ' . now()->format('d M Y H:i')]);
                fputcsv($handle, []);
                fputcsv($handle, ['Title', 'Priority', 'Source', 'Status', 'Details', 'Related', 'Created', 'Resolved']);

                foreach ($actions as $action) {
                    fputcsv($handle, [
                        $action->title,
                        ucfirst($action->priority),
                        match ($action->source) {
                            'ai-agent' => 'AI Agent',
                            'system'   => 'System',
                            default    => 'Manual',
                        },
                        $action->isResolved() ? 'Resolved' : 'Open',
                        $action->body ?? '',
                        $action->related_type ? ($action->related_type . ($action->related_id ? ' #' . $action->related_id : '')) : '',
                        $action->created_at->format('d M Y H:i'),
                        $action->resolved_at?->format('d M Y H:i') ?? '',
                    ]);
                }

                fclose($handle);
            }, $baseName . '.csv', ['Content-Type' => 'text/csv']);
        }

        if ($format === 'pdf') {
            return Pdf::loadView('pdf.actions', compact('company', 'actions', 'tabLabel'))
                ->setPaper('a4', 'landscape')
                ->stream($baseName . '.pdf');
        }

        $export = new \App\Exports\ActionsExport($actions, $company->registered_name, $tabLabel);

        return \Maatwebsite\Excel\Facades\Excel::download(
            $export,
            $baseName . '.xlsx',
            \Maatwebsite\Excel\Excel::XLSX,
        );
    }

    private function authorize(Company $company): void
    {
        abort_unless($company->user_id === auth()->id(), 403);
    }
}
