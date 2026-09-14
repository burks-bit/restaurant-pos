<?php

namespace App\Exports\Sheets;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollSummarySheet implements FromCollection, WithHeadings, WithTitle, WithColumnFormatting, WithStyles
{
    protected $payroll;

    public function __construct(Payroll $payroll)
    {
        $this->payroll = $payroll;
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function headings(): array
    {
        return [
            'Employee Code',
            'Employee Name',
            'Days',
            'Hours',
            'Overtime (hrs)',
            'Late (hrs)',
            'Undertime (hrs)',
            'Basic Pay',
            'Overtime Pay',
            'Allowances',
            'Gross Pay',
            'Total Earnings',
            'Total Deductions',
            'Net Pay',
        ];
    }

    public function collection()
    {
        return $this->payroll->items
            ->sortBy(fn ($item) => strtolower($item->employee?->first_name ?? ''))
            ->map(function ($item) {
                $totalEarnings   = (float) $item->earnings->sum('amount');
                $totalDeductions = (float) $item->deductions->sum('amount');

                return [
                    $item->employee?->employee_code ?? '',
                    trim(($item->employee?->first_name ?? '') . ' ' . ($item->employee?->last_name ?? '')),
                    (int) $item->days,
                    (float) $item->hours,
                    (float) $item->overtime_hours,
                    (float) $item->late,
                    (float) $item->undertime,
                    (float) $item->basic_pay,
                    (float) $item->overtime_pay,
                    (float) $item->allowances,
                    (float) $item->gross_pay,
                    $totalEarnings,
                    $totalDeductions,
                    // Net pay is derived from the live earnings/deductions every
                    // time the workbook is generated, so it always reflects the
                    // latest inputs (gross + earnings - deductions).
                    (float) $item->gross_pay + $totalEarnings - $totalDeductions,
                ];
            })
            ->values();
    }

    public function columnFormats(): array
    {
        // H = Basic Pay, I = Overtime Pay, J = Allowances, K = Gross Pay,
        // L = Total Earnings, M = Total Deductions, N = Net Pay
        return [
            'H' => NumberFormat::FORMAT_NUMBER_00,
            'I' => NumberFormat::FORMAT_NUMBER_00,
            'J' => NumberFormat::FORMAT_NUMBER_00,
            'K' => NumberFormat::FORMAT_NUMBER_00,
            'L' => NumberFormat::FORMAT_NUMBER_00,
            'M' => NumberFormat::FORMAT_NUMBER_00,
            'N' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}