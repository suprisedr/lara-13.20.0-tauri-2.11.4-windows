<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActionsExport implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    private array $rows    = [];
    private int   $hRow    = 5;
    private int   $lastRow = 0;

    public function __construct(
        private readonly Collection $actions,
        private readonly string $companyName,
        private readonly string $tabLabel,
    ) {
        $this->rows = $this->build();
    }

    public function title(): string { return 'Actions'; }

    public function array(): array { return $this->rows; }

    public function columnWidths(): array
    {
        return [
            'A' => 40,
            'B' => 12,
            'C' => 12,
            'D' => 12,
            'E' => 50,
            'F' => 20,
            'G' => 18,
            'H' => 18,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $last = $this->lastRow;

        $sheet->mergeCells("A1:H1");
        $sheet->mergeCells("A2:H2");
        $sheet->mergeCells("A3:H3");

        $sheet->getStyle("A1")->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle("A2")->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle("A3")->getFont()->setSize(9)->getColor()->setRGB('666666');

        $headerRange = "A{$this->hRow}:H{$this->hRow}";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '000000']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        if ($last > $this->hRow) {
            $dataRange = "A" . ($this->hRow + 1) . ":H{$last}";
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            ]);
            $sheet->getStyle($dataRange)->getFont()->setSize(9);
        }

        return [];
    }

    private function build(): array
    {
        $rows = [];

        $rows[] = [$this->companyName];
        $rows[] = ['Company Actions — ' . $this->tabLabel];
        $rows[] = ['Exported: ' . now()->format('d M Y H:i')];
        $rows[] = [];
        $rows[] = ['Title', 'Priority', 'Source', 'Status', 'Details', 'Related', 'Created', 'Resolved'];

        foreach ($this->actions as $action) {
            $rows[] = [
                $action->title,
                ucfirst($action->priority),
                match ($action->source) {
                    'ai-agent' => 'AI Agent',
                    'system'   => 'System',
                    default    => 'Manual',
                },
                $action->isResolved() ? 'Resolved' : 'Open',
                $action->body ?? '',
                $action->related_type ? ($action->related_type . ($action->related_id ? ' #' . $action->related_id : '')) : '',
                $action->created_at->format('d M Y H:i'),
                $action->resolved_at?->format('d M Y H:i') ?? '',
            ];
        }

        $this->lastRow = count($rows);

        return $rows;
    }
}
