<?php

namespace App\Exports\Sheets;

use App\Models\EarningType;
use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollEarningsSheet implements FromCollection, WithHeadings, WithTitle, WithColumnFormatting, WithStyles
{
    protected $payroll;

    public function __construct(Payroll $payroll)
    {
        $this->payroll = $payroll;
    }

    /**
     * All active earning types (from the earning_types table), in the same
     * order shown on the payroll details page. Map of type name => column label.
     */
    protected function earningTypes(): array
    {
        $map = [];

        foreach (EarningType::where('status', true)->get() as $type) {
            $map[$type->name] = $type->name;
        }

        return $map;
    }

    public function title(): string
    {
        return 'Earnings';
    }

    public function headings(): array
    {
        return array_merge(
            ['Employee Code', 'Employee Name'],
            array_values($this->earningTypes()),
            ['Total']
        );
    }

    public function collection()
    {
        $types = $this->earningTypes();

        return $this->payroll->items
            ->sortBy(fn ($item) => strtolower($item->employee?->first_name ?? ''))
            ->map(function ($item) use ($types) {
                $amounts = [];

                foreach (array_keys($types) as $type) {
                    $earning = $item->earnings->firstWhere('earning_type', $type);
                    $amounts[] = $earning ? (float) $earning->amount : 0;
                }

                return array_merge(
                    [
                        $item->employee?->employee_code ?? '',
                        trim(($item->employee?->first_name ?? '') . ' ' . ($item->employee?->last_name ?? '')),
                    ],
                    $amounts,
                    [(float) $item->earnings->sum('amount')]
                );
            })
            ->values();
    }

    public function columnFormats(): array
    {
        // A = Employee Code, B = Employee Name, then one column per earning type,
        // then the Total column. All amount columns get a numeric (2-decimal) format.
        $formats = [];
        $totalColumns = 2 + count($this->earningTypes()) + 1;

        for ($i = 3; $i <= $totalColumns; $i++) {
            $formats[Coordinate::stringFromColumnIndex($i)] = NumberFormat::FORMAT_NUMBER_00;
        }

        return $formats;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}