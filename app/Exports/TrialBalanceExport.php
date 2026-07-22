<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrialBalanceExport implements FromArray, WithColumnFormatting, WithColumnWidths, WithStyles, WithTitle
{
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
        $fmt = $this->rounding === 1 ? '#,##0.00' : '#,##0';
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
                strtoupper($typeLabels[$type] ?? $type),
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
            'TOTAL', '', '',
            $totalDebit / $this->rounding,
            $totalCredit / $this->rounding,
        ];

        $diff = abs($totalDebit - $totalCredit);
        if (round($diff, 2) > 0) {
            $rows[] = [
                'DIFFERENCE', '', '', '',
                $diff / $this->rounding,
            ];
        }

        $this->lastRow = count($rows);

        return $rows;
    }

    public function styles(Worksheet $sheet): void
    {
        $h        = $this->hRow;
        $dataFrom = $h + 1;
        $last     = $this->lastRow;
        $hasDiff  = str_contains($this->rows[$last - 1][0] ?? '', 'DIFFERENCE');
        $totalsRow = $hasDiff ? $last - 1 : $last;

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)
            ->getColor()->setARGB('FF1B1B18');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)
            ->getColor()->setARGB('FF000000');
        $sheet->getStyle('A3:A4')->getFont()->setSize(9)
            ->getColor()->setARGB('FF6B7280');
        $sheet->getStyle("A4:E4")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB('FFDDDDDD');

        $sheet->getStyle("A{$h}:E{$h}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF374151']],
            'alignment' => [
                'vertical'   => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'wrapText'   => true,
            ],
        ]);
        $sheet->getRowDimension($h)->setRowHeight(28);
        $sheet->getStyle("D{$h}:E{$h}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach ($this->sectionHeaderRows as $sr) {
            $sheet->getStyle("A{$sr}:E{$sr}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF000000']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF5F5F5']],
            ]);
            $sheet->getStyle("A{$sr}:E{$sr}")->getBorders()->getTop()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setARGB('FF000000');
            $sheet->getStyle("D{$sr}:E{$sr}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        if ($dataFrom <= $last) {
            for ($r = $dataFrom; $r <= $last; $r++) {
                if (in_array($r, $this->sectionHeaderRows)) {
                    continue;
                }
                if ($r === $totalsRow || ($hasDiff && $r === $last)) {
                    continue;
                }
                $fill = ($r % 2 === 0) ? 'FFF9FAFB' : 'FFFFFFFF';
                $sheet->getStyle("A{$r}:E{$r}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($fill);
                $sheet->getRowDimension($r)->setRowHeight(16);
            }

            $sheet->getStyle("A{$dataFrom}:E{$last}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setARGB('FFE5E7EB');

            $sheet->getStyle("D{$dataFrom}:E{$last}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("A{$dataFrom}:E{$last}")
                ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getStyle("A{$dataFrom}:A{$last}")->getFont()
                ->setName('Courier New')->setBold(true)->setSize(9);
        }

        $sheet->getStyle("A{$totalsRow}:E{$totalsRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF000000']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFAFAFA']],
        ]);
        $sheet->getStyle("A{$totalsRow}:E{$totalsRow}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_MEDIUM)
            ->getColor()->setARGB('FF000000');
        $sheet->getRowDimension($totalsRow)->setRowHeight(22);

        if ($hasDiff) {
            $sheet->getStyle("A{$last}:E{$last}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFB91C1C']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF5F5']],
            ]);
        }

        $sheet->freezePane("A{$dataFrom}");
    }
}
