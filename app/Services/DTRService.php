<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeOvertime;
use App\Models\Branch;
use Inertia\Inertia;
use Carbon\Carbon;

class DTRService
{
    public function generatePayroll()
    {
        return Inertia::render('Payrolls/PayrollGenerate', [
            'branches' => Branch::all()
        ]);
    }

    public function clockIn(Request $request)
    {
        Log::info('clockin');
        Log::info($request->all());

        $photoPath = null;

        // ✅ ALWAYS SAVE IMAGE FIRST
        if ($request->hasFile('photo')) {
            try {
                $photoPath = $request->file('photo')->store('attendance_photo_images', 'public');

                Log::info('Photo saved at: ' . $photoPath);
            } catch (\Exception $e) {
                Log::error('Photo save failed: ' . $e->getMessage());
            }
        } else {
            Log::warning('No photo received');
        }

        $employee = auth()->user()->employee;
        $today = Carbon::now()->toDateString();

        $schedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->whereDate('schedule_date', $today)
            ->first();

        // ❌ NO SCHEDULE
        if (!$schedule) {
            return response()->json([
                'message' => 'No schedule found for today.',
                'photo_path' => $photoPath // 👈 return for testing
            ], 404);
        }

        // ❌ ALREADY CLOCKED IN
        if ($schedule->actual_time_in) {
            return response()->json([
                'message' => 'Already clocked in.',
                'photo_path' => $photoPath // 👈 return for testing
            ], 422);
        }

        // ✅ NORMAL CLOCK IN
        $schedule->update([
            'actual_time_in' => now()->format('H:i:s'),
            'time_in_photo' => $photoPath // 👈 save actual photo
        ]);

        return response()->json([
            'message' => 'Clocked in successfully.',
            'schedule' => $schedule,
            'photo_path' => $photoPath
        ]);
    }

    public function clockOut(Request $request)
    {
        \Log::info('clockout');
        \Log::info($request->all());

        $photoPath = null;

        // ✅ ALWAYS SAVE IMAGE FIRST (for testing)
        if ($request->hasFile('photo')) {
            try {
                $photoPath = $request->file('photo')->store('attendance_photo_images', 'public');
                \Log::info('Clockout photo saved at: ' . $photoPath);
            } catch (\Exception $e) {
                \Log::error('Clockout photo save failed: ' . $e->getMessage());
            }
        } else {
            \Log::warning('No clockout photo received');
        }

        $employee = auth()->user()->employee;
        $today = \Carbon\Carbon::now()->toDateString();

        $schedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->whereDate('schedule_date', $today)
            ->first();

        // ❌ NO SCHEDULE
        if (!$schedule) {
            return response()->json([
                'message' => 'No schedule found for today.',
                'photo_path' => $photoPath
            ], 404);
        }

        // ❌ NOT YET CLOCKED IN
        if (!$schedule->actual_time_in) {
            return response()->json([
                'message' => 'You must clock in first.',
                'photo_path' => $photoPath
            ], 422);
        }

        // ❌ ALREADY CLOCKED OUT
        if ($schedule->actual_time_out) {
            return response()->json([
                'message' => 'Already clocked out.',
                'photo_path' => $photoPath
            ], 422);
        }

        // ✅ SUCCESS
        $schedule->update([
            'actual_time_out' => now()->format('H:i:s'),
            'time_out_photo' => $photoPath // 👈 add this column
        ]);

        return response()->json([
            'message' => 'Clocked out successfully.',
            'schedule' => $schedule,
            'photo_path' => $photoPath
        ]);
    }

    // public function clockIn-orig-04162026(Request $request)
    // {
    //     Log::info('clockin');
    //     Log::info($request->all());
    //     $employee = auth()->user()->employee;

    //     $today = Carbon::now()->toDateString();

    //     $schedule = EmployeeSchedule::where('employee_id', $employee->id)
    //         ->whereDate('schedule_date', $today)
    //         ->first();

