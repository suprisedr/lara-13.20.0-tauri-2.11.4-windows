<?php

namespace App\Services;

use App\Models\AgentActionHistory;

class AgentHistoryService
{
    public function recall(string $agentType, string $entityType, int $entityId): ?string
    {
        $history = AgentActionHistory::where('agent_type', $agentType)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->first();

        if (! $history) {
            return null;
        }

        return "--- PREVIOUS ACTION CONTEXT ---\n"
            ."The last time you posted for this {$entityType} (ID {$entityId}), "
            ."here is what happened:\n\n"
            ."Prompt given:\n{$history->last_prompt}\n\n"
            ."Your response:\n{$history->last_response}\n"
            ."--- END PREVIOUS ACTION CONTEXT ---\n\n"
            ."Use the above as context to maintain consistency in account selection and posting patterns. "
            ."Now handle the current action:";
    }

    /**
     * @param  array<int, array{agent_type: string, entity_type: string, entity_id: int, label: string}>  $related
     */
    public function recallWithRelated(string $agentType, string $entityType, int $entityId, array $related = []): ?string
    {
        $own = $this->recall($agentType, $entityType, $entityId);

        if (empty($related)) {
            return $own;
        }

        $keys = array_map(fn (array $r) => [
            'agent_type'  => $r['agent_type'],
            'entity_type' => $r['entity_type'],
            'entity_id'   => $r['entity_id'],
        ], $related);

        $rows = AgentActionHistory::where(function ($q) use ($keys) {
            foreach ($keys as $key) {
                $q->orWhere(function ($q2) use ($key) {
                    $q2->where('agent_type', $key['agent_type'])
                       ->where('entity_type', $key['entity_type'])
                       ->where('entity_id', $key['entity_id']);
                });
            }
        })->get();

        if ($rows->isEmpty()) {
            return $own;
        }

        $relatedText = "--- RELATED ENTITY CONTEXT ---\n";
        foreach ($rows as $row) {
            $label = collect($related)->first(fn ($r) =>
                $r['agent_type'] === $row->agent_type
                && $r['entity_type'] === $row->entity_type
                && $r['entity_id'] === $row->entity_id
            )['label'] ?? "{$row->entity_type} #{$row->entity_id}";

            $relatedText .= "[{$label}]\n"
                ."Prompt: {$row->last_prompt}\n"
                ."Response: {$row->last_response}\n\n";
        }
        $relatedText .= "--- END RELATED ENTITY CONTEXT ---\n\n"
            ."The above shows what other agents posted for related entities. "
            ."Use consistent accounts where applicable.\n";

        if ($own) {
            return $own."\n\n".$relatedText;
        }

        return $relatedText;
    }

    public function remember(string $agentType, string $entityType, int $entityId, string $prompt, string $response): void
    {
        AgentActionHistory::updateOrCreate(
            [
                'agent_type'  => $agentType,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
            ],
            [
                'last_prompt'   => $prompt,
                'last_response' => $response,
            ],
        );
    }
}
