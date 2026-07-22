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

class TransactionsExport implements FromArray, WithColumnFormatting, WithColumnWidths, WithStyles, WithTitle
{
    private array $rows    = [];
    private int   $hRow    = 5; // column-header row (1-indexed)
    private int   $lastRow = 0;

    public function __construct(
        private readonly Collection $transactions,
        private readonly string $companyName,
        private readonly string $periodLabel,
    ) {
        $this->rows = $this->build();
    }

    public function title(): string { return 'Journal'; }

    public function array(): array { return $this->rows; }

    // ── Column widths (explicit — avoids the auto-wide problem) ──
    public function columnWidths(): array
    {
        return [
            'A' => 13,  // Journal No.
            'B' => 13,  // Date
            'C' => 13,  // Customer ID
            'D' => 24,  // Customer Name
            'E' => 15,  // Reference
            'F' => 30,  // Transaction Type
            'G' => 32,  // Account Dr
            'H' => 32,  // Account Cr
            'I' => 15,  // Debit (R)
            'J' => 15,  // Credit (R)
            'K' => 20,  // Running Balance (R)
            'L' => 14,  // Posted By
            'M' => 38,  // Narration
        ];
    }

    public function columnFormats(): array
    {
        return [
            'I' => '#,##0.00',
            'J' => '#,##0.00',
            'K' => '#,##0.00',
        ];
    }

    // ── Build rows ───────────────────────────────────────────────
    private function build(): array
    {
        $rows = [];

        // ── Rows 1-4: letterhead ─────────────────────────────────
        $rows[] = [$this->companyName];                          // row 1 — company
        $rows[] = ['Transactions Journal'];                       // row 2 — report title
        $rows[] = [$this->periodLabel];                          // row 3 — period
        $rows[] = ['Exported: ' . now()->format('d M Y H:i')];  // row 4 — timestamp

        // ── Row 5: column headers ────────────────────────────────
        $rows[] = [
            'Journal No.', 'Date', 'Customer ID', 'Customer Name',
            'Reference', 'Transaction Type', 'Account Dr', 'Account Cr',
            'Debit (R)', 'Credit (R)', 'Running Balance (R)', 'Posted By', 'Narration',
        ];

        // ── Data rows ────────────────────────────────────────────
        $totalDebits  = 0.0;
        $totalCredits = 0.0;
        $seq = 1;

        foreach ($this->transactions as $tx) {
            $lines        = $tx->journalLines;
            $debitLines   = $lines->where('type', 'debit');
            $creditLines  = $lines->where('type', 'credit');
            $debitTotal   = (float) $debitLines->sum('amount');
            $creditTotal  = (float) $creditLines->sum('amount');

            $drAccount    = $debitLines->first()?->account;
            $crAccount    = $creditLines->first()?->account;
            $customerLine = $lines->first(fn($l) => $l->customer_id !== null);
            $customer     = $customerLine?->customer;

            $rows[] = [
                'JNL-' . str_pad($seq, 3, '0', STR_PAD_LEFT),
                $tx->transaction_date->format('Y-m-d'),
                $customer?->id ?? '',
                $customer?->name ?? '',
                $tx->reference ?? '',
                $tx->description,
                $drAccount ? $drAccount->account_code . ' ' . $drAccount->account_name : '',
                $crAccount ? $crAccount->account_code . ' ' . $crAccount->account_name : '',
                $debitTotal,
                $creditTotal,
                $debitTotal - $creditTotal,
                '',
                $tx->notes ?? $lines->first()?->description ?? '',
            ];

            $totalDebits  += $debitTotal;
            $totalCredits += $creditTotal;
            $seq++;
        }

        // ── Totals row ───────────────────────────────────────────
        $rows[] = [
            'JOURNAL TOTALS', '', '', '', '', '', '', '',
            $totalDebits, $totalCredits, '', '', '',
        ];

        $this->lastRow = count($rows);

        return $rows;
    }

    // ── Styles ───────────────────────────────────────────────────
    public function styles(Worksheet $sheet): void
    {
        $h        = $this->hRow;       // column-header row
        $dataFrom = $h + 1;
        $dataTo   = $this->lastRow - 1;
        $last     = $this->lastRow;

        // ── Letterhead ───────────────────────────────────────────
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
        $sheet->getStyle("A4:M4")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB('FFEDE9FE');

        // ── Column header row ────────────────────────────────────
        $sheet->getStyle("A{$h}:M{$h}")->applyFromArray([
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
        $sheet->getRowDimension($h)->setRowHeight(30);

        // Right-align numeric header cells
        $sheet->getStyle("I{$h}:K{$h}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // ── Data rows ────────────────────────────────────────────
        if ($dataFrom <= $dataTo) {
            // Alternating row fill
            for ($r = $dataFrom; $r <= $dataTo; $r++) {
                $fill = ($r % 2 === 0) ? 'FFF9FAFB' : 'FFFFFFFF';
                $sheet->getStyle("A{$r}:M{$r}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($fill);

                $sheet->getRowDimension($r)->setRowHeight(16);
            }

            // Light border around data area
            $sheet->getStyle("A{$dataFrom}:M{$dataTo}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setARGB('FFE5E7EB');

            // Right-align numeric columns
            $sheet->getStyle("I{$dataFrom}:K{$dataTo}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Vertical center all data
            $sheet->getStyle("A{$dataFrom}:M{$dataTo}")
                ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        // ── Totals row ───────────────────────────────────────────
        $sheet->getStyle("A{$last}:M{$last}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF1B1B18']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEDE9FE']],
        ]);

        $sheet->getStyle("A{$last}:M{$last}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_MEDIUM)
            ->getColor()->setARGB('FF5E17EB');

        $sheet->getStyle("I{$last}:J{$last}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getRowDimension($last)->setRowHeight(18);

        // ── Freeze below column headers ──────────────────────────
        $sheet->freezePane("A{$dataFrom}");
    }
}
