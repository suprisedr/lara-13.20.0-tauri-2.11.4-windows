<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Gemini)]
#[UseCheapestModel]
class EmailSummaryAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'TEXT'
        You are an accounting assistant that summarises supplier emails for a bookkeeping platform.

        Given the sender name, sender email, subject line and body of an email, produce a concise summary that helps an accountant quickly understand what the email is about.

        Focus on:
        - What the supplier is communicating (invoice, statement, quote, delivery, query, etc.)
        - Any monetary amounts, reference numbers, or due dates mentioned
        - Any action required from the recipient

        Keep the summary to 1-3 sentences. Be factual and concise. Do not add opinions or assumptions.
        If the email body is empty or contains only signatures/disclaimers, return a summary like "Empty email body" or "Email contains only a signature block".
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->string()->required(),
        ];
    }
}