    //     if (!$schedule) {
    //         return response()->json([
    //             'message' => 'No schedule found for today.'
    //         ], 404);
    //     }

    //     if ($schedule->actual_time_in) {
    //         return response()->json([
    //             'message' => 'Already clocked in.'
    //         ], 422);
    //     }

    //     $schedule->update([
    //         'actual_time_in' => now()->format('H:i:s')
    //     ]);

    //     return response()->json([
    //         'message' => 'Clocked in successfully.',
    //         'schedule' => $schedule
    //     ]);
    // }

    // public function clockOut_orig(Request $request)
    // {
    //     $employee = auth()->user()->employee;

    //     $today = Carbon::now()->toDateString();

    //     $schedule = EmployeeSchedule::where('employee_id', $employee->id)
    //         ->whereDate('schedule_date', $today)
    //         ->first();

    //     if (!$schedule) {
    //         return response()->json([
    //             'message' => 'No schedule found for today.'
    //         ], 404);
    //     }

    //     if ($schedule->actual_time_out) {
    //         return response()->json([
    //             'message' => 'Already clocked out.'
    //         ], 422);
    //     }

    //     $schedule->update([
    //         'actual_time_out' => now()->format('H:i:s')
    //     ]);

    //     return response()->json([
    //         'message' => 'Clocked out successfully.',
    //         'schedule' => $schedule
    //     ]);
    // }

