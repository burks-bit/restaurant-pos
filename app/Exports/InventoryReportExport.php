<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Collection;

class InventoryReportExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    protected Collection $data;
    protected string $itemType;
    protected string $downloadedBy;

    public function __construct(Collection $data, string $itemType, string $downloadedBy)
    {
        $this->data         = $data;
        $this->itemType     = $itemType;
        $this->downloadedBy = $downloadedBy;
    }

    public function collection(): Collection
    {
        // WET ingredients
        if ($this->itemType === '0') {
            return $this->data->map(fn($row) => [
                'Item Name' => $row['name'],
                'Unit'      => $row['unit'],
                'Quantity'  => $row['current_quantity'],
                'Remarks'   => $row['remarks'],
            ]);
        }

        // DRY ingredients — matches the image format
        if ($this->itemType === '1') {
            return $this->data->map(fn($row) => [
                'Item Name'    => $row['name'],
                'Unit'         => $row['unit'],
                'Actual Count' => $row['actualCount'] ?? 0,
                'IN'           => $row['stockInQty'],
                'OUT'          => $row['stockOutQty'],
                'Final Count'  => $row['finalCount'] ?? 0,
                'Remarks'      => $row['remarks'],
            ]);
        }

        // ALL — default full view
        return $this->data->map(fn($row) => [
            'ID'              => $row['id'],
            'Category'        => $row['category'],
            'Item Name'       => $row['name'],
            'Unit'            => $row['unit'],
            'Current Stock'   => $row['current_quantity'],
            'Stock In'        => $row['stockInQty'],
            'Stock Out'       => $row['stockOutQty'],
            'Total Used Cost' => $row['total_cost'],
            'Remarks'         => $row['remarks'],
        ]);
    }

    public function headings(): array
    {
        if ($this->itemType === '0') {
            return ['Item Name', 'Unit', 'Quantity', 'Remarks'];
        }

        if ($this->itemType === '1') {
            return ['Item Name', 'Unit', 'Actual Count', 'IN', 'OUT', 'Final Count', 'Remarks'];
        }

        return ['ID', 'Category', 'Item Name', 'Unit', 'Current Stock', 'Stock In', 'Stock Out', 'Total Used Cost', 'Remarks'];
    }

    public function title(): string
    {
        return 'Inventory Report';
    }

    protected function getLastColumn(): string
    {
        return match($this->itemType) {
            '0'     => 'D',  // 4 cols: Item Name, Unit, Quantity, Remarks
            '1'     => 'G',  // 7 cols: Item Name, Unit, Actual Count, IN, OUT, Final Count, Remarks
            default => 'I',  // 9 cols: all
        };
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = $this->getLastColumn();
                $lastRow = $this->data->count() + 3; // +1 meta row, +1 title row, +1 header row

                // ── Row 1: meta info ──────────────────────────────────────
                $sheet->insertNewRowBefore(1, 2);

                // Row 1: DRY GOODS title (matches image)
                $sectionTitle = match($this->itemType) {
                    '0'     => 'WET GOODS',
                    '1'     => 'DRY GOODS',
                    default => 'INVENTORY REPORT',
                };
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', $sectionTitle);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'size'  => 13,
                        'color' => ['argb' => 'FFFFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF2E7D32'], // dark green like image
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(22);

                // Row 2: prepared by meta
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue(
                    'A2',
                    'Prepared by: ' . $this->downloadedBy . '   |   Date: ' . now()->format('F d, Y h:i A')
                );
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'bold'   => false,
                        'italic' => true,
                        'color'  => ['argb' => 'FF555555'],
                    ],
                ]);

                // ── Row 3: header row styling ─────────────────────────────
                $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['argb' => 'FF1B5E20'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFC8E6C9'], // light green header
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // ── Data rows ─────────────────────────────────────────────
                if ($lastRow >= 4) {
                    $sheet->getStyle("A4:{$lastCol}{$lastRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_NONE],
                        'font' => ['bold' => false],
                    ]);

                    // Color-code dry columns: Actual Count=yellow, IN=green, OUT=red, Final=blue
                    if ($this->itemType === '1') {
                        for ($row = 4; $row <= $lastRow; $row++) {
                            $sheet->getStyle("C{$row}")->getFont()->getColor()->setARGB('FF856404'); // Actual Count amber
                            $sheet->getStyle("D{$row}")->getFont()->getColor()->setARGB('FF155724'); // IN green
                            $sheet->getStyle("E{$row}")->getFont()->getColor()->setARGB('FF7B1D1D'); // OUT red
                            $sheet->getStyle("F{$row}")->getFont()->getColor()->setARGB('FF0D3C6B'); // Final Count blue
                        }
                    }
                }

                // ── Borders on full table ─────────────────────────────────
                $sheet->getStyle("A3:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FFCCCCCC'],
                        ],
                    ],
                ]);
            },
        ];
    }
}