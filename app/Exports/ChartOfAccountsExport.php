<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ChartOfAccountsExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Collection $accounts,
        private readonly string $companyName,
        private readonly string $periodLabel,
    ) {}

    public function sheets(): array
    {
        $typeOrder = ['assets', 'liabilities', 'equity', 'income', 'expenses'];
        $sheets    = [new CoaSummarySheet($this->accounts, $this->companyName, $this->periodLabel)];

        foreach ($typeOrder as $type) {
            $group = $this->accounts->where('account_type', $type)->sortBy('account_code')->values();
            if ($group->isNotEmpty()) {
                $sheets[] = new CoaTypeSheet($group, $type, $this->companyName, $this->periodLabel);
            }
        }

        return $sheets;
    }
}

// ── Shared letterhead + style helper ─────────────────────────────
trait CoaSheetStyle
{
    use \App\Exports\Concerns\AfsExcelDesign;

    private function applyLetterhead(Worksheet $sheet, string $companyName, string $reportTitle, string $periodLabel, string $lastCol, int $hRow): void
    {
        $dataFrom = $hRow + 1;
        $lastRow = $sheet->getHighestRow();
        $dataTo = $lastRow - 1;

        $this->afsBaseSheet($sheet);
        $this->afsTitleBlock($sheet, $companyName, $reportTitle, $periodLabel, 'A4');
        $this->afsFootnoteRow($sheet, 4, 'A', $lastCol);

        // The balance column is the primary one, so it takes the #005BF0
        // header cell and the band below; every other header cell is plain
        // with a black hairline.
        $this->afsHeaderRow($sheet, $hRow, 'A', $lastCol, [$lastCol], [$lastCol]);

        if ($dataFrom <= $dataTo) {
            // No alternating fill and no grid: the tint is a column band, and
            // the document rules rows rather than boxing every cell.
            $this->afsBodyRange($sheet, "A{$dataFrom}:{$lastCol}{$dataTo}", $dataFrom, $dataTo);
            $this->afsBandColumn($sheet, $lastCol, $dataFrom, $lastRow);
        }

        $this->afsEmphasisRow($sheet, $lastRow, 'A', $lastCol);
        $this->afsRuleRow($sheet, $lastRow, 'A', $lastCol);
        // The band carries through the totals row, so its bold black figure is
        // reapplied after the emphasis colour above.
        $this->afsBandColumn($sheet, $lastCol, $lastRow, $lastRow);
        $sheet->getRowDimension($lastRow)->setRowHeight(self::AFS_ROW_HEIGHT);

        $sheet->freezePane("A{$dataFrom}");
    }
}

// ── Summary sheet (all accounts) ─────────────────────────────────
class CoaSummarySheet implements FromArray, WithColumnFormatting, WithColumnWidths, WithStyles, WithTitle
{
    use CoaSheetStyle;

    private array $rows    = [];
    private int   $hRow    = 5;
    private int   $lastRow = 0;

    public function __construct(
        private readonly Collection $accounts,
        private readonly string $companyName,
        private readonly string $periodLabel,
    ) {
        $this->rows = $this->build();
    }

    public function title(): string { return 'Summary'; }

    public function array(): array { return $this->rows; }

    public function columnWidths(): array
    {
        return [
            'A' => 14, // Account Code
            'B' => 34, // Account Name
            'C' => 14, // Type
            'D' => 18, // Category
            'E' => 10, // Role
            'F' => 38, // Description
            'G' => 8,  // Active
            'H' => 18, // Balance
        ];
    }

    public function columnFormats(): array
    {
        return ['H' => $this->afsNumberFormat()];
    }