    public function getAllEmployeeOvertime()
    {
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
        $end = Carbon::parse($request->end_time);

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

    public function filterAllEmployeeOvertime(Request $request)
    {
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $employees = Employee::with(['overtimes' => function ($query) use ($startDate, $endDate) {
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }
        }])
        ->when($search, function ($query, $search) {
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

        $ot = EmployeeOvertime::findOrFail($id);
        $ot->status = $request->status;
        $ot->save();

        return response()->json(['message' => 'Overtime status updated successfully.']);
    }

    // public function summary(Request $request)
    // {
    //     Log::info('Generating payroll summary with filters:');
    //     Log::info($request->all());
        
    //     $request->validate([
    //         'start_date' => 'required|date',
    //         'end_date'   => 'required|date|after_or_equal:start_date',
    //     ]);

    //     $start = Carbon::parse($request->start_date)->startOfDay();
    //     $end = Carbon::parse($request->end_date)->endOfDay();

    //     $employees = Employee::with([
    //         'overtimes' => function ($q) use ($start, $end) {
    //             $q->where('status', 'approved')
    //                 ->whereBetween('created_at', [$start, $end]);
    //         },
    //         'employmentDetails',
    //         'schedules' => function ($q) use ($start, $end) {
    //             $q->whereBetween('schedule_date', [$start, $end]);
    //         }
    //     ])
    //     ->when(
    //         $request->branch_id !== 'all' && $request->branch_id !== null,
    //         function ($q) use ($request) {

    //             $q->whereHas('employmentDetails', function ($q2) use ($request) {
    //                 $q2->where('branch_id', $request->branch_id);
    //             });

    //         }
    //     )
    //     ->get();

    //     $payrollData = [];

    //     foreach ($employees as $emp) {
    //         $totalDays = 0;
    //         $totalHours = 0;
    //         $totalLate = 0;
    //         $totalUndertime = 0;
    //         $totalEarnings = 0;
    //         $overtimeHours = 0;

    //         $employmentDetail = $emp->employmentDetails->first();
    //         $dailyRate = optional($emp->employmentDetails->first())->daily_rate ?? 0;
    //         $overtimeRate = optional($employmentDetail)->overtime_rate ?? 1.25;
    //         $hourlyRate = $dailyRate > 0 ? $dailyRate / 8 : 0;

    //         $overtimeHours = $emp->overtimes->sum(function ($ot) {
    //             return (float) $ot->total_hours;
    //         });

    //         foreach ($emp->schedules as $schedule) {
    //             if ($schedule->status === 'Absent') continue;
    //             if (!$schedule->actual_time_in || !$schedule->actual_time_out) continue;

    //             $timeIn = Carbon::parse($schedule->actual_time_in);
    //             $timeOut = Carbon::parse($schedule->actual_time_out);
    //             $shiftStart = Carbon::parse($schedule->time_in);
    //             $shiftEnd = Carbon::parse($schedule->time_out);

    //             $workedHours = max(0, $timeIn->floatDiffInHours($timeOut));

    //             $totalHours += $workedHours;
    //             $totalDays++;

    //             if ($timeIn->gt($shiftStart)) {
    //                 $totalLate += $shiftStart->diffInMinutes($timeIn);
    //             }

    //             if ($timeOut->lt($shiftEnd)) {
    //                 $totalUndertime += $timeOut->diffInMinutes($shiftEnd);
    //             }

    //             $multiplier = 1;
    //             if ($schedule->holiday_type === 'Regular') {
    //                 $multiplier = 2;
    //             } elseif ($schedule->holiday_type === 'Special') {
    //                 $multiplier = 1.3;
    //             }

    //             if ($schedule->is_rest_day) {
    //                 $multiplier *= 1.3;
    //             }

    //             $dayEarnings = min($workedHours, 8) * $hourlyRate * $multiplier;

    //             $totalEarnings += $dayEarnings;
    //         }

    //         $overtimePay = $overtimeHours * $overtimeRate;
    //         $totalEarnings += $overtimePay;

    //         $payrollData[] = [
    //             'id' => $emp->id,
    //             'employee_code' => $emp->employee_code,
    //             'first_name' => $emp->first_name,
    //             'last_name' => $emp->last_name,
    //             'total_days' => $totalDays,
    //             'total_hours' => round($totalHours, 2),
    //             'total_late' => $totalLate,
    //             'total_undertime' => $totalUndertime,
    //             'overtime_hours' => round($overtimeHours, 2),
    //             'total_earnings' => round($totalEarnings, 2),
    //         ];
    //     }

    //     return response()->json($payrollData);
    // }
    public function summary(Request $request)
    {
        Log::info('Generating payroll summary with filters:');
        Log::info($request->all());

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
            'latestEmployment', // <-- replace 'employmentDetails'
            'schedules' => function ($q) use ($start, $end) {
                $q->whereBetween('schedule_date', [$start, $end]);
            }
        ])
        ->when(
            $request->branch_id !== 'all' && $request->branch_id !== null,
            function ($q) use ($request) {
                $q->whereHas('employmentDetails', function ($q2) use ($request) {
                    $q2->where('branch_id', $request->branch_id);
                });
            }
        )
        ->get();

        $payrollData = [];

        foreach ($employees as $emp) {
            $totalDays      = 0;
            $totalHours     = 0;
            $totalLate      = 0;
            $totalUndertime = 0;
            $totalEarnings  = 0;
            $overtimeHours  = 0;

            // $employmentDetail = $emp->employmentDetails->first();
            // $dailyRate        = optional($employmentDetail)->daily_rate ?? 0; // get the daily rate in employee.latestEmployment
            $employmentDetail = $emp->latestEmployment;
            $dailyRate        = $employmentDetail->daily_rate ?? 0;
            $overtimeRate     = $employmentDetail->overtime_rate ?? 1.25;
            $hourlyRate       = $dailyRate > 0 ? $dailyRate / 8 : 0;
            // $overtimeRate     = optional($employmentDetail)->overtime_rate ?? 1.25;
            // $hourlyRate       = $dailyRate > 0 ? $dailyRate / 8 : 0;

            $overtimeHours = $emp->overtimes->sum(fn($ot) => (float) $ot->total_hours);

            foreach ($emp->schedules as $schedule) {
                if ($schedule->status === 'Absent') continue;
                if (!$schedule->actual_time_in || !$schedule->actual_time_out) continue;

                $scheduleDate = Carbon::parse($schedule->schedule_date);

                // Use shift_end_date if stored, otherwise fall back to same day
                $shiftEndDate = $schedule->shift_end_date
                    ? Carbon::parse($schedule->shift_end_date)
                    : $scheduleDate->copy();

                // Anchor shift start/end to their correct calendar dates
                $shiftStart = $scheduleDate->copy()->setTimeFromTimeString($schedule->time_in);
                $shiftEnd   = $shiftEndDate->copy()->setTimeFromTimeString($schedule->time_out);

                // Anchor actual time in/out — actual_time_out date = shift_end_date if crosses midnight
                $timeIn  = Carbon::parse($schedule->actual_time_in);
                $timeOut = Carbon::parse($schedule->actual_time_out);

                // If actual times are stored as plain H:i (no date), anchor them properly
                if ($timeOut->lte($timeIn)) {
                    $timeOut->addDay();
                }

                $workedHours = max(0, $timeIn->floatDiffInHours($timeOut));

                $totalHours += $workedHours;
                $totalDays++;

                // Late: employee arrived after shift start
                if ($timeIn->gt($shiftStart)) {
                    $totalLate += $shiftStart->diffInMinutes($timeIn);
                }

                // Undertime: employee left before shift end
                if ($timeOut->lt($shiftEnd)) {
                    $totalUndertime += $timeOut->diffInMinutes($shiftEnd);
                }

                // Holiday / rest day multiplier
                $multiplier = 1;
                if ($schedule->holiday_type === 'Regular') {
                    $multiplier = 2;
                } elseif ($schedule->holiday_type === 'Special') {
                    $multiplier = 1.3;
                }

                if ($schedule->is_rest_day) {
                    $multiplier *= 1.3;
                }

                $dayEarnings    = min($workedHours, 8) * $hourlyRate * $multiplier;
                $totalEarnings += $dayEarnings;
            }

            $overtimePay    = $overtimeHours * $overtimeRate;
            $totalEarnings += $overtimePay;

            $payrollData[] = [
                'id'             => $emp->id,
                'employee_code'  => $emp->employee_code,
                'first_name'     => $emp->first_name,
                'last_name'      => $emp->last_name,
                'total_days'     => $totalDays,
                'total_hours'    => round($totalHours, 2),
                'total_late'     => $totalLate,
                'total_undertime'=> $totalUndertime,
                'overtime_hours' => round($overtimeHours, 2),
                'total_earnings' => round($totalEarnings, 2),
            ];
        }

        return response()->json($payrollData);
    }

