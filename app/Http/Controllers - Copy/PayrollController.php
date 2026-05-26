<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollDeduction;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Mpdf\Mpdf;

class PayrollController extends Controller
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
        Log::info($request->all());
        DB::beginTransaction();

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

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT
            | Replace this with your real payroll computation logic
            |--------------------------------------------------------------------------
            */
            $totalGross = 0;

            foreach ($request->all() as $key => $employee) {

                // Only process numeric indexes (employees)
                if (!is_numeric($key)) {
                    continue;
                }

                $gross = (float) $employee['total_earnings'];

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
        // Load items and deductions
        $payroll->load([
            'items.deductions',
            'items.employee',
        ]);
        Log::info($payroll);

        return Inertia::render('Payrolls/PayrollDetail', [
            'payroll' => $payroll // match what your Vue expects
        ]);
    }

    public function postEmployeeDeduction(Request $request, Payroll $payroll)
    {
        // Log incoming data for debugging
        Log::info([
            'payroll' => $payroll->toArray(),
            'request' => $request->all(),
        ]);

        // Validate incoming data
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'lates'       => 'nullable|numeric',
            'philhealth'  => 'nullable|numeric',
            'sss'         => 'nullable|numeric',
            'pagibig'     => 'nullable|numeric',
            'tax'         => 'nullable|numeric',
            'loan'        => 'nullable|numeric',
        ]);

        // Find the payroll item for this employee in the given payroll
        $item = PayrollItem::where('payroll_id', $payroll->id)
            ->where('employee_id', $data['employee_id'])
            ->firstOrFail();

        // Map each deduction field to its type
        $deductionMap = [
            'lates'      => 'lates',
            'philhealth' => 'philhealth',
            'sss'        => 'sss',
            'pagibig'    => 'pagibig',
            'tax'        => 'tax',
            'loan'       => 'loan',
        ];

        // Create or update a row for each non-empty deduction
        foreach ($deductionMap as $field => $type) {
            if (!empty($data[$field])) {
                PayrollDeduction::updateOrCreate(
                    [
                        'payroll_item_id' => $item->id,
                        'deduction_type'  => $type,
                    ],
                    [
                        'amount' => $data[$field],
                    ]
                );
            }
        }

        // Recalculate totals from all deductions
        $item->total_deductions = $item->deductions()->sum('amount');
        $item->net_pay = $item->gross_pay - $item->total_deductions;
        $item->save();

        return response()->json([
            'message'   => 'Deductions saved successfully.',
            'item'      => $item->load('deductions'),
        ]);
    }

    public function printPayslipSingle(Payroll $payroll, PayrollItem $payrollItem)
    {
        abort_unless($payrollItem->payroll_id === $payroll->id, 404);

        $payrollItem->load(['employee', 'deductions']);

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $html = view('payroll.payslip-single', [
            'payroll' => $payroll,
            'item' => $payrollItem,
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

        $items = PayrollItem::with(['employee', 'deductions'])
            ->where('payroll_id', $payroll->id)
            ->whereIn('id', $ids)
            ->orderBy('employee_id')
            ->get();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $html = view('payroll.payslip-batch', [
            'payroll' => $payroll,
            'items' => $items,
        ])->render();

        $mpdf->WriteHTML($html);

        return response($mpdf->Output("payslips-payroll-{$payroll->id}.pdf", 'S'))
            ->header('Content-Type', 'application/pdf');
    }
}
