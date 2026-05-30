<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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
        if ($this->itemType === '0') {
            return $this->data->map(fn($row) => [
                'Item Name' => $row['name'],
                'Unit'      => $row['unit'],
                'Quantity'  => $row['current_quantity'],
                'Remarks'   => $row['remarks'],
            ]);
        }

        return $this->data->map(fn($row) => [
            'ID'              => $row['id'],
            'Category'        => $row['category'],
            'Item Name'       => $row['name'],
            'Unit'            => $row['unit'],
            'Current Stock'   => $row['current_quantity'],
            'Stock In'        => $row['stockInQty'],
            'Stock Out'       => $row['stockOutQty'],
            'Total Used Cost' => $row['total_cost'],
        ]);
    }

    public function headings(): array
    {
        if ($this->itemType === '0') {
            return ['Item Name', 'Unit', 'Quantity', 'Remarks'];
        }

        return ['ID', 'Category', 'Item Name', 'Unit', 'Current Stock', 'Stock In', 'Stock Out', 'Total Used Cost'];
    }

    public function title(): string
    {
        return 'Inventory Report';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = $this->itemType === '0' ? 'D' : 'H'; // D = Item Name, Unit, Quantity, Remarks
                $lastRow = $this->data->count() + 2;

                $sheet->insertNewRowBefore(1, 1);
                $sheet->setCellValue(
                    'A1',
                    'Prepared by: ' . $this->downloadedBy . '   |   Date: ' . now()->format('F d, Y h:i A')
                );

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold'   => false,
                        'italic' => true,
                        'color'  => ['argb' => 'FF555555'],
                    ],
                ]);

                $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFADD8E6'],
                    ],
                ]);

                if ($lastRow >= 3) {
                    $sheet->getStyle("A3:{$lastCol}{$lastRow}")->applyFromArray([
                        'font' => ['bold' => false],
                        'fill' => [
                            'fillType' => Fill::FILL_NONE,
                        ],
                    ]);
                }
            },
        ];
    }
}