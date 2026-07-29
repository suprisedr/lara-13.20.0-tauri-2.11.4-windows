<?php

namespace App\Ai\Agents;

use App\Ai\Concerns\HasProviderFallback;
use App\Models\Company;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Creates a single missing chart-of-account entry on demand — used when a
 * posting agent (e.g. InvoicePostingAgent) needs an account that doesn't
 * exist yet in the company's chart of accounts. The account_type and, for
 * expenses, the IFRS expense band are fixed by the caller (they're implied
 * by the posting role being filled, e.g. "sales account"); this agent only
 * decides the name/category/description and whether the account belongs
 * under an existing group.
 */
class CreateChartOfAccountAgent implements Agent, HasStructuredOutput
{
    use Promptable, HasProviderFallback;

    public function __construct(public readonly Company $company) {}

    public function instructions(): Stringable|string
    {
        return <<<'TEXT'
        You are a chartered accountant adding ONE missing account to an
        existing chart of accounts so a transaction can be posted.

        You will be given the account_type (and, for expenses, the IFRS
        expense band) that the new account MUST use — do not change it —
        plus a short description of what the account is for, and a list of
        this company's existing top-level (group) accounts of that same
        type for context.

        Decide:
        - account_name: a clear, specific name (e.g. "Workshop Labour
          Revenue", not just "Revenue").
        - category: a short grouping label consistent with similar existing
          accounts, or null.
        - description: one sentence describing what gets posted to this
          account, or null.
        - is_contra: true only if this is a contra account under IFRS
          (e.g. Accumulated Depreciation, Allowance for Credit Losses,
          Drawings) — false by default.
        - parent_code: if one of the supplied existing group accounts is a
          good fit for this new account to nest under as a child, return
          its account_code. Otherwise return null and it will be created
          as its own new top-level group.

        Never invent an account_code — that is assigned automatically.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'account_name' => $schema->string()->required(),
            'category' => $schema->string()->nullable(),
            'description' => $schema->string()->nullable(),
            'is_contra' => $schema->boolean()->required(),
            'parent_code' => $schema->string()
                ->description('An existing account_code from the supplied list to nest this account under, or null to create it as a new top-level account.')
                ->nullable(),
        ];
    }
}
