<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeDocument;
use App\Models\EmployeeOvertime;
use App\Models\User;
use App\Models\Branch;
use Inertia\Inertia;
use Carbon\Carbon;
use Mpdf\Mpdf;

class DTRController extends Controller
{
    public function generatePayroll(){
        return Inertia::render('Payrolls/PayrollGenerate', [
            // 'employees' => $employees
        ]);
    }

    public function clockIn(Request $request)
    {
        $employee = auth()->user()->employee;

        $today = Carbon::now()->toDateString(); // safer

        $schedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->whereDate('schedule_date', $today)
            ->first();

        if (!$schedule) {
            return response()->json([
                'message' => 'No schedule found for today.'
            ], 404);
        }

        if ($schedule->actual_time_in) {
            return response()->json([
                'message' => 'Already clocked in.'
            ], 422);
        }

        $schedule->update([
            'actual_time_in' => now()->format('H:i:s') // cleaner helper
        ]);

        return response()->json([
            'message' => 'Clocked in successfully.',
            'schedule' => $schedule
        ]);
    }

    public function clockOut(Request $request)
    {
        $employee = auth()->user()->employee;

        $today = Carbon::now()->toDateString(); // safer

        $schedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->whereDate('schedule_date', $today)
            ->first();

        if (!$schedule) {
            return response()->json([
                'message' => 'No schedule found for today.'
            ], 404);
        }

        if ($schedule->actual_time_out) {
            return response()->json([
                'message' => 'Already clocked out.'
            ], 422);
        }

        $schedule->update([
            'actual_time_out' => now()->format('H:i:s') // cleaner helper
        ]);

        return response()->json([
            'message' => 'Clocked out successfully.',
            'schedule' => $schedule
        ]);
    }

    public function getAllEmployeeOvertime(){
        $employees = Employee::with(['overtimes'])->get();

        return Inertia::render('Employees/EmployeesOvertime', [
            'employees' => $employees
        ]);
    }

    public function saveOvertime(Request $request)
    {
        $request->validate([
            'employee_schedule_id' => 'required|exists:employee_schedules,id',
            'ot_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'type' => 'required',
        ]);

        $start = Carbon::parse($request->start_time);
        $end   = Carbon::parse($request->end_time);

        $totalHours = $start->floatDiffInHours($end);

        $overtime = EmployeeOvertime::create([
            'employee_id' => $request->employee_id,
            'employee_schedule_id' => $request->employee_schedule_id,
            'ot_date' => $request->ot_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'total_hours' => $totalHours,
            'type' => $request->type,
            'status' => 'approved',
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'message' => 'Overtime filed successfully.',
            'overtime' => $overtime
        ]);
    }

    // public function saveOvertime(Request $request)
    // {
    //     $request->validate([
    //         'employee_schedule_id' => 'required|exists:employee_schedules,id',
    //         'ot_date' => 'required|date',
    //         'start_time' => 'required',
    //         'end_time' => 'required|after:start_time',
    //         'type' => 'required',
    //     ]);

    //     EmployeeOvertime::create([
    //         'employee_id' => auth()->user()->employee->id,
    //         'employee_schedule_id' => $request->employee_schedule_id,
    //         'ot_date' => $request->ot_date,
    //         'start_time' => $request->start_time,
    //         'end_time' => $request->end_time,
    //         'total_hours' => $request->total_hours,
    //         'type' => $request->type,
    //         'status' => 'approved',
    //         'remarks' => $request->remarks,
    //     ]);

    //     return response()->json([
    //         'message' => 'Overtime filed successfully.'
    //     ]);
    // }

    public function filterAllEmployeeOvertime(Request $request)
    {
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $employees = Employee::with(['overtimes' => function($query) use ($startDate, $endDate) {
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }
        }])
        ->when($search, function($query, $search) {
            $query->where('first_name', 'like', "%$search%")
                ->orWhere('last_name', 'like', "%$search%")
                ->orWhere('employee_code', 'like', "%$search%");
        })
        ->get();

