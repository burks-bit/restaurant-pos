<?php

namespace App\Exports;

use App\Exports\Sheets\PayrollDeductionsSheet;
use App\Exports\Sheets\PayrollEarningsSheet;
use App\Exports\Sheets\PayrollSummarySheet;
use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PayrollExport implements WithMultipleSheets
{
    protected $payroll;

    public function __construct(Payroll $payroll)
    {
        $this->payroll = $payroll->load([
            'items.earnings',
            'items.deductions',
            'items.employee',
        ]);
    }

    /**
     * Workbook sheets:
     *  - Summary   : one row per employee with days / overtime / gross / net / deductions
     *  - Earnings  : itemized earnings per employee
     *  - Deductions: itemized deductions per employee
     */
    public function sheets(): array
    {
        return [
            new PayrollSummarySheet($this->payroll),
            new PayrollEarningsSheet($this->payroll),
            new PayrollDeductionsSheet($this->payroll),
        ];
    }
}