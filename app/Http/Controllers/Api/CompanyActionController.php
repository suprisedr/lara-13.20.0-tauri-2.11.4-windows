<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyActionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id'   => ['required', 'integer', 'exists:companies,id'],
            'title'        => ['required', 'string', 'max:255'],
            'body'         => ['nullable', 'string'],
            'priority'     => ['nullable', 'in:low,medium,high'],
            'related_type' => ['nullable', 'string', 'max:60'],
            'related_id'   => ['nullable', 'integer'],
        ]);

        $company = Company::findOrFail($validated['company_id']);

        abort_unless($company->user_id === $request->user()->id, 403);

        $action = $company->actions()->create([
            'title'        => $validated['title'],
            'body'         => $validated['body'] ?? null,
            'priority'     => $validated['priority'] ?? CompanyAction::PRIORITY_MEDIUM,
            'source'       => CompanyAction::SOURCE_AI_AGENT,
            'related_type' => $validated['related_type'] ?? null,
            'related_id'   => $validated['related_id'] ?? null,
        ]);

        return response()->json([
            'data' => [
                'id'           => $action->id,
                'title'        => $action->title,
                'body'         => $action->body,
                'priority'     => $action->priority,
                'source'       => $action->source,
                'related_type' => $action->related_type,
                'related_id'   => $action->related_id,
                'created_at'   => $action->created_at->toIso8601String(),
            ],
        ], 201);
    }

    public function update(Request $request, CompanyAction $action): JsonResponse
    {
        $company = $action->company;

        abort_unless($company->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'title'        => ['sometimes', 'string', 'max:255'],
            'body'         => ['nullable', 'string'],
            'priority'     => ['sometimes', 'in:low,medium,high'],
            'resolved'     => ['sometimes', 'boolean'],
            'related_type' => ['nullable', 'string', 'max:60'],
            'related_id'   => ['nullable', 'integer'],
        ]);

        if (array_key_exists('resolved', $validated)) {
            $validated['resolved_at'] = $validated['resolved'] ? now() : null;
            unset($validated['resolved']);
        }

        $action->update($validated);

        return response()->json([
            'data' => [
                'id'           => $action->id,
                'title'        => $action->title,
                'body'         => $action->body,
                'priority'     => $action->priority,
                'source'       => $action->source,
                'related_type' => $action->related_type,
                'related_id'   => $action->related_id,
                'resolved_at'  => $action->resolved_at?->toIso8601String(),
                'created_at'   => $action->created_at->toIso8601String(),
            ],
        ]);
    }

    public function destroy(Request $request, CompanyAction $action): JsonResponse
    {
        $company = $action->company;

        abort_unless($company->user_id === $request->user()->id, 403);

        $action->delete();

        return response()->json(['message' => 'Action deleted.']);
    }
}
