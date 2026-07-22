<?php

namespace App\Imports;

use App\Models\ChartOfAccount;
use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TrialBalanceImport implements ToCollection, WithHeadingRow, WithValidation
{
    public int $created  = 0;
    public int $updated  = 0;
    public int $skipped  = 0;
    public array $errors = [];

    private static array $TYPE_MAP = [
        'assets'      => 'assets',
        'asset'       => 'assets',
        'liabilities' => 'liabilities',
        'liability'   => 'liabilities',
        'equity'      => 'equity',
        'income'      => 'income',
        'revenue'     => 'income',
        'expenses'    => 'expenses',
        'expense'     => 'expenses',
    ];

    private static array $DEBIT_NORMAL = ['assets', 'expenses'];

    public function __construct(private readonly Company $company) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            $code = trim((string) ($row['code'] ?? $row['account_code'] ?? ''));
            $name = trim((string) ($row['account_name'] ?? $row['name'] ?? ''));
            $type = strtolower(trim((string) ($row['type'] ?? '')));

            // Skip section headers and totals rows (no code, or code is a label)
            if ($code === '' || in_array(strtoupper($code), ['CODE', 'TOTAL', 'DIFFERENCE', 'ASSETS', 'LIABILITIES', 'EQUITY', 'INCOME', 'EXPENSES'])) {
                continue;
            }

            $debit  = (float) str_replace([',', ' '], '', $row['debit_r'] ?? $row['debit'] ?? 0);
            $credit = (float) str_replace([',', ' '], '', $row['credit_r'] ?? $row['credit'] ?? 0);

            $resolvedType = self::$TYPE_MAP[$type] ?? null;

            // Determine the signed opening balance from the debit/credit columns
            // Debit-normal accounts: positive = debit side, negative = credit side
            // Credit-normal accounts: positive = credit side, negative = debit side
            $isDebitNormal = $resolvedType ? in_array($resolvedType, self::$DEBIT_NORMAL) : ($debit >= $credit);
            $openingBalance = $isDebitNormal
                ? round($debit - $credit, 2)
                : round($credit - $debit, 2);

            $existing = ChartOfAccount::where('company_id', $this->company->id)
                ->where('account_code', $code)
                ->first();

            if ($existing) {
                $existing->opening_balance = $openingBalance;
                if ($name !== '' && $name !== $existing->account_name) {
                    $existing->account_name = $name;
                }
                $existing->save();
                $this->updated++;
            } else {
                if ($name === '') {
                    $this->errors[] = "Row " . ($i + 2) . ": account code {$code} not found and no name provided — skipped.";
                    $this->skipped++;
                    continue;
                }

                if (!$resolvedType) {
                    $this->errors[] = "Row " . ($i + 2) . ": unrecognised account type \"{$type}\" for {$code} — skipped.";
                    $this->skipped++;
                    continue;
                }

                ChartOfAccount::create([
                    'company_id'         => $this->company->id,
                    'account_code'       => $code,
                    'account_name'       => $name,
                    'account_type'       => $resolvedType,
                    'category'           => $this->defaultCategory($resolvedType),
                    'cash_flow_category' => $this->defaultCashFlow($resolvedType),
                    'is_contra'          => false,
                    'is_active'          => true,
                    'opening_balance'    => $openingBalance,
                ]);

                $this->created++;
            }
        }
    }

    public function rules(): array
    {
        return [];
    }

    private function defaultCategory(string $type): string
    {
        return match ($type) {
            'assets'      => 'Current Assets',
            'liabilities' => 'Current Liabilities',
            'equity'      => 'Equity',
            'income'      => 'Revenue',
            'expenses'    => 'Operating Expenses',
            default       => Str::title($type),
        };
    }

    private function defaultCashFlow(string $type): string
    {
        return match ($type) {
            'assets', 'liabilities' => 'operating',
            'equity'                => 'financing',
            default                 => 'operating',
        };
    }
}