    private function build(): array
    {
        $rows = [];

        $rows[] = [$this->companyName];
        $rows[] = ['Chart of Accounts — Summary'];
        $rows[] = [$this->periodLabel];
        $rows[] = ['Exported: ' . now()->format('d M Y H:i')];
        $rows[] = ['Account Code', 'Account Name', 'Type', 'Category', 'Role', 'Description', 'Active', 'Balance (ZAR)'];

        $totalBalance = 0.0;

        foreach ($this->accounts->sortBy('account_code') as $account) {
            $balance = (float) $account->balance;
            $totalBalance += $balance;

            $rows[] = [
                $account->account_code,
                $account->account_name,
                ucfirst($account->account_type),
                $account->category ?? '—',
                $account->parent_id ? 'Item' : 'Group',
                $account->description ?? '',
                $account->is_active ? 'Yes' : 'No',
                $balance,
            ];
        }

        $rows[] = ['Total', '', '', '', '', '', '', $totalBalance];

        $this->lastRow = count($rows);

        return $rows;
    }

    public function styles(Worksheet $sheet): void
    {
        // Right-align balance column in header + totals
        $sheet->getStyle("H{$this->hRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $dataFrom = $this->hRow + 1;
        $dataTo   = $this->lastRow - 1;

        if ($dataFrom <= $dataTo) {
            $sheet->getStyle("H{$dataFrom}:H{$dataTo}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $sheet->getStyle("H{$this->lastRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $this->applyLetterhead($sheet, $this->companyName, 'Chart of Accounts — Summary', $this->periodLabel, 'H', $this->hRow);
    }
}

// ── Per-type sheet (assets / liabilities / equity / income / expenses) ──
class CoaTypeSheet implements FromArray, WithColumnFormatting, WithColumnWidths, WithStyles, WithTitle
{
    use CoaSheetStyle;

    private array $rows    = [];
    private int   $hRow    = 5;
    private int   $lastRow = 0;

    public function __construct(
        private readonly Collection $accounts,
        private readonly string $accountType,
        private readonly string $companyName,
        private readonly string $periodLabel,
    ) {
        $this->rows = $this->build();
    }

    public function title(): string { return ucfirst($this->accountType); }

    public function array(): array { return $this->rows; }

    public function columnWidths(): array
    {
        return [
            'A' => 14, // Account Code
            'B' => 34, // Account Name
            'C' => 18, // Category
            'D' => 10, // Role
            'E' => 38, // Description
            'F' => 8,  // Active
            'G' => 18, // Balance
        ];
    }

    public function columnFormats(): array
    {
        return ['G' => $this->afsNumberFormat()];
    }

    private function build(): array
    {
        $rows = [];

        $title = $this->companyName . ' — ' . ucfirst($this->accountType);

        $rows[] = [$this->companyName];
        $rows[] = ['Chart of Accounts — ' . ucfirst($this->accountType)];
        $rows[] = [$this->periodLabel];
        $rows[] = ['Exported: ' . now()->format('d M Y H:i')];
        $rows[] = ['Account Code', 'Account Name', 'Category', 'Role', 'Description', 'Active', 'Balance (ZAR)'];

        $totalBalance = 0.0;

        foreach ($this->accounts as $account) {
            $balance = (float) $account->balance;
            $totalBalance += $balance;

            $rows[] = [
                $account->account_code,
                $account->account_name,
                $account->category ?? '—',
                $account->parent_id ? 'Item' : 'Group',
                $account->description ?? '',
                $account->is_active ? 'Yes' : 'No',
                $balance,
            ];
        }

        $rows[] = ['Total', '', '', '', '', '', $totalBalance];

        $this->lastRow = count($rows);

        return $rows;
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle("G{$this->hRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $dataFrom = $this->hRow + 1;
        $dataTo   = $this->lastRow - 1;

        if ($dataFrom <= $dataTo) {
            $sheet->getStyle("G{$dataFrom}:G{$dataTo}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $sheet->getStyle("G{$this->lastRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $this->applyLetterhead($sheet, $this->companyName, 'Chart of Accounts', $this->periodLabel, 'G', $this->hRow);
    }
}
