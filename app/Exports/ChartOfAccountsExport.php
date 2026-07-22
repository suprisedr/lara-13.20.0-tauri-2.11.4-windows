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
    private function applyLetterhead(Worksheet $sheet, string $companyName, string $reportTitle, string $periodLabel, string $lastCol, int $hRow): void
    {
        $dataFrom = $hRow + 1;

        // Company name
        $sheet->getStyle('A1')->getFont()
            ->setBold(true)->setSize(14)
            ->getColor()->setARGB('FF1B1B18');

        // Report title
        $sheet->getStyle('A2')->getFont()
            ->setBold(true)->setSize(11)
            ->getColor()->setARGB('FF5E17EB');

        // Period + timestamp
        $sheet->getStyle('A3:A4')->getFont()
            ->setSize(9)
            ->getColor()->setARGB('FF6B7280');

        // Thin rule under letterhead
        $sheet->getStyle("A4:{$lastCol}4")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB('FFEDE9FE');

        // Column header row
        $sheet->getStyle("A{$hRow}:{$lastCol}{$hRow}")->applyFromArray([
            'font' => [
                'bold'  => true,
                'size'  => 9,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF374151'],
            ],
            'alignment' => [
                'vertical'   => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'wrapText'   => true,
            ],
        ]);
        $sheet->getRowDimension($hRow)->setRowHeight(30);

        // Data rows
        $lastRow = $sheet->getHighestRow();
        $dataTo  = $lastRow - 1;

        if ($dataFrom <= $dataTo) {
            for ($r = $dataFrom; $r <= $dataTo; $r++) {
                $fill = ($r % 2 === 0) ? 'FFF9FAFB' : 'FFFFFFFF';
                $sheet->getStyle("A{$r}:{$lastCol}{$r}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($fill);

                $sheet->getRowDimension($r)->setRowHeight(16);
            }

            $sheet->getStyle("A{$dataFrom}:{$lastCol}{$dataTo}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setARGB('FFE5E7EB');

            $sheet->getStyle("A{$dataFrom}:{$lastCol}{$dataTo}")
                ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        // Totals row (last row)
        $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF1B1B18']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEDE9FE']],
        ]);
        $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_MEDIUM)
            ->getColor()->setARGB('FF5E17EB');
        $sheet->getRowDimension($lastRow)->setRowHeight(18);

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
        return ['H' => '#,##0.00'];
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

        $rows[] = ['TOTAL', '', '', '', '', '', '', $totalBalance];

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
        return ['G' => '#,##0.00'];
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

        $rows[] = ['TOTAL', '', '', '', '', '', $totalBalance];

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
