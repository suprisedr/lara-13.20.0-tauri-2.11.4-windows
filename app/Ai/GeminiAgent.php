<?php

namespace App\Ai;

use App\Ai\Concerns\HasProviderFallback;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

abstract class GeminiAgent implements Agent, HasStructuredOutput
{
    use Promptable, HasProviderFallback;

    protected string $systemPrompt = '';

    public function instructions(): string
    {
        return $this->systemPrompt;
    }

    abstract protected function rawSchema(): array;

    public function schema(JsonSchema $schema): array
    {
        $raw = $this->rawSchema();
        $result = [];

        foreach ($raw['properties'] ?? [] as $name => $prop) {
            $type = $prop['type'] ?? 'string';
            $desc = $prop['description'] ?? '';
            $required = in_array($name, $raw['required'] ?? []);

            $field = match ($type) {
                'integer' => $schema->integer()->description($desc),
                'number'  => $schema->number()->description($desc),
                'boolean' => $schema->boolean()->description($desc),
                default   => $schema->string()->description($desc),
            };

            if ($required) {
                $field = $field->required();
            } else {
                $field = $field->nullable();
            }

            $result[$name] = $field;
        }

        return $result;
    }
}