        return response()->json(['employees' => $employees]);
    }

    public function updateEmployeeOTStatus(Request $request, $id)
    {
        Log::info('updateEmployeeOTStatus');
        Log::info($id);
        Log::info($request);

        $ot = \App\Models\EmployeeOvertime::findOrFail($id);
        $ot->status = $request->status;
        $ot->save();

        return response()->json(['message' => 'Overtime status updated successfully.']);
    }

    // ==================== PAYROLL SUMMARY ====================
    public function summary(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $start = Carbon::parse($request->start_date)->startOfDay();
        $end   = Carbon::parse($request->end_date)->endOfDay();

        $employees = Employee::with([
            'overtimes' => function ($q) use ($start, $end) {
                $q->where('status', 'approved')
                ->whereBetween('created_at', [$start, $end]);
            },
            'employmentDetails',
            'schedules' => function ($q) use ($start, $end) {
                $q->whereBetween('schedule_date', [$start, $end]);
            }
        ])->get();

        $payrollData = [];

        foreach ($employees as $emp) {

            $totalDays = 0;
            $totalHours = 0;
            $totalLate = 0;
            $totalUndertime = 0;
            $totalEarnings = 0;
            $overtimeHours = 0;

            $employmentDetail = $emp->employmentDetails->first();
            $dailyRate = optional($emp->employmentDetails->first())->daily_rate ?? 0;
            $overtimeRate = optional($employmentDetail)->overtime_rate ?? 1.25;
            // $hourlyRate = $dailyRate / 8;
            $hourlyRate = $dailyRate > 0 ? $dailyRate / 8 : 0;

            // SUM APPROVED OVERTIME HOURS
            $overtimeHours = $emp->overtimes->sum(function ($ot) {
                return (float) $ot->total_hours;
            });

            foreach ($emp->schedules as $schedule) {

                if ($schedule->status === 'Absent') continue;
                if (!$schedule->actual_time_in || !$schedule->actual_time_out) continue;

                $timeIn = Carbon::parse($schedule->actual_time_in);
                $timeOut = Carbon::parse($schedule->actual_time_out);
                $shiftStart = Carbon::parse($schedule->time_in);
                $shiftEnd = Carbon::parse($schedule->time_out);

                $workedHours = max(0, $timeIn->floatDiffInHours($timeOut));

                $totalHours += $workedHours;
                $totalDays++;

                // LATE
                if ($timeIn->gt($shiftStart)) {
                    $totalLate += $shiftStart->diffInMinutes($timeIn);
                }

                // UNDERTIME
                if ($timeOut->lt($shiftEnd)) {
                    $totalUndertime += $timeOut->diffInMinutes($shiftEnd);
                }

                // MULTIPLIER
                $multiplier = 1;
                if ($schedule->holiday_type === 'Regular') {
                    $multiplier = 2;
                } elseif ($schedule->holiday_type === 'Special') {
                    $multiplier = 1.3;
                }
                if ($schedule->is_rest_day) {
                    $multiplier *= 1.3;
                }

                // DAILY EARNINGS
                $dayEarnings = min($workedHours, 8) * $hourlyRate * $multiplier;

                $totalEarnings += $dayEarnings;
            }

            // ADD APPROVED OVERTIME PAY
            $overtimePay = $overtimeHours * $overtimeRate;
            $totalEarnings += $overtimePay;

            $payrollData[] = [
                'id' => $emp->id,
                'employee_code' => $emp->employee_code,
                'first_name' => $emp->first_name,
                'last_name' => $emp->last_name,
                'total_days' => $totalDays,
                'total_hours' => round($totalHours, 2),
                'total_late' => $totalLate,
                'total_undertime' => $totalUndertime,
                'overtime_hours' => round($overtimeHours, 2),
                'total_earnings' => round($totalEarnings, 2),
            ];
        }

        return response()->json($payrollData);
    }

    // public function summaryorig(Request $request)
    // {
    //     $request->validate([
    //         'start_date' => 'required|date',
    //         'end_date'   => 'required|date|after_or_equal:start_date',
    //     ]);

    //     $start = Carbon::parse($request->start_date)->startOfDay();
    //     $end   = Carbon::parse($request->end_date)->endOfDay();

    //     $employees = Employee::with([
    //         'overtimes',
    //         'employmentDetails',
    //         'schedules' => function ($q) use ($start, $end) {
    //             $q->whereBetween('schedule_date', [$start, $end]);
    //         }
    //     ])->get();

    //     $payrollData = [];

    //     foreach ($employees as $emp) {

    //         $totalDays = 0;
    //         $totalHours = 0;
    //         $totalLate = 0;
    //         $totalUndertime = 0;
    //         $totalEarnings = 0;
    //         $overtimeHours = 0;

    //         $dailyRate = optional($emp->employmentDetails->first())->daily_rate ?? 0;
    //         $hourlyRate = $dailyRate / 8;

    //         foreach ($emp->schedules as $schedule) {

    //             if ($schedule->status === 'Absent') continue;
    //             if (!$schedule->actual_time_in || !$schedule->actual_time_out) continue;

    //             $timeIn = Carbon::parse($schedule->actual_time_in);
    //             $timeOut = Carbon::parse($schedule->actual_time_out);
    //             $shiftStart = Carbon::parse($schedule->time_in);
    //             $shiftEnd = Carbon::parse($schedule->time_out);

    //             // KEEP floatDiffInHours
    //             $workedHours = max(0, $timeIn->floatDiffInHours($timeOut));

    //             $totalHours += $workedHours;
    //             $totalDays++;

    //             // OVERTIME
    //             // if ($workedHours > 8) {
    //             //     $overtimeHours += ($workedHours - 8);
    //             // }

    //             // LATE
    //             if ($timeIn->gt($shiftStart)) {
    //                 $totalLate += $shiftStart->diffInMinutes($timeIn);
    //             }

    //             // UNDERTIME
    //             if ($timeOut->lt($shiftEnd)) {
    //                 $totalUndertime += $timeOut->diffInMinutes($shiftEnd);
    //             }

    //             // MULTIPLIER
    //             $multiplier = 1;

    //             if ($schedule->holiday_type === 'Regular') {
    //                 $multiplier = 2;
    //             } elseif ($schedule->holiday_type === 'Special') {
    //                 $multiplier = 1.3;
    //             }

    //             if ($schedule->is_rest_day) {
    //                 $multiplier *= 1.3;
    //             }

    //             // DAILY EARNINGS
    //             $dayEarnings = min($workedHours, 8) * $hourlyRate * $multiplier;

    //             // OVERTIME PAY
    //             if ($workedHours > 8) {
    //                 $otHours = $workedHours - 8;
    //                 $dayEarnings += $otHours * $hourlyRate * 1.25 * $multiplier;
    //             }

    //             $totalEarnings += $dayEarnings;
    //         }

    //         $payrollData[] = [
    //             'id' => $emp->id,
    //             'employee_code' => $emp->employee_code,
    //             'first_name' => $emp->first_name,
    //             'last_name' => $emp->last_name,
    //             'total_days' => $totalDays,
    //             'total_hours' => round($totalHours, 2),
    //             'total_late' => $totalLate,
    //             'total_undertime' => $totalUndertime,
    //             // 'overtime_hours' => round($overtimeHours, 2),
    //             'total_earnings' => round($totalEarnings, 2),
    //         ];
    //     }

    //     return response()->json($payrollData);
    // }

    // ==================== PRINT PAYROLL ====================
    public function printPayrollSummary(Request $request)
    {
        // Validate request
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $start = Carbon::parse($request->start_date)->startOfDay();
        $end   = Carbon::parse($request->end_date)->endOfDay();

        // Get employees with schedules in range
        $employees = Employee::with([
            'employmentDetails',
            'schedules' => function ($q) use ($start, $end) {
                $q->whereBetween('schedule_date', [$start, $end]);
            }
        ])->get();

        $payrollData = [];

        foreach ($employees as $emp) {
            $totalDays = 0;
            $totalHours = 0;
            $totalLate = 0;
            $totalUndertime = 0;
            $totalEarnings = 0;
            $overtimeHours = 0;

            $dailyRate = optional($emp->employmentDetails->first())->daily_rate ?? 0;
            $hourlyRate = $dailyRate / 8;

            foreach ($emp->schedules as $schedule) {
                if ($schedule->status === 'Absent') continue;
                if (!$schedule->actual_time_in || !$schedule->actual_time_out) continue;

                $timeIn = Carbon::parse($schedule->actual_time_in);
                $timeOut = Carbon::parse($schedule->actual_time_out);
                $shiftStart = Carbon::parse($schedule->time_in);
                $shiftEnd = Carbon::parse($schedule->time_out);

                $workedHours = max(0, $timeIn->floatDiffInHours($timeOut));

                $totalHours += $workedHours;
                $totalDays++;

                // OVERTIME
                // if ($workedHours > 8) {
                //     $overtimeHours += ($workedHours - 8);
                // }

                // LATE
                if ($timeIn->gt($shiftStart)) {
                    $totalLate += $shiftStart->diffInMinutes($timeIn);
                }

                // UNDERTIME
                if ($timeOut->lt($shiftEnd)) {
                    $totalUndertime += $timeOut->diffInMinutes($shiftEnd);
                }

                // MULTIPLIER
                $multiplier = 1;
                if ($schedule->holiday_type === 'Regular') {
                    $multiplier = 2;
                } elseif ($schedule->holiday_type === 'Special') {
                    $multiplier = 1.3;
                }

                if ($schedule->is_rest_day) {
                    $multiplier *= 1.3;
                }

                // DAILY EARNINGS
                $dayEarnings = min($workedHours, 8) * $hourlyRate * $multiplier;

                // OVERTIME PAY
                if ($workedHours > 8) {
                    $otHours = $workedHours - 8;
                    $dayEarnings += $otHours * $hourlyRate * 1.25 * $multiplier;
                }

                $totalEarnings += $dayEarnings;
            }

            $payrollData[] = [
                'id' => $emp->id,
                'employee_code' => $emp->employee_code,
                'first_name' => $emp->first_name,
                'last_name' => $emp->last_name,
                'total_days' => $totalDays,
                'total_hours' => round($totalHours, 2),
                'total_late' => $totalLate,
                'total_undertime' => $totalUndertime,
                // 'overtime_hours' => round($overtimeHours, 2),
                'total_earnings' => round($totalEarnings, 2),
            ];
        }
        $branch = Branch::where('main', 1)->first();
        $generated_by = auth()->user()->name ?? 'System'; // Get authenticated user's name

        // Generate PDF
        // $mpdf = new Mpdf();

        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4-L', // Landscape
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);
        $html = view('payroll.summary', compact('payrollData', 'start', 'end', 'branch', 'generated_by'))->render();
        $mpdf->WriteHTML($html);

        // Open in browser
        return $mpdf->Output('Payroll-Summary.pdf', \Mpdf\Output\Destination::INLINE);
    }
}
