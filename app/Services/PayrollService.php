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
use App\Exports\PayrollExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
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

        $payroll->setRelation(
            'items',
            $payroll->items->sortBy(fn ($item) => $item->employee?->first_name)->values()
        );

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

    public function exportExcel(Payroll $payroll)
    {
        $payroll->load(['items.earnings', 'items.deductions', 'items.employee']);

        $cutoff = \Carbon\Carbon::parse($payroll->cutoff_start)->format('Y-m-d')
            . ' - ' . \Carbon\Carbon::parse($payroll->cutoff_end)->format('Y-m-d');

        $filename = "payroll_{$payroll->id}_{$cutoff}.xlsx";

        return Excel::download(new PayrollExport($payroll), $filename);
    }

    /**
     * Import a payroll Excel file (the one downloaded from this page) and apply
     * the Earnings / Deductions values back onto the payroll, then recompute
     * net pay for every employee automatically.
     */
    public function importExcel(Request $request, Payroll $payroll)
    {
        // Accept any file (the user may pick any type in the file dialog);
        // the actual Excel readability is validated below.
        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $file = $request->file('file');

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file could not be read as an Excel file. Please upload the Excel file downloaded from this page.',
            ]);
        }

        $payroll->load(['items.employee', 'items.earnings', 'items.deductions']);

        // Look up payroll items by employee code (column A of each sheet).
        $itemsByCode = [];
        foreach ($payroll->items as $item) {
            $code = strtolower(trim((string) ($item->employee?->employee_code ?? '')));
            if ($code !== '') {
                $itemsByCode[$code] = $item;
            }
        }

        // Case-insensitive type-name maps so header variations still match.
        $earningTypeMap = [];
        foreach (EarningType::all() as $type) {
            $earningTypeMap[strtolower(trim($type->name))] = $type->name;
        }
        $deductionTypeMap = [];
        foreach (DeductionType::all() as $type) {
            $deductionTypeMap[strtolower(trim($type->name))] = $type->name;
        }

        $sheetConfigs = [
            [
                'keyword'   => 'earning',
                'map'       => $earningTypeMap,
                'model'     => PayrollEarning::class,
                'typeField' => 'earning_type',
            ],
            [
                'keyword'   => 'deduction',
                'map'       => $deductionTypeMap,
                'model'     => PayrollDeduction::class,
                'typeField' => 'deduction_type',
            ],
        ];

        $summary = ['employees' => 0, 'earnings' => 0, 'deductions' => 0];
        $matchedSheets = 0;

        DB::beginTransaction();

        try {
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $title = strtolower(trim($sheet->getTitle()));

                foreach ($sheetConfigs as $config) {
                    if (strpos($title, $config['keyword']) === false) {
                        continue;
                    }
                    $matchedSheets++;

                    // Rows keyed by Excel column letter (A, B, C, ...).
                    $rows = $sheet->toArray(null, true, true, true);
                    if (count($rows) < 2) {
                        continue;
                    }

                    $header = array_shift($rows);

                    // Map header columns to earning/deduction type names.
                    $typeColumns = [];
                    foreach ($header as $colLetter => $label) {
                        $label = strtolower(trim((string) $label));
                        if ($label === '' || in_array($label, ['employee code', 'employee name', 'total'], true)) {
                            continue;
                        }
                        $typeColumns[$colLetter] = $config['map'][$label] ?? trim((string) $header[$colLetter]);
                    }

                    foreach ($rows as $row) {
                        $code = strtolower(trim((string) ($row['A'] ?? '')));
                        if ($code === '' || !isset($itemsByCode[$code])) {
                            continue;
                        }

                        $item = $itemsByCode[$code];
                        $itemChanged = false;

                        foreach ($typeColumns as $colLetter => $type) {
                            // Strip thousand separators / currency symbols, keep digits.
                            $raw = preg_replace('/[^0-9.\-]/', '', (string) ($row[$colLetter] ?? '0'));
                            $amount = (float) ($raw === '' ? 0 : $raw);

                            if ($amount > 0) {
                                $config['model']::updateOrCreate(
                                    [
                                        'payroll_item_id' => $item->id,
                                        $config['typeField'] => $type,
                                    ],
                                    ['amount' => $amount]
                                );
                                $summary[$config['keyword'] === 'earning' ? 'earnings' : 'deductions']++;
                                $itemChanged = true;
                            } else {
                                // Amount cleared to 0/blank => remove the row.
                                $deleted = $config['model']::where('payroll_item_id', $item->id)
                                    ->where($config['typeField'], $type)
                                    ->delete();
                                if ($deleted) {
                                    $itemChanged = true;
                                }
                            }
                        }

                        if ($itemChanged) {
                            $summary['employees']++;
                        }
                    }
                }
            }

            if ($matchedSheets === 0) {
                DB::rollBack();
                throw ValidationException::withMessages([
                    'file' => 'This file does not look like a payroll export (no Earnings or Deductions sheets found). Please upload the Excel file downloaded from this page.',
                ]);
            }

            // Recompute totals and net pay for every payroll item so the stored
            // values always match the imported earnings/deductions.
            foreach ($payroll->items as $item) {
                $item->refresh();

                $totalEarnings   = (float) $item->earnings()->sum('amount');
                $totalDeductions = (float) $item->deductions()->sum('amount');

                $item->total_deductions = $totalDeductions;
                $item->net_pay          = (float) $item->gross_pay + $totalEarnings - $totalDeductions;
                $item->save();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'message' => 'Payroll Excel imported successfully. Earnings, deductions and net pay have been updated.',
            'summary' => $summary,
        ]);
    }
}