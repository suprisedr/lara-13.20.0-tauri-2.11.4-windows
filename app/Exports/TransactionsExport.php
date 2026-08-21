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
    use \App\Exports\Concerns\AfsExcelDesign;

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
            'I' => $this->afsNumberFormat(),
            'J' => $this->afsNumberFormat(),
            'K' => $this->afsNumberFormat(),
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
        $h        = $this->hRow;
        $dataFrom = $h + 1;
        $dataTo   = $this->lastRow - 1;
        $last     = $this->lastRow;

        $this->afsBaseSheet($sheet);
        $this->afsTitleBlock($sheet, $this->companyName, 'Transactions', $this->periodLabel, 'A4');
        $this->afsFootnoteRow($sheet, 4, 'A', 'M');

        // Debit and credit are a balancing pair, so neither is banded; the
        // running balance in K is the figure the reader follows.
        $this->afsHeaderRow($sheet, $h, 'A', 'M', ['K'], ['I', 'J', 'K']);

        if ($dataFrom <= $dataTo) {
            $this->afsBodyRange($sheet, "A{$dataFrom}:M{$dataTo}", $dataFrom, $dataTo);
            $this->afsFigureColumn($sheet, 'I', $dataFrom, $last);
            $this->afsFigureColumn($sheet, 'J', $dataFrom, $last);
            $this->afsBandColumn($sheet, 'K', $dataFrom, $last);
            $sheet->getStyle("K{$dataFrom}:K{$last}")
                ->getNumberFormat()->setFormatCode($this->afsNumberFormat());
        }

        $this->afsEmphasisRow($sheet, $last, 'A', 'M');
        $this->afsRuleRow($sheet, $last, 'A', 'M');
        $this->afsBandColumn($sheet, 'K', $last, $last);
        $sheet->getRowDimension($last)->setRowHeight(self::AFS_ROW_HEIGHT);

        $sheet->freezePane("A{$dataFrom}");
    }
}