    public function printPayrollSummary(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $start = Carbon::parse($request->start_date)->startOfDay();
        $end = Carbon::parse($request->end_date)->endOfDay();

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

                if ($timeIn->gt($shiftStart)) {
                    $totalLate += $shiftStart->diffInMinutes($timeIn);
                }

                if ($timeOut->lt($shiftEnd)) {
                    $totalUndertime += $timeOut->diffInMinutes($shiftEnd);
                }

                $multiplier = 1;
                if ($schedule->holiday_type === 'Regular') {
                    $multiplier = 2;
                } elseif ($schedule->holiday_type === 'Special') {
                    $multiplier = 1.3;
                }

                if ($schedule->is_rest_day) {
                    $multiplier *= 1.3;
                }

                $dayEarnings = min($workedHours, 8) * $hourlyRate * $multiplier;

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
                'total_earnings' => round($totalEarnings, 2),
            ];
        }

        $branch = Branch::where('main', 1)->first();
        $generated_by = auth()->user()->name ?? 'System';

        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4-L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $html = view('payroll.summary', compact('payrollData', 'start', 'end', 'branch', 'generated_by'))->render();
        $mpdf->WriteHTML($html);

        return $mpdf->Output('Payroll-Summary.pdf', \Mpdf\Output\Destination::INLINE);
    }
}