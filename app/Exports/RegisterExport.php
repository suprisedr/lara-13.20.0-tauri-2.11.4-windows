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

class RegisterExport implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    private array $rows    = [];
    private int   $hRow    = 5;
    private int   $lastRow = 0;
    private string $sheetTitle;

    public function __construct(
        private readonly string     $register,   // ppe | intangible | investment_property | held_for_sale | biological | lease | ecl
        private readonly Collection $items,
        private readonly string     $companyName,
        private readonly string     $asOfDate,
    ) {
        $this->sheetTitle = match ($register) {
            'ppe'                 => 'PPE (IAS 16)',
            'intangible'          => 'Intangibles (IAS 38)',
            'investment_property' => 'Inv. Properties (IAS 40)',
            'held_for_sale'       => 'Held for Sale (IFRS 5)',
            'biological'          => 'Bio Assets (IAS 41)',
            'lease'               => 'Leases (IFRS 16)',
            'ecl'                 => 'ECL (IFRS 9)',
            default               => 'Register',
        };

        $this->rows = $this->build();
    }

    public function title(): string { return $this->sheetTitle; }

    public function array(): array { return $this->rows; }

    public function columnWidths(): array
    {
        return match ($this->register) {
            'lease' => ['A' => 5, 'B' => 26, 'C' => 12, 'D' => 12, 'E' => 12, 'F' => 14, 'G' => 14, 'H' => 14, 'I' => 14, 'J' => 14, 'K' => 14, 'L' => 14],
            'ecl'   => ['A' => 30, 'B' => 14, 'C' => 14, 'D' => 14, 'E' => 14, 'F' => 14, 'G' => 14],
            default => ['A' => 5, 'B' => 28, 'C' => 12, 'D' => 12, 'E' => 14, 'F' => 14, 'G' => 14, 'H' => 14, 'I' => 14, 'J' => 14],
        };
    }

    // ─── builders ────────────────────────────────────────────────────────────

    private function build(): array
    {
        return match ($this->register) {
            'ppe'                 => $this->buildPpe(),
            'intangible'          => $this->buildIntangible(),
            'investment_property' => $this->buildInvestmentProperty(),
            'held_for_sale'       => $this->buildHeldForSale(),
            'biological'          => $this->buildBiological(),
            'lease'               => $this->buildLease(),
            'ecl'                 => $this->buildEcl(),
            default               => [],
        };
    }

    private function header(array $cols): array
    {
        $rows   = [];
        $rows[] = [$this->companyName];
        $rows[] = [$this->sheetTitle];
        $rows[] = ['As at ' . \Carbon\Carbon::parse($this->asOfDate)->format('d F Y')];
        $rows[] = ['Exported: ' . now()->format('d M Y H:i')];
        $rows[] = $cols;
        $this->hRow = 5;
        return $rows;
    }

    private function buildPpe(): array
    {
        $rows = $this->header(['#', 'Asset Name', 'Class', 'Tag', 'Acquired', 'Cost (R)', 'Acc. Dep. (R)', 'Acc. Imp. (R)', 'Carrying Amount (R)', 'Rev. Surplus (R)', 'Status']);

        $n = 0;
        foreach ($this->items as $asset) {
            $accDep = (float) $asset->accumulatedDepreciation($this->asOfDate);
            $accImp = (float) ($asset->accumulated_impairment ?? 0);
            $nbv    = (float) $asset->cost - $accDep - $accImp;

            $rows[] = [
                ++$n,
                $asset->name,
                $asset->ppeClass?->name ?? '—',
                $asset->asset_tag ?? '—',
                $asset->acquisition_date?->format('d/m/Y') ?? '—',
                (float) $asset->cost,
                $accDep,
                $accImp,
                max(0, $nbv),
                (float) ($asset->revaluation_surplus ?? 0),
                ucfirst($asset->status ?? 'active'),
            ];
        }

        $this->totalRow($rows, [5, 6, 7, 8, 9], $n);
        $this->lastRow = count($rows);
        return $rows;
    }

    private function buildIntangible(): array
    {
        $rows = $this->header(['#', 'Asset Name', 'Class', 'Reference', 'Acquired', 'Cost (R)', 'Acc. Amort. (R)', 'Acc. Imp. (R)', 'Carrying Amount (R)', 'Rev. Surplus (R)', 'Status']);

        $n = 0;
        foreach ($this->items as $asset) {
            $accAmort = (float) $asset->accumulatedAmortisation($this->asOfDate);
            $accImp   = (float) ($asset->accumulated_impairment ?? 0);
            $nbv      = (float) $asset->cost - $accAmort - $accImp;

            $rows[] = [
                ++$n,
                $asset->name,
                $asset->intangibleClass?->name ?? $asset->category ?? '—',
                $asset->reference ?? '—',
                $asset->acquisition_date?->format('d/m/Y') ?? '—',
                (float) $asset->cost,
                $accAmort,
                $accImp,
                max(0, $nbv),
                (float) ($asset->revaluation_surplus ?? 0),
                ucfirst($asset->status ?? 'active'),
            ];
        }

        $this->totalRow($rows, [5, 6, 7, 8, 9], $n);
        $this->lastRow = count($rows);
        return $rows;
    }

    private function buildInvestmentProperty(): array
    {
        $rows = $this->header(['#', 'Property Name', 'Class', 'Location', 'Acquired', 'Cost (R)', 'Fair Value (R)', 'Acc. Imp. (R)', 'Carrying Amount (R)', 'FV Gain/Loss (R)', 'Status']);

        $n = 0;
        foreach ($this->items as $prop) {
            $accImp  = (float) ($prop->accumulated_impairment ?? 0);
            $fv      = (float) ($prop->fair_value ?? 0);
            $cost    = (float) $prop->cost;
            $carrying = $fv > 0 ? $fv : ($cost - $accImp);

            $rows[] = [
                ++$n,
                $prop->name,
                $prop->investmentPropertyClass?->name ?? '—',
                $prop->location ?? '—',
                $prop->acquisition_date?->format('d/m/Y') ?? '—',
                $cost,
                $fv ?: '—',
                $accImp,
                $carrying,
                (float) ($prop->fair_value_gain_loss ?? 0),
                ucfirst($prop->status ?? 'active'),
            ];
        }

        $this->totalRow($rows, [5, 6, 7, 8, 9], $n);
        $this->lastRow = count($rows);
        return $rows;
    }

    private function buildHeldForSale(): array
    {
        $rows = $this->header(['#', 'Asset Name', 'Reclassified', 'Carrying at Reclassification (R)', 'FVLCTS (R)', 'Imp. on Reclassification (R)', 'Expected Sale Date', 'Disposal Proceeds (R)', 'Status']);

        $n = 0;
        foreach ($this->items as $hfs) {
            $rows[] = [
                ++$n,
                $hfs->asset?->name ?? '—',
                $hfs->reclassification_date?->format('d/m/Y') ?? '—',
                (float) ($hfs->carrying_amount_at_reclassification ?? 0),
                (float) ($hfs->fair_value_less_costs_to_sell ?? 0),
                (float) ($hfs->impairment_on_reclassification ?? 0),
                $hfs->expected_sale_date?->format('d/m/Y') ?? '—',
                $hfs->disposal_proceeds ? (float) $hfs->disposal_proceeds : '—',
                ucfirst($hfs->status ?? 'active'),
            ];
        }

        $this->totalRow($rows, [3, 4, 5, 7], $n);
        $this->lastRow = count($rows);
        return $rows;
    }

    private function buildBiological(): array
    {
        $rows = $this->header(['#', 'Asset Name', 'Class', 'Location', 'Acquired', 'Quantity', 'Unit', 'Cost (R)', 'Fair Value (R)', 'Acc. Imp. (R)', 'Status']);

        $n = 0;
        foreach ($this->items as $asset) {
            $rows[] = [
                ++$n,
                $asset->name,
                $asset->biologicalAssetClass?->name ?? '—',
                $asset->location ?? '—',
                $asset->acquisition_date?->format('d/m/Y') ?? '—',
                (float) ($asset->quantity ?? 0),
                $asset->unit ?? '—',
                (float) $asset->cost,
                (float) ($asset->fair_value ?? 0),
                (float) ($asset->accumulated_impairment ?? 0),
                ucfirst($asset->status ?? 'active'),
            ];
        }

        $this->totalRow($rows, [5, 7, 8, 9], $n);
        $this->lastRow = count($rows);
        return $rows;
    }

    private function buildLease(): array
    {
        $rows = $this->header(['#', 'Lease Name', 'Role', 'Counterparty', 'Commenced', 'End Date', 'Monthly Pmt (R)', 'ROU/Asset Cost (R)', 'Acc. Dep. (R)', 'Liability/Investment (R)', 'Acc. Imp. (R)', 'Status']);

        $n = 0;
        foreach ($this->items as $lease) {
            $accDep = (float) $lease->accumulatedDepreciation($this->asOfDate);
            $rows[] = [
                ++$n,
                $lease->name,
                ucfirst($lease->role ?? 'lessee'),
                $lease->counterparty ?? '—',
                $lease->commencement_date?->format('d/m/Y') ?? '—',
                $lease->end_date?->format('d/m/Y') ?? '—',
                (float) ($lease->monthly_payment ?? 0),
                (float) ($lease->rou_asset_cost ?? $lease->asset_fair_value ?? 0),
                $accDep,
                (float) ($lease->lease_liability_opening ?? $lease->net_investment ?? 0),
                (float) ($lease->accumulated_impairment ?? 0),
                ucfirst($lease->status ?? 'active'),
            ];
        }

        $this->totalRow($rows, [6, 7, 8, 9, 10], $n);
        $this->lastRow = count($rows);
        return $rows;
    }

    private function buildEcl(): array
    {
        $rows = $this->header(['Customer', 'Current (R)', '31–60 Days (R)', '61–90 Days (R)', '91+ Days (R)', 'Gross Balance (R)', 'ECL Allowance (R)']);
        $this->hRow = 5;

        foreach ($this->items as $a) {
            $rows[] = [
                $a->customer?->name ?? '—',
                (float) ($a->current_amount ?? 0),
                (float) ($a->days_31_60 ?? 0),
                (float) ($a->days_61_90 ?? 0),
                (float) ($a->days_91_plus ?? 0),
                (float) ($a->total_outstanding ?? 0),
                (float) ($a->total_ecl ?? 0),
            ];
        }

        $n = count($this->items);

        // Totals
        $rows[] = [
            'TOTAL',
            $this->items->sum('current_amount'),
            $this->items->sum('days_31_60'),
            $this->items->sum('days_61_90'),
            $this->items->sum('days_91_plus'),
            $this->items->sum('total_outstanding'),
            $this->items->sum('total_ecl'),
        ];

        $this->lastRow = count($rows);
        return $rows;
    }

    /** Append a totals row summing the given 0-indexed column positions. */
    private function totalRow(array &$rows, array $sumCols, int $n): void
    {
        if ($n === 0) return;

        $last = end($rows);
        $total = array_fill(0, count($last), '');
        $total[0] = 'TOTAL';

        foreach ($sumCols as $col) {
            $total[$col] = array_sum(array_column(
                array_slice($rows, $this->hRow), // skip header rows
                $col
            ));
        }

        $rows[] = $total;
    }

    // ─── styles ──────────────────────────────────────────────────────────────

    public function styles(Worksheet $sheet): void
    {
        $h        = $this->hRow;
        $dataFrom = $h + 1;
        $last     = $this->lastRow;

        // Title rows
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setARGB('FF1B1B18');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A3:A4')->getFont()->setSize(8)->getColor()->setARGB('FF6B7280');

        // Column header row
        $sheet->getStyle("A{$h}:" . $this->lastCol() . "{$h}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF374151']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension($h)->setRowHeight(28);

        // Data rows — zebra
        for ($r = $dataFrom; $r < $last; $r++) {
            $fill = ($r % 2 === 0) ? 'FFF9FAFB' : 'FFFFFFFF';
            $sheet->getStyle("A{$r}:" . $this->lastCol() . "{$r}")
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($fill);
            $sheet->getRowDimension($r)->setRowHeight(16);
        }

        // Grid lines over data
        if ($dataFrom <= $last) {
            $sheet->getStyle("A{$dataFrom}:" . $this->lastCol() . "{$last}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE5E7EB');
        }

        // Totals row
        $sheet->getStyle("A{$last}:" . $this->lastCol() . "{$last}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF5F5F5']],
        ]);
        $sheet->getStyle("A{$last}:" . $this->lastCol() . "{$last}")
            ->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB('FF000000');

        // Right-align numeric columns (all except A and B)
        $colCount = count($this->columnWidths());
        for ($c = 3; $c <= $colCount; $c++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $sheet->getStyle("{$col}{$dataFrom}:{$col}{$last}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("{$col}{$dataFrom}:{$col}{$last}")
                ->getNumberFormat()->setFormatCode('#,##0.00');
        }

        $sheet->freezePane("A{$dataFrom}");
    }

    private function lastCol(): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->columnWidths()));
    }
}
