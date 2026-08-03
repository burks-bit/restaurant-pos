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
use Illuminate\Support\Facades\DB;

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

        $employee = auth()->user()->employee;
        $today = Carbon::now()->toDateString();

        $schedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->whereDate('schedule_date', $today)
            ->first();

        // ❌ NO SCHEDULE
        if (!$schedule) {
            return response()->json([
                'message' => "No schedule found for today ({$today}). Please contact your supervisor or HR to confirm your shift.",
            ], 404);
        }

        // ❌ ALREADY CLOCKED IN
        if ($schedule->actual_time_in) {
            return response()->json([
                'message' => "You already clocked in today at " .
                    Carbon::parse($schedule->actual_time_in)->format('h:i A') . ".",
            ], 422);
        }

        // ❌ NO PHOTO RECEIVED — fail clearly instead of silently proceeding
        if (!$request->hasFile('photo')) {
            Log::warning('No photo received for clock-in', ['employee_id' => $employee->id]);

            return response()->json([
                'message' => 'No photo was captured. Please allow camera access in your browser and try again.',
            ], 422);
        }

        // ✅ SAVE PHOTO
        try {
            $photoPath = $request->file('photo')->store('attendance_photo_images', 'public');
            Log::info('Photo saved at: ' . $photoPath);
        } catch (\Exception $e) {
            Log::error('Photo save failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to save your photo. Please check your connection and try again.',
            ], 500);
        }

        // ✅ NORMAL CLOCK IN
        $schedule->update([
            'actual_time_in' => now()->format('H:i:s'),
            'time_in_photo' => $photoPath,
        ]);

        $shiftStart = $schedule->time_in
            ? Carbon::parse($schedule->time_in)->format('h:i A')
            : null;

        $isLate = $shiftStart && now()->format('H:i:s') > $schedule->time_in;

        return response()->json([
            'message' => $isLate
                ? "Clocked in at " . now()->format('h:i A') . ". You were scheduled for {$shiftStart} — this clock-in is late."
                : "Clocked in successfully at " . now()->format('h:i A') . ".",
            'is_late' => $isLate,
            'schedule' => $schedule,
            'photo_path' => $photoPath,
        ]);
    }

    public function clockOut(Request $request)
    {
        Log::info('clockout');
        Log::info($request->all());

        $employee = auth()->user()->employee;
        $today = Carbon::now()->toDateString();

        // ✅ Find the employee's open shift — could be today's schedule,
        // or an overnight shift that started yesterday and ends today.
        $schedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->whereNotNull('actual_time_in')
            ->whereNull('actual_time_out')
            ->where(function ($query) use ($today) {
                $query->whereDate('schedule_date', $today)
                    ->orWhereDate('shift_end_date', $today);
            })
            ->orderByDesc('schedule_date')
            ->first();

        // ❌ NO OPEN SHIFT FOUND
        if (!$schedule) {
            // Distinguish "no schedule at all today" vs "not clocked in yet"
            $todaySchedule = EmployeeSchedule::where('employee_id', $employee->id)
                ->whereDate('schedule_date', $today)
                ->first();

            if (!$todaySchedule) {
                return response()->json([
                    'message' => "No schedule found for today ({$today}). Please contact your supervisor or HR.",
                ], 404);
            }

            if (!$todaySchedule->actual_time_in) {
                return response()->json([
                    'message' => 'You must clock in first before you can clock out.',
                ], 422);
            }

            if ($todaySchedule->actual_time_out) {
                return response()->json([
                    'message' => "You already clocked out today at " .
                        Carbon::parse($todaySchedule->actual_time_out)->format('h:i A') . ".",
                ], 422);
            }
        }

        // ❌ ALREADY CLOCKED OUT (defensive, shouldn't hit due to whereNull above)
        if ($schedule->actual_time_out) {
            return response()->json([
                'message' => "You already clocked out today at " .
                    Carbon::parse($schedule->actual_time_out)->format('h:i A') . ".",
            ], 422);
        }

        // ❌ NO PHOTO RECEIVED
        if (!$request->hasFile('photo')) {
            Log::warning('No photo received for clock-out', ['employee_id' => $employee->id]);

            return response()->json([
                'message' => 'No photo was captured. Please allow camera access in your browser and try again.',
            ], 422);
        }

        // ✅ SAVE PHOTO
        try {
            $photoPath = $request->file('photo')->store('attendance_photo_images', 'public');
            Log::info('Clockout photo saved at: ' . $photoPath);
        } catch (\Exception $e) {
            Log::error('Clockout photo save failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to save your photo. Please check your connection and try again.',
            ], 500);
        }

        // ✅ SUCCESS
        $schedule->update([
            'actual_time_out' => now()->format('H:i:s'),
            'time_out_photo' => $photoPath,
        ]);

        return response()->json([
            'message' => "Clocked out successfully at " . now()->format('h:i A') . ".",
            'schedule' => $schedule,
            'photo_path' => $photoPath,
        ]);
    }

    public function getAllEmployeeOvertime()
    {
        $employees = Employee::with(['overtimes'])->get();

        return Inertia::render('Employees/EmployeesOvertime', [
            'employees' => $employees
        ]);
    }

    // public function getAllEmployeeDailyLogs()
    // {
    //     $today = Carbon::now()->toDateString();

    //     $logs = DB::table('employees as emp')
    //         ->leftJoin('employee_schedules as emps', 'emps.employee_id', '=', 'emp.id')
    //         ->whereDate('emps.schedule_date', $today)
    //         ->where(function ($query) {
    //             $query->where('emps.status', '!=', 'Day Off')
    //                 ->where('emps.status', '!=', 'Absent')
    //                 ->where('emps.status', '!=', 'Leave');
    //         })
    //         ->get();

    //     return Inertia::render('Employees/Logs', [
    //         'logs' => $logs
    //     ]);
    // }
    
    public function getAllEmployeeDailyLogs(?string $date = null)
    {
        $date = $date ?: Carbon::now()->toDateString();
        Log::info('Fetching employee daily logs for date: ' . $date);

        $logs = DB::table('employee_schedules as es')
            ->join('employees as e', 'e.id', '=', 'es.employee_id')
            ->select([
                'es.id',
                'es.employee_id',
                'e.employee_code',
                'e.first_name',
                'e.last_name',
                'es.time_in',
                'es.time_out',
                'es.actual_time_in',
                'es.actual_time_out',
                'es.time_in_photo',
                'es.time_out_photo',
                'es.schedule_date',
                'es.shift_end_date',
            ])
            ->when($date, function ($query) use ($date) {
                // Overnight shifts can end the next calendar day, so a log
                // "belongs" to $date if either schedule_date or shift_end_date matches.
                $query->where(function ($q) use ($date) {
                    $q->whereDate('es.schedule_date', $date)
                      ->orWhereDate('es.shift_end_date', $date);
                });
            })
            ->where(function ($query) {
                $query->where('es.status', '!=', 'Day Off')
                    ->where('es.status', '!=', 'Absent')
                    ->where('es.status', '!=', 'Leave');
            })
            ->orderByDesc('es.actual_time_in')
            ->get();

        return Inertia::render('Employees/Logs', [
            'logs' => $logs,
            'filters' => [
                'date' => $date,
            ],
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
        ->where('status', 'active') // Only include active employees
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

                //$scheduleDate = Carbon::parse($schedule->schedule_date);

                //// Use shift_end_date if stored, otherwise fall back to same day
                //$shiftEndDate = $schedule->shift_end_date
                //    ? Carbon::parse($schedule->shift_end_date)
                //    : $scheduleDate->copy();

                //// Anchor shift start/end to their correct calendar dates
                //$shiftStart = $scheduleDate->copy()->setTimeFromTimeString($schedule->time_in);
                //$shiftEnd   = $shiftEndDate->copy()->setTimeFromTimeString($schedule->time_out);

                //// Anchor actual time in/out — actual_time_out date = shift_end_date if crosses midnight
                //// $timeIn  = Carbon::parse($schedule->actual_time_in);
                //// $timeOut = Carbon::parse($schedule->actual_time_out);

                //// Anchor actual_time_in to the schedule date (not today's date)
                //$timeIn = $scheduleDate->copy()->setTimeFromTimeString(
                //    Carbon::parse($schedule->actual_time_in)->format('H:i:s')
                //);

                //// Anchor actual_time_out to shift_end_date (handles overnight shifts)
                //$timeOut = $shiftEndDate->copy()->setTimeFromTimeString(
                //    Carbon::parse($schedule->actual_time_out)->format('H:i:s')
                //);

                //// Safety net: if time_out still ends up before/equal time_in, it crossed midnight unexpectedly
                //if ($timeOut->lte($timeIn)) {
                //    $timeOut->addDay();
                //}

                //$workedHours = max(0, $timeIn->floatDiffInHours($timeOut));

                //$totalHours += $workedHours;
                //$totalDays++;

                //// Late: employee arrived after shift start
                //if ($timeIn->gt($shiftStart)) {
                //    $totalLate += $shiftStart->diffInMinutes($timeIn);
                //}

                //// Undertime: employee left before shift end
                //if ($timeOut->lt($shiftEnd)) {
                //    $totalUndertime += $timeOut->diffInMinutes($shiftEnd);
                //}

                $scheduleDate = Carbon::parse($schedule->schedule_date);

                $shiftEndDate = $schedule->shift_end_date
                    ? Carbon::parse($schedule->shift_end_date)
                    : $scheduleDate->copy();

                // Only build shift anchors if the scheduled times exist
                $shiftStart = $schedule->time_in
                    ? $scheduleDate->copy()->setTimeFromTimeString($schedule->time_in)
                    : null;

                $shiftEnd = $schedule->time_out
                    ? $shiftEndDate->copy()->setTimeFromTimeString($schedule->time_out)
                    : null;

                // Actual times (already guaranteed non-null by the earlier continue)
                $timeIn = $scheduleDate->copy()->setTimeFromTimeString(
                    Carbon::parse($schedule->actual_time_in)->format('H:i:s')
                );

                $timeOut = $shiftEndDate->copy()->setTimeFromTimeString(
                    Carbon::parse($schedule->actual_time_out)->format('H:i:s')
                );

                if ($timeOut->lte($timeIn)) {
                    $timeOut->addDay();
                }

                $workedHours = max(0, $timeIn->floatDiffInHours($timeOut));

                $totalHours += $workedHours;
                $totalDays++;

                // Late: only if we know the scheduled start
                if ($shiftStart && $timeIn->gt($shiftStart)) {
                    $totalLate += $shiftStart->diffInMinutes($timeIn);
                }

                // Undertime: only if we know the scheduled end
                if ($shiftEnd && $timeOut->lt($shiftEnd)) {
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