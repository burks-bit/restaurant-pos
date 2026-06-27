<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollEarning;
use App\Models\PayrollDeduction;
use App\Models\EarningType;
use App\Models\DeductionType;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Mpdf\Mpdf;

class PayrollService
{
    public function payrollIndex()
    {
        $payrolls = Payroll::withCount('items')
            ->orderBy('cutoff_start', 'desc')
            ->get();

        return Inertia::render('Payrolls/PayrollIndex', [
            'payrolls' => $payrolls
        ]);
    }

    public function postPayroll(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date'   => 'required|date',
            ]);

            $payroll = Payroll::create([
                'cutoff_start' => $request->start_date,
                'cutoff_end'   => $request->end_date,
                'status'       => 'posted',
            ]);

            $totalGross = 0;

            foreach ($request->employees as $key => $employee) {
                $gross = (float) ($employee['total_earnings'] ?? 0);

                PayrollItem::create([
                    'payroll_id'       => $payroll->id,
                    'employee_id'      => $employee['id'],
                    'days'             => (int) $employee['total_days'],
                    'hours'            => (float) $employee['total_hours'],
                    'overtime_hours'   => (float) $employee['overtime_hours'],
                    'basic_pay'        => $gross,
                    'overtime_pay'     => 0,
                    'allowances'       => 0,
                    'gross_pay'        => $gross,
                    'total_deductions' => 0,
                    'net_pay'          => $gross,
                ]);

                $totalGross += $gross;
            }

            DB::commit();

            return response()->json([
                'message' => 'Payroll posted successfully.',
                'payroll_id' => $payroll->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to post payroll.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function viewPayrollDetails(Payroll $payroll)
    {
        $earning_types = EarningType::where('status', true)->get()->toArray();
        $deduction_types = DeductionType::where('status', true)->get()->toArray();

        $payroll->load([
            'items.earnings',
            'items.deductions',
            'items.employee',
        ]);

        return Inertia::render('Payrolls/PayrollDetail', [
            'payroll' => $payroll,
            'earning_types' => $earning_types,
            'deduction_types' => $deduction_types,
        ]);
    }

    public function postEmployeeEarnings(Request $request, Payroll $payroll)
    {
        $data = $request->validate([
            'payroll_item_id' => 'required|exists:payroll_items,id',
            'employee_id'     => 'required|exists:employees,id',
            'amounts'         => 'nullable|array',
            'amounts.*'       => 'nullable|numeric|min:0',
        ]);

        $item = PayrollItem::where('payroll_id', $payroll->id)
            ->where('employee_id', $data['employee_id'])
            ->firstOrFail();

        $amounts = $data['amounts'] ?? [];

        foreach ($amounts as $type => $amount) {
            $amount = (float) ($amount ?? 0);

            if ($amount > 0) {
                PayrollEarning::updateOrCreate(
                    [
                        'payroll_item_id' => $item->id,
                        'earning_type'    => $type,
                    ],
                    [
                        'amount' => $amount,
                    ]
                );
            } else {
                PayrollEarning::where('payroll_item_id', $item->id)
                    ->where('earning_type', $type)
                    ->delete();
            }
        }

        $totalEarnings   = $item->earnings()->sum('amount');
        $totalDeductions = $item->deductions()->sum('amount');

        // $item->total_earnings   = $totalEarnings;
        $item->total_deductions = $totalDeductions;
        $item->net_pay          = $item->gross_pay + $totalEarnings - $totalDeductions;
        $item->save();

        return response()->json([
            'message'      => 'Earnings saved successfully.',
            'payroll_item' => $item->load(['earnings', 'deductions']),
        ]);
    }

    public function postEmployeeDeduction(Request $request, Payroll $payroll)
    {
        $data = $request->validate([
            'payroll_item_id' => 'required|exists:payroll_items,id',
            'employee_id'     => 'required|exists:employees,id',
            'amounts'         => 'nullable|array',
            'amounts.*'       => 'nullable|numeric|min:0',
        ]);

        $item = PayrollItem::where('payroll_id', $payroll->id)
            ->where('employee_id', $data['employee_id'])
            ->firstOrFail();

        $amounts = $data['amounts'] ?? [];

        foreach ($amounts as $type => $amount) {
            $amount = (float) ($amount ?? 0);

            if ($amount > 0) {
                PayrollDeduction::updateOrCreate(
                    [
                        'payroll_item_id' => $item->id,
                        'deduction_type'  => $type,
                    ],
                    [
                        'amount' => $amount,
                    ]
                );
            } else {
                // Remove the deduction row if amount was cleared to 0
                PayrollDeduction::where('payroll_item_id', $item->id)
                    ->where('deduction_type', $type)
                    ->delete();
            }
        }

        $totalEarnings   = $item->earnings()->sum('amount');
        $totalDeductions = $item->deductions()->sum('amount');

        // $item->total_earnings   = $totalEarnings;
        $item->total_deductions = $totalDeductions;
        $item->net_pay          = $item->gross_pay + $totalEarnings - $totalDeductions;
        $item->save();

        return response()->json([
            'message' => 'Deductions saved successfully.',
            'item'    => $item->load(['earnings', 'deductions']),
        ]);
    }

    public function printPayslipSingle(Payroll $payroll, PayrollItem $payrollItem)
    {
        abort_unless($payrollItem->payroll_id === $payroll->id, 404);
        $branch = Branch::where('main', 1)->first();
        $payrollItem->load(['employee.latestEmployment', 'deductions', 'earnings']);
        $cutoff = \Carbon\Carbon::parse($payroll->cutoff_start)->format('F d') . ' - ' . \Carbon\Carbon::parse($payroll->cutoff_end)->format('F d, Y');
        
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5,
        ]);

        $html = view('payroll.payslip-single', [
            'payroll' => $payroll,
            'item' => $payrollItem,
            'branch' => $branch,
            'cutoff' => $cutoff,
            'roles' => User::roles(),
        ])->render();

        $mpdf->WriteHTML($html);

        return response($mpdf->Output("payslip-{$payrollItem->id}.pdf", 'S'))
            ->header('Content-Type', 'application/pdf');
    }

    public function printPayslipSelected(Request $request, Payroll $payroll)
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || count($ids) === 0) {
            abort(422, 'No employees selected.');
        }

        // $roleName = User::roles()[$payrollItem->employee->access] ?? 'Unknown Role';
        $roleName = null;
        

        $branch = Branch::where('main', 1)->first();

        $items = PayrollItem::with(['employee.latestEmployment', 'deductions', 'earnings'])
            ->where('payroll_id', $payroll->id)
            ->whereIn('id', $ids)
            ->orderBy('employee_id')
            ->get();

        $cutoff = \Carbon\Carbon::parse($payroll->cutoff_start)->format('F d')
            . ' - '
            . \Carbon\Carbon::parse($payroll->cutoff_end)->format('F d, Y');

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 5,
            'margin_right'  => 5,
            'margin_top'    => 5,
            'margin_bottom' => 5,
        ]);

        $html = view('payroll.payslip-batch', [
            'payroll' => $payroll,
            'items'   => $items,
            'branch'  => $branch,
            'cutoff'  => $cutoff,
            'roles' => User::roles(),
        ])->render();

        $mpdf->WriteHTML($html);

        return response($mpdf->Output("payslips-payroll-{$payroll->id}.pdf", 'S'))
            ->header('Content-Type', 'application/pdf');
    }
}