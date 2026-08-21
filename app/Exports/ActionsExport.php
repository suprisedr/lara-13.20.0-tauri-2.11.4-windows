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
    use \App\Exports\Concerns\AfsExcelDesign;

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
        $last     = $this->lastRow;
        $dataFrom = $this->hRow + 1;

        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');

        $this->afsBaseSheet($sheet);
        $this->afsTitleBlock($sheet, $this->companyName, 'Actions', $this->tabLabel, null);

        // Nothing is banded: an action list carries no figures, so there is no
        // primary column for the tint to mark.
        $this->afsHeaderRow($sheet, $this->hRow, 'A', 'H', [], []);

        if ($last > $this->hRow) {
            // Actions carry prose that wraps, so rows are left to auto-height
            // rather than pinned to the 13.5pt body leading.
            $this->afsBodyRange($sheet, "A{$dataFrom}:H{$last}");
            $sheet->getStyle("A{$dataFrom}:H{$last}")->getAlignment()
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP)
                ->setWrapText(true);
        }

        $sheet->freezePane("A{$dataFrom}");

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
