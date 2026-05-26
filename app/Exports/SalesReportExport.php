<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SalesReportExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize,
    WithTitle,
    WithEvents
{
    protected Collection $orders;
    protected float      $grossSales;
    protected float      $totalExpenses;
    protected float      $netSales;
    protected array      $paymentTotals;  // dynamic: ['Cash' => 87242, 'GCash' => 11908, 'BPI' => 738, ...]
    protected string     $startDate;
    protected string     $endDate;

    public function __construct(
        Collection $orders,
        float      $grossSales,
        float      $totalExpenses,
        float      $netSales,
        array      $paymentTotals,
        string     $startDate,
        string     $endDate
    ) {
        $this->orders        = $orders;
        $this->grossSales    = $grossSales;
        $this->totalExpenses = $totalExpenses;
        $this->netSales      = $netSales;
        $this->paymentTotals = $paymentTotals;
        $this->startDate     = $startDate;
        $this->endDate       = $endDate;
    }

    public function title(): string
    {
        return 'Sales Report';
    }

    public function collection(): Collection
    {
        return $this->orders;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Cashier',
            'Table #',
            'Customer Name',
            'No. of Pax',
            'Amount (Subtotal)',
            'GCash',
            'Cash',
            'Maya',
            'Total Gross Sales',
            'Less Discount',
            'Total Net Sales',
            'Status',
        ];
    }

    public function map($order): array
    {
        $isReservation = str_starts_with($order->order_no ?? '', 'RSVP');
        $isSingle      = !$order->tableSession && !$order->table_number && !$isReservation;

        if ($isReservation) {
            $tableLabel   = 'Reservation Fee (' . ($order->order_no ?? 'N/A') . ')';
            $customerName = '-';
            $pax          = '-';
        } elseif ($isSingle) {
            $tableLabel   = 'Single Order';
            $customerName = '-';
            $pax          = '-';
        } else {
            $tableLabel   = $order->tableSession?->table?->name ?? 'N/A';
            $customerName = $order->tableSession?->customer_name ?? '-';
            $pax          = $order->tableSession?->pax ?? '-';
        }

        $payments = collect($order->payments ?? []);

        $gcash = $payments
            ->filter(fn($p) => ($p->paymentMethod?->code ?? '') === 'gcash')
            ->sum(fn($p) => (float) ($p->amount ?? 0));

        $cash = $payments
            ->filter(fn($p) => ($p->paymentMethod?->code ?? '') === 'cash')
            ->sum(fn($p) => (float) ($p->amount ?? 0));

        $maya = $payments
            ->filter(fn($p) => ($p->paymentMethod?->code ?? '') === 'maya')
            ->sum(fn($p) => (float) ($p->amount ?? 0));

        return [
            \Carbon\Carbon::parse($order->created_at)->format('Y-m-d H:i'),
            $order->user?->name ?? '-',
            $tableLabel,
            $customerName,
            $pax,
            (float) ($order->subtotal ?? 0),
            $gcash,
            $cash,
            $maya,
            (float) ($order->subtotal ?? 0),
            (float) ($order->total_discount ?? 0),
            (float) ($order->total ?? 0),
            $order->status ?? '-',
        ];
    }

    public function styles($sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF16A34A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet       = $event->sheet->getDelegate();
                $lastDataRow = $this->orders->count() + 1; // +1 for header
                $summaryStart = $lastDataRow + 3;

                // ── Build summary rows dynamically ────────────────────────
                $summaries = [
                    ['Label',            'Amount'],
                    ['Gross Sales',      $this->grossSales],
                    ['Total Expenses',   $this->totalExpenses],
                    ['Net Sales',        $this->netSales],
                    ['', ''],
                    ['Payment Breakdown', ''],
                ];

                foreach ($this->paymentTotals as $methodName => $amount) {
                    $summaries[] = [$methodName, (float) $amount];
                }

                foreach ($summaries as $i => $row) {
                    $r = $summaryStart + $i;
                    $sheet->setCellValue("L{$r}", $row[0]);
                    $sheet->setCellValue("M{$r}", $row[1]);
                }

                // Header row styling
                $sheet->getStyle("L{$summaryStart}:M{$summaryStart}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1D4ED8']],
                ]);

                // Currency format for amount column
                $summaryEnd  = $summaryStart + count($summaries) - 1;
                $amountRows  = range($summaryStart + 1, $summaryEnd);
                foreach ($amountRows as $r) {
                    $sheet->getStyle("M{$r}")
                        ->getNumberFormat()
                        ->setFormatCode('₱#,##0.00');
                }

                // Bold Net Sales row
                $netRow = $summaryStart + 3;
                $sheet->getStyle("L{$netRow}:M{$netRow}")->getFont()->setBold(true);

                // Border around entire summary block
                $sheet->getStyle("L{$summaryStart}:M{$summaryEnd}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FFD1D5DB'],
                        ],
                    ],
                ]);

                // ── Currency format for data rows ─────────────────────────
                $currencyCols = ['F', 'G', 'H', 'I', 'J', 'K', 'L'];
                foreach ($currencyCols as $col) {
                    $sheet->getStyle("{$col}2:{$col}{$lastDataRow}")
                        ->getNumberFormat()
                        ->setFormatCode('₱#,##0.00');
                }

                // ── Stripe cancelled rows red ─────────────────────────────
                foreach ($this->orders as $index => $order) {
                    if (($order->status ?? '') === 'cancelled') {
                        $row = $index + 2;
                        $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
                            'fill' => [
                                'fillType'   => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFFEE2E2'],
                            ],
                            'font' => ['color' => ['argb' => 'FF9CA3AF']],
                        ]);
                    }
                }

                // ── Report title at the very top ──────────────────────────
                $sheet->insertNewRowBefore(1, 2);
                $sheet->setCellValue('A1', 'Sales Report');
                $sheet->setCellValue('A2', 'Period: ' . $this->startDate . ' to ' . $this->endDate);
                $sheet->mergeCells('A1:M1');
                $sheet->mergeCells('A2:M2');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['italic' => true, 'color' => ['argb' => 'FF6B7280']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            },
        ];
    }
}