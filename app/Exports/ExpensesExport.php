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

class ExpensesExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    protected Collection $data;
    protected string $downloadedBy;

    public function __construct(Collection $data, string $downloadedBy)
    {
        $this->data         = $data;
        $this->downloadedBy = $downloadedBy;
    }

    public function collection(): Collection
    {
        return $this->data->map(fn($expense) => [
            'Date'            => $expense->expense_date,
            'Category'        => $expense->category->name ?? '',
            'Requesting Name' => $expense->requesting_name ?? '',
            'Description'     => $expense->description,
            'Amount'          => (float) $expense->amount,
            'Account Title'   => $expense->account_title ?? '',
            'Posted By'       => $expense->creator->name ?? '',
        ]);
    }

    public function headings(): array
    {
        return ['Date', 'Category', 'Requesting Name', 'Description', 'Amount', 'Account Title', 'Posted By'];
    }

    public function title(): string
    {
        return 'Expenses';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = 'G';
                $lastRow = $this->data->count() + 3; // +1 meta row, +1 title row, +1 header row

                // ── Row 1: report title ───────────────────────────────────
                $sheet->insertNewRowBefore(1, 2);

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', 'EXPENSES REPORT');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'size'  => 13,
                        'color' => ['argb' => 'FFFFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFB91C1C'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(22);

                // ── Row 2: prepared by meta ───────────────────────────────
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue(
                    'A2',
                    'Prepared by: ' . $this->downloadedBy . '   |   Date: ' . now()->format('F d, Y h:i A')
                );
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'italic' => true,
                        'color'  => ['argb' => 'FF555555'],
                    ],
                ]);

                // ── Row 3: header row styling ─────────────────────────────
                $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['argb' => 'FF7F1D1D'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFECACA'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // ── Borders on the full table ─────────────────────────────
                $sheet->getStyle("A3:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FFCCCCCC'],
                        ],
                    ],
                ]);

                // ── Amount column number format ───────────────────────────
                if ($this->data->count() > 0) {
                    $sheet->getStyle("E4:E{$lastRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }
            },
        ];
    }
}
