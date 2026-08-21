<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The CGL-YE25 annual-financial-statements design, for PhpSpreadsheet exports.
 *
 * One definition shared by every export in this namespace. The app has been
 * bitten before by the same design living in two files and drifting — the web
 * report styles against the PDF styles — so nothing here should be copied into
 * an individual export; add to the trait instead.
 *
 * This is the same system as `desktop-mcp/src/lib/management-xlsx.ts` and
 * `afs-docx.ts`. The rules those enforce, enforced here too:
 *
 *   1. The header row is NOT a full coloured band. Only a current/primary
 *      column is filled #005BF0 with white text; every other header cell is
 *      plain with a black hairline under it.
 *   2. #EAF8FB is a column band, not row striping. Alternating row fills are
 *      not part of this design and must not be reintroduced.
 *   3. Every rule is a single 0.5pt black hairline. No medium borders, no
 *      double rules, and no full grid of `allBorders` — the document rules
 *      rows, never columns.
 *   4. No uppercase and no letter-spacing anywhere. Emphasis is weight and
 *      colour only.
 *   5. Figures use a space thousands separator, parentheses for negatives and
 *      an em dash for nil.
 *
 * @see \App\Services\ManagementAccountsModelService for the same conventions
 *      on the model side.
 */
trait AfsExcelDesign
{
    /** Bundled in public/fonts; referenced by name here. */
    public const AFS_FONT = 'Century Gothic';

    /** The document's type ladder, in points. */
    public const AFS_SIZE_TITLE = 25;
    public const AFS_SIZE_SECTION = 13;
    public const AFS_SIZE_DATE = 11.5;
    public const AFS_SIZE_BODY = 10.5;
    public const AFS_SIZE_CONTROL = 10;
    public const AFS_SIZE_FOOTNOTE = 7;

    /** ARGB. Nothing outside this palette belongs in an export. */
    public const AFS_RULE = 'FF000000';
    public const AFS_EMPHASIS = 'FF005BF0';
    public const AFS_NAVY = 'FF1A345B';
    public const AFS_TINT = 'FFEAF8FB';
    public const AFS_WHITE = 'FFFFFFFF';
    public const AFS_MUTED = 'FF5A7186';
    /** Reserved for a genuine failure state, e.g. a trial balance that does not balance. */
    public const AFS_ALERT = 'FF9D174D';

    /** w:line 270 exact = 13.5pt. Row height comes entirely from this. */
    public const AFS_ROW_HEIGHT = 13.5;

    /**
     * The document's figure conventions as an Excel format.
     *
     * The thousands space is escaped so it survives as a group separator
     * whatever the reader's locale would otherwise substitute.
     */
    protected function afsNumberFormat(int $rounding = 1): string
    {
        $body = $rounding === 1 ? '#\ ##0.00' : '#\ ##0';

        return $body . ';(' . $body . ');"—"';
    }

    protected function afsPercentFormat(): string
    {
        return '0.0"%";(0.0"%");"—"';
    }

    /** Sheet defaults: the document's face, and none of Excel's own chrome. */
    protected function afsBaseSheet(Worksheet $sheet): void
    {
        $sheet->getParent()->getDefaultStyle()->getFont()
            ->setName(self::AFS_FONT)
            ->setSize(self::AFS_SIZE_BODY);

        // The document draws its own hairlines; gridlines are not part of it.
        $sheet->setShowGridlines(false);

        $setup = $sheet->getPageSetup();
        $setup->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $setup->setFitToWidth(1);
        $setup->setFitToHeight(0);
    }

    /**
     * Company name, then the report title, then the date line.
     *
     * The 25pt slot belongs to the report title; the company name rides the
     * 13pt letterhead treatment above it, as in the source document.
     */
    protected function afsTitleBlock(
        Worksheet $sheet,
        string $companyName,
        string $title,
        string $subtitle,
        ?string $note = null,
    ): void {
        $lines = [
            [1, $companyName, self::AFS_SIZE_SECTION, true],
            [2, $title, self::AFS_SIZE_TITLE, true],
            [3, $subtitle, self::AFS_SIZE_DATE, false],
        ];

        foreach ($lines as [$row, , $size, $bold]) {
            $sheet->getStyle("A{$row}")->getFont()
                ->setName(self::AFS_FONT)
                ->setSize($size)
                ->setBold($bold)
                ->getColor()->setARGB(self::AFS_NAVY);
            // Display type needs more room than the 13.5pt body leading.
            $sheet->getRowDimension($row)->setRowHeight(ceil($size * 1.35));
        }

        if ($note !== null) {
            $sheet->getStyle('A4')->getFont()
                ->setName(self::AFS_FONT)
                ->setSize(self::AFS_SIZE_FOOTNOTE)
                ->getColor()->setARGB(self::AFS_NAVY);
        }
    }

