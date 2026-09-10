<?php

namespace App\Exports;

use App\Exports\Concerns\AfsExcelDesign;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrialBalanceExport implements FromArray, WithColumnFormatting, WithColumnWidths, WithStyles, WithTitle
{
    use AfsExcelDesign;

    private array $rows = [];
    private int $hRow = 5;
    private int $lastRow = 0;
    private array $sectionHeaderRows = [];

    public function __construct(
        private readonly Collection $accounts,
        private readonly string     $companyName,
        private readonly string     $periodLabel,
        private readonly int        $rounding = 1,
    ) {
        $this->rows = $this->build();
    }

    public function title(): string { return 'Trial Balance'; }

    public function array(): array { return $this->rows; }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 38,
            'C' => 16,
            'D' => 18,
            'E' => 18,
        ];
    }

    public function columnFormats(): array
    {
        $fmt = $this->afsNumberFormat($this->rounding);

        return [
            'D' => $fmt,
            'E' => $fmt,
        ];
    }

    private function build(): array
    {
        $roundingLabel = match ($this->rounding) {
            1000    => "R'000",
            1000000 => "R'm",
            default => 'R',
        };

        $rows = [];
        $rows[] = [$this->companyName];
        $rows[] = ['Trial Balance'];
        $rows[] = [$this->periodLabel];
        $rows[] = ['Exported: ' . now()->format('d M Y H:i')];

        $rows[] = ['Code', 'Account Name', 'Type', "Debit ({$roundingLabel})", "Credit ({$roundingLabel})"];

        $debitNormal = ['assets', 'expenses'];
        $typeLabels  = [
            'assets'      => 'Assets',
            'liabilities' => 'Liabilities',
            'equity'      => 'Equity',
            'income'      => 'Income',
            'expenses'    => 'Expenses',
        ];
        $typeOrder = ['assets', 'liabilities', 'equity', 'income', 'expenses'];
        $grouped   = $this->accounts->groupBy('account_type');

        $totalDebit  = 0.0;
        $totalCredit = 0.0;

        foreach ($typeOrder as $type) {
            if (! $grouped->has($type)) {
                continue;
            }

            $sectDebit  = 0.0;
            $sectCredit = 0.0;
            $sectionRows = [];

            foreach ($grouped[$type] as $account) {
                $opening  = (float) $account->opening_balance;
                $pDebits  = (float) $account->posted_debits;
                $pCredits = (float) $account->posted_credits;
                $isDebitNormal = in_array($type, $debitNormal);
                $balance = $isDebitNormal
                    ? $opening + $pDebits - $pCredits
                    : $opening + $pCredits - $pDebits;

                if ($isDebitNormal) {
                    $d = $balance >= 0 ? $balance : 0;
                    $c = $balance < 0 ? abs($balance) : 0;
                } else {
                    $c = $balance >= 0 ? $balance : 0;
                    $d = $balance < 0 ? abs($balance) : 0;
                }

                $sectionRows[] = [
                    $account->account_code,
                    $account->account_name,
                    $typeLabels[$type] ?? $type,
                    $d / $this->rounding,
                    $c / $this->rounding,
                ];

                $sectDebit  += $d;
                $sectCredit += $c;
            }

            $this->sectionHeaderRows[] = count($rows) + 1;
            $rows[] = [
                // Sentence case: the source document uses no uppercase
                // anywhere, and emphasis here is weight and colour.
                $typeLabels[$type] ?? $type,
                '', '',
                $sectDebit / $this->rounding,
                $sectCredit / $this->rounding,
            ];

            foreach ($sectionRows as $sr) {
                $rows[] = $sr;
            }

            $totalDebit  += $sectDebit;
            $totalCredit += $sectCredit;
        }

        $rows[] = [
            'Total', '', '',
            $totalDebit / $this->rounding,
            $totalCredit / $this->rounding,
        ];

        $diff = abs($totalDebit - $totalCredit);
        if (round($diff, 2) > 0) {
            $rows[] = [
                'Difference — the trial balance does not balance', '', '', '',
                $diff / $this->rounding,
            ];
        }

        $this->lastRow = count($rows);

        return $rows;
    }

    public function styles(Worksheet $sheet): void
    {
        $h = $this->hRow;
        $dataFrom = $h + 1;
        $last = $this->lastRow;
        $hasDiff = str_starts_with($this->rows[$last - 1][0] ?? '', 'Difference');
        $totalsRow = $hasDiff ? $last - 1 : $last;

        $this->afsBaseSheet($sheet);
        $this->afsTitleBlock($sheet, $this->companyName, 'Trial Balance', $this->periodLabel, 'A4');
        $this->afsFootnoteRow($sheet, 4, 'A', 'E');

        // Debit and credit are a balancing pair with no primary side, so no
        // column is banded — the same reasoning that leaves the statement of
        // changes in equity untinted. Hence no band columns passed here.
        $this->afsHeaderRow($sheet, $h, 'A', 'E', [], ['D', 'E']);

        $this->afsBodyRange($sheet, "A{$dataFrom}:E{$last}", $dataFrom, $last);
        $this->afsFigureColumn($sheet, 'D', $dataFrom, $last, $this->rounding);
        $this->afsFigureColumn($sheet, 'E', $dataFrom, $last, $this->rounding);

        // Section heads carry the emphasis treatment and a closing hairline;
        // no fill, and no rule above — the document rules under a row, never
        // over it.
        foreach ($this->sectionHeaderRows as $sr) {
            $this->afsEmphasisRow($sheet, $sr, 'A', 'E');
            $this->afsRuleRow($sheet, $sr, 'A', 'E');
        }

        $this->afsEmphasisRow($sheet, $totalsRow, 'A', 'E');
        $this->afsRuleRow($sheet, $totalsRow, 'A', 'E');

        // A trial balance that does not balance is a genuine failure state,
        // and #9D174D is the palette's one status colour — see the reversal
        // badge. It is not decoration and should not be repurposed.
        if ($hasDiff) {
            $sheet->getStyle("A{$last}:E{$last}")->getFont()
                ->setName(self::AFS_FONT)
                ->setBold(true)
                ->getColor()->setARGB(self::AFS_ALERT);
        }

        $sheet->freezePane("A{$dataFrom}");
    }
}
