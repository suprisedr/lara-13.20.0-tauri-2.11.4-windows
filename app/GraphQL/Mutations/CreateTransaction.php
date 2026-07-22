<?php

namespace App\GraphQL\Mutations;

use App\Models\Company;
use App\Models\Transaction;
use App\Services\TransactionService;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class CreateTransaction
{
    public function __construct(protected TransactionService $transactionService) {}

    /**
     * @param  array{input: array<string, mixed>}  $args
     *
     * @throws ValidationException
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Transaction
    {
        $user = $context->user();

        /** @var Company $company */
        $company = Company::findOrFail($args['company_id']);

        if ($company->user_id !== $user->id) {
            abort(403, 'You do not own this company.');
        }

        $lines = array_map(function (array $line) {
            return [
                'chart_of_account_id' => $line['chart_of_account_id'],
                'customer_id' => $line['customer_id'] ?? null,
                'type' => strtolower($line['type']),
                'amount' => $line['amount'],
                'description' => $line['description'] ?? null,
                'is_vat_line' => (bool) ($line['is_vat_line'] ?? false),
                'vat_rate' => isset($line['vat_rate']) ? (float) $line['vat_rate'] : null,
            ];
        }, $args['lines']);

        try {
            return $this->transactionService->record($company, $user, [
                'transaction_date' => $args['transaction_date'],
                'description' => $args['description'],
                'reference' => $args['reference'] ?? null,
                'notes' => $args['notes'] ?? null,
                'source_document' => $args['source_document'] ?? null,
                'lines' => $lines,
            ]);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['input' => $e->getMessage()]);
        }
    }
}
