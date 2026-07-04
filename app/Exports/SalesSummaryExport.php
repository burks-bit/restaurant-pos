<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SalesSummaryExport implements FromArray, WithEvents, WithTitle
{
    protected string $startDate;
    protected string $endDate;
    protected bool   $isConsolidated;
    protected string $cashierName;
    protected float  $totalSales;
    protected float  $plusDP;
    protected float  $lessDP;
    protected float  $totalAmountOfSales;
    protected float  $lessExpenses;
    protected float  $remainingCash;
    protected array  $paymentBreakdown; // ['Cash' => 1000, 'GCash' => 500, 'Reservation Fee' => 2000, ...]

    public function __construct(
        string $startDate,
        string $endDate,
        bool   $isConsolidated,
        string $cashierName,
        float  $totalSales,
        float  $plusDP,
        float  $lessDP,
        float  $totalAmountOfSales,
        float  $lessExpenses,
        float  $remainingCash,
        array  $paymentBreakdown
    ) {
        $this->startDate          = $startDate;
        $this->endDate            = $endDate;
        $this->isConsolidated     = $isConsolidated;
        $this->cashierName        = $cashierName;
        $this->totalSales         = $totalSales;
        $this->plusDP             = $plusDP;
        $this->lessDP             = $lessDP;
        $this->totalAmountOfSales = $totalAmountOfSales;
        $this->lessExpenses       = $lessExpenses;
        $this->remainingCash      = $remainingCash;
        $this->paymentBreakdown   = $paymentBreakdown;
    }

    public function title(): string
    {
        return 'Sales Summary';
    }

    // No tabular data grid needed — everything is placed manually in AfterSheet.
    public function array(): array
    {
        return [[]];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ── Title block ───────────────────────────────────────────
                $sheet->setCellValue('A1', 'Sales Report');
                $sheet->setCellValue('A2', 'Period: ' . $this->startDate . ' to ' . $this->endDate);
                $sheet->mergeCells('A1:E1');
                $sheet->mergeCells('A2:E2');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['italic' => true, 'color' => ['argb' => 'FF6B7280']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ── Section headers (row 4) ──────────────────────────────
                $leftHeaderLabel = $this->isConsolidated
                    ? 'CONSOLIDATED CASHIER SUMMARY'
                    : 'PER CASHIER SUMMARY';

                $sheet->setCellValue('A4', $leftHeaderLabel);
                $sheet->mergeCells('A4:B4');
                $sheet->setCellValue('D4', 'PAYMENT BREAKDOWN');
                $sheet->mergeCells('D4:E4');

                $sheet->getStyle('A4:B4')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF16A34A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('D4:E4')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1D4ED8']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ── Left block: cashier summary ──────────────────────────
                $leftRows = [];

                if ($this->isConsolidated) {
                    $leftRows[] = ['All Cashier Sales', $this->totalSales];
                } else {
                    $leftRows[] = ['Name of Cashier', $this->cashierName];
                    $leftRows[] = ['Total Sales', $this->totalSales];
                }

                $leftRows[] = ['Plus DP', $this->plusDP];
                $leftRows[] = ['Less DP', $this->lessDP];
                $leftRows[] = ['Total Amount of Sales', $this->totalAmountOfSales];
                $leftRows[] = ['Less Expenses', $this->lessExpenses];
                $leftRows[] = ['Remaining Cash', $this->remainingCash];

                $leftStartRow = 5;
                foreach ($leftRows as $i => $row) {
                    $r = $leftStartRow + $i;
                    $sheet->setCellValue("A{$r}", $row[0]);
                    $sheet->setCellValue("B{$r}", $row[1]);

                    if (is_numeric($row[1])) {
                        $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode('₱#,##0.00');
                    }
                }

                // Bold the two "total" rows (Total Amount of Sales, Remaining Cash — always the last two)
                $leftEndRow            = $leftStartRow + count($leftRows) - 1;
                $totalAmountOfSalesRow = $leftEndRow - 2;
                $remainingCashRow      = $leftEndRow;

                $sheet->getStyle("A{$totalAmountOfSalesRow}:B{$totalAmountOfSalesRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$remainingCashRow}:B{$remainingCashRow}")->getFont()->setBold(true);

                $sheet->getStyle("A{$leftStartRow}:B{$leftEndRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FFD1D5DB'],
                        ],
                    ],
                ]);

                // ── Right block: payment breakdown ───────────────────────
                $rightStartRow = 5;
                $i = 0;
                $totalPayment = 0.0;

                foreach ($this->paymentBreakdown as $methodName => $amount) {
                    $r = $rightStartRow + $i;
                    $sheet->setCellValue("D{$r}", $methodName);
                    $sheet->setCellValue("E{$r}", (float) $amount);
                    $sheet->getStyle("E{$r}")->getNumberFormat()->setFormatCode('₱#,##0.00');
                    $totalPayment += (float) $amount;
                    $i++;
                }

                $totalRow = $rightStartRow + $i;
                $sheet->setCellValue("D{$totalRow}", 'Total Amount');
                $sheet->setCellValue("E{$totalRow}", $totalPayment);
                $sheet->getStyle("E{$totalRow}")->getNumberFormat()->setFormatCode('₱#,##0.00');
                $sheet->getStyle("D{$totalRow}:E{$totalRow}")->getFont()->setBold(true);

                $sheet->getStyle("D{$rightStartRow}:E{$totalRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FFD1D5DB'],
                        ],
                    ],
                ]);

                // ── Column sizing ────────────────────────────────────────
                foreach (['A', 'B', 'D', 'E'] as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                $sheet->getColumnDimension('C')->setWidth(4); // spacer between blocks
            },
        ];
    }
}