    /**
     * The header row.
     *
     * `$bandColumns` are the primary columns — those get the #005BF0 fill with
     * white text. Everything else is plain with a black hairline. Passing an
     * empty array is correct for a matrix-shaped report such as a trial
     * balance, where debit and credit are a balancing pair with no primary
     * side and neither should be banded.
     *
     * @param  array<int, string>  $bandColumns  e.g. ['D']
     */
    protected function afsHeaderRow(
        Worksheet $sheet,
        int $row,
        string $firstCol,
        string $lastCol,
        array $bandColumns = [],
        array $rightAlignCols = [],
    ): void {
        $range = "{$firstCol}{$row}:{$lastCol}{$row}";

        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'name' => self::AFS_FONT,
                'size' => self::AFS_SIZE_BODY,
                'bold' => false,
                'color' => ['argb' => self::AFS_RULE],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_BOTTOM,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'wrapText' => true,
            ],
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::AFS_RULE]],
            ],
        ]);

        foreach ($rightAlignCols as $col) {
            $sheet->getStyle("{$col}{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        foreach ($bandColumns as $col) {
            $sheet->getStyle("{$col}{$row}")->applyFromArray([
                'font' => [
                    'name' => self::AFS_FONT,
                    'size' => self::AFS_SIZE_BODY,
                    'bold' => true,
                    'color' => ['argb' => self::AFS_WHITE],
                ],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::AFS_EMPHASIS]],
                'borders' => [
                    'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::AFS_EMPHASIS]],
                ],
            ]);
        }

        // Two stacked lines need the doubled height or the second one clips.
        $sheet->getRowDimension($row)->setRowHeight(self::AFS_ROW_HEIGHT * 2);
    }

    /**
     * Body rows: the document's face and leading, no fills and no grid.
     *
     * Deliberately does not touch borders. Rules belong to specific rows —
     * see afsRuleRow — never to the whole block.
     */
    protected function afsBodyRange(Worksheet $sheet, string $range, ?int $fromRow = null, ?int $toRow = null): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'name' => self::AFS_FONT,
                'size' => self::AFS_SIZE_BODY,
                'color' => ['argb' => self::AFS_RULE],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_BOTTOM],
        ]);

        if ($fromRow !== null && $toRow !== null) {
            for ($r = $fromRow; $r <= $toRow; $r++) {
                $sheet->getRowDimension($r)->setRowHeight(self::AFS_ROW_HEIGHT);
            }
        }
    }

    /** A closing hairline across a row — the only rule in the document. */
    protected function afsRuleRow(Worksheet $sheet, int $row, string $firstCol, string $lastCol): void
    {
        $sheet->getStyle("{$firstCol}{$row}:{$lastCol}{$row}")
            ->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB(self::AFS_RULE);
    }

    /** An emphasis row: bold #005BF0 label, as sections and subtotals carry. */
    protected function afsEmphasisRow(Worksheet $sheet, int $row, string $firstCol, string $lastCol): void
    {
        $sheet->getStyle("{$firstCol}{$row}:{$lastCol}{$row}")->getFont()
            ->setName(self::AFS_FONT)
            ->setBold(true)
            ->getColor()->setARGB(self::AFS_EMPHASIS);
    }

    /**
     * The #EAF8FB band down a primary column.
     *
     * Runs unbroken from the first body row to the last, through section and
     * subtotal rows alike, and the column is bold on every row. Breaking it
     * around a section row is the single most visible way to get this wrong.
     */
    protected function afsBandColumn(Worksheet $sheet, string $column, int $fromRow, int $toRow): void
    {
        $sheet->getStyle("{$column}{$fromRow}:{$column}{$toRow}")->applyFromArray([
            'font' => ['name' => self::AFS_FONT, 'bold' => true, 'color' => ['argb' => self::AFS_RULE]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::AFS_TINT]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_BOTTOM],
        ]);
    }

    /** Right-align a figure column over the body. */
    protected function afsFigureColumn(Worksheet $sheet, string $column, int $fromRow, int $toRow, int $rounding = 1): void
    {
        $sheet->getStyle("{$column}{$fromRow}:{$column}{$toRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("{$column}{$fromRow}:{$column}{$toRow}")
            ->getNumberFormat()->setFormatCode($this->afsNumberFormat($rounding));
    }

    /** Fine print, e.g. an export timestamp or a basis note. */
    protected function afsFootnoteRow(Worksheet $sheet, int $row, string $firstCol, string $lastCol): void
    {
        $sheet->getStyle("{$firstCol}{$row}:{$lastCol}{$row}")->getFont()
            ->setName(self::AFS_FONT)
            ->setSize(self::AFS_SIZE_FOOTNOTE)
            ->getColor()->setARGB(self::AFS_NAVY);
        $sheet->getRowDimension($row)->setRowHeight(self::AFS_ROW_HEIGHT);
    }
}
