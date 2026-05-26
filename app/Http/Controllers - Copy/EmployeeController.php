<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\DB;


class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with([
            'employmentDetails.documents', // ← correct relationship
            'schedules'
        ])->latest()->get();

        // Log::info('Fetched employees: ', $employees->toArray());
        return Inertia::render('Employees/Index', [
            'employees' => $employees
        ]);
    }

    public function getMyRecords()
    {
        $employee = auth()->user()
            ->employee()
            ->with([
                'overtimes',
                'employmentDetails.documents',
                'schedules' => function ($query) {
                    $query->orderBy('schedule_date', 'asc');
                },
                'user'
            ])
            ->first();

        Log::info('my records: ', ['employee' => $employee]);

        return Inertia::render('Employees/Biometrix', [
            'employee' => $employee
        ]);
    }

    public function store(Request $request)
    {
        Log::info('Storing employee with data: ', $request->all());

        $request->validate([
            'first_name' => 'required',
            'last_name'  => 'required',
        ]);

        DB::beginTransaction();

        try {

            // Prevent duplicate account
            if ($request->email && User::where('email', $request->email)->exists()) {
                DB::rollBack();
                Log::warning('Duplicate email prevented: ' . $request->email);
                return redirect()->back()->with('error', 'Account already exists.');
            }

            // Prefix
            $prefix = 'HSB';

            // Get current year
            $year = Carbon::now()->format('y');

            // Get last employee
            $lastEmployee = Employee::where('employee_code', 'like', "{$prefix}{$year}-%")
                ->orderBy('id', 'desc')
                ->first();

            if ($lastEmployee) {
                $lastNumber = (int) substr($lastEmployee->employee_code, -5);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            $formattedNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
            $emp_code = "{$prefix}{$year}-{$formattedNumber}";

            $employee = Employee::create([
                'employee_code'  => $emp_code,
                'first_name'     => $request->first_name,
                'last_name'      => $request->last_name,
                'middle_name'    => $request->middle_name,
                'email'          => $request->email,
                'contact_number' => $request->contact_number,
                'birth_date'     => $request->birth_date,
                'gender'         => $request->gender,
                'address'        => $request->address,
                'status'         => $request->status ?? 'active',
                'access'         => $request->access ?? '',
            ]);

            $name = trim($request->first_name . " " . $request->middle_name . " " . $request->last_name);

            $employee->user()->create([
                'name'     => $name,
                'email'    => $request->email,
                'password' => Hash::make('1'),
                'role'     => $request->access,
            ]);

            EmploymentDetail::create([
                'employee_id'     => $employee->id,
                'position'        => '',
                'department'      => '',
                'hire_date'       => date('Y-m-d'),
                'salary'          => null,
                'employment_type' => null,
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Employee created successfully.');

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Employee creation failed: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Employee creation failed.');
        }
    }

    // public function store(Request $request)
    // {
    //     Log::info('Storing employee with data: ', $request->all());
    //     $request->validate([
    //         'first_name' => 'required',
    //         'last_name' => 'required',
    //     ]);

    //     // Prefix
    //     $prefix = 'HSB';

    //     // Get current year (last 2 digits)
    //     $year = Carbon::now()->format('y'); // 26

    //     // Get last employee for this year
    //     $lastEmployee = Employee::where('employee_code', 'like', "{$prefix}{$year}-%")
    //         ->orderBy('id', 'desc')
    //         ->first();

    //     if ($lastEmployee) {
    //         // Extract last number
    //         $lastNumber = (int) substr($lastEmployee->employee_code, -5);
    //         $nextNumber = $lastNumber + 1;
    //     } else {
    //         $nextNumber = 1;
    //     }

    //     // Format to 5 digits
    //     $formattedNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

    //     // Final Employee Code
    //     $emp_code = "{$prefix}{$year}-{$formattedNumber}";

    //     $employee = Employee::create([
    //         'employee_code' => $emp_code,
    //         'first_name'    => $request->first_name,
    //         'last_name'     => $request->last_name,
    //         'middle_name'   => $request->middle_name,
    //         'email'         => $request->email,
    //         'contact_number'=> $request->contact_number,
    //         'birth_date'    => $request->birth_date,
    //         'gender'        => $request->gender,
    //         'address'       => $request->address,
    //         'status'        => $request->status ?? 'active',
    //         'access'        => $request->access ?? '',
    //     ]);

        
    //     $name = $request->first_name . " " . $request->middle_name . " " . $request->last_name;
    //     $employee->user()->create([
    //         'name'     => $name,
    //         'email'    => $request->email,
    //         'password' => Hash::make('1'),
    //         'role'     => $request->access,
    //     ]);

    //     EmploymentDetail::create([
    //         'employee_id' => $employee->id,
    //         'position' => '',
    //         'department' => '',
    //         'hire_date' => date('Y-m-d'),
    //         'salary' => NULL,
    //         'employment_type' => NULL,
    //     ]);
        

    //     // if ($request->schedules) {
    //     //     foreach ($request->schedules as $schedule) {
    //     //         EmployeeSchedule::create([
    //     //             'employee_id' => $employee->id,
    //     //             'day' => $schedule['day'],
    //     //             'time_in' => $schedule['time_in'],
    //     //             'time_out' => $schedule['time_out'],
    //     //         ]);
    //     //     }
    //     // }

    //     return redirect()->back()->with('success', 'Employee created successfully.');
    // }

    public function update(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name'  => 'required',
        ]);

        $employee = Employee::with('employmentDetails')->findOrFail($id);

        // 🔹 Update basic employee info
        $employee->update([
            'first_name'     => $request->first_name,
            'last_name'      => $request->last_name,
            'middle_name'    => $request->middle_name,
            'email'          => $request->email,
            'contact_number' => $request->contact_number,
            'birth_date'     => $request->birth_date,
            'gender'         => $request->gender,
            'address'        => $request->address,
            'status'         => $request->status,
        ]);

        return redirect()->back()->with('success', 'Employee updated successfully.');
    }

    public function newEmployment(Request $request, $id)
    {
        Log::info('Adding new employment for employee ID: ' . $id, $request->all());
        EmploymentDetail::create([
            'employee_id' => $id,
            'position' => $request->position,
            'department' => $request->department,
            'hire_date' => $request->hire_date,
            'salary' => $request->salary,
            'daily_rate' => $request->daily_rate,
            'employment_type' => 'Probationary',
        ]);

        return redirect()->back()->with('success', 'Employee created successfully.');
    }

    public function updateEmployment(Request $request, $employee, $employment)
    {
        Log::info('Updating employment', [
            'employee_id' => $employee,
            'employment_id' => $employment,
            'data' => $request->all()
        ]);

        // Validate
        $validated = $request->validate([
            'position' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'hire_date' => 'required|date',
            'salary' => 'required|numeric',
            'employment_type' => 'required|string|max:255',
        ]);

        // Find employment record
        $employmentRecord = EmploymentDetail::where('id', $employment)
            ->where('employee_id', $employee) // safety check
            ->firstOrFail();

        // Update record
        $employmentRecord->update($validated);

        return redirect()->back()->with('success', 'Employment updated successfully.');
    }

    public function uploadEmploymentDocument(Request $request, $employeeId, $employmentId)
    {
        Log::info('uploadEmploymentDocument');
        Log::info($request->all());

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'document_type' => 'required|string',
        ]);

        $file = $request->file('file');
        $documentType = $request->document_type;

        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('employment_documents/'.$employmentId, $filename, 'public');

        // Save to DB (use correct column name)
        $doc = EmployeeDocument::updateOrCreate(
            [
                'employment_detail_id' => $employmentId, // <- use the correct column
                'document_type' => $documentType
            ],
            [
                'file_path' => $path,
                'employee_id' => $employeeId,           // optional but good to store
            ]
        );

        return response()->json([
            'success' => true,
            'document' => $doc
        ]);
    }



    public function getSchedules()
    {
        $employees = Employee::with(['schedules'])
            ->latest()
            ->get();

        Log::info('Fetched schedules: ', $employees->toArray());
        return Inertia::render('Employees/Schedule', [
            'employees' => $employees
        ]);
    }

    public function storeSchedule(Request $request, Employee $employee)
    {
        // Log::info('posting sched');
        // Log::info($request->all());
        $validated = $request->validate([
            'schedule_date' => 'required|date',
            'shift'         => 'nullable|in:Morning,Night',
            'time_in'       => 'nullable|date_format:H:i',
            'time_out'      => 'nullable|date_format:H:i|after:time_in',
            'status'        => 'required|in:Scheduled,Absent,Leave,Day Off', // NEW
            'remarks'       => 'nullable|string|max:255',  
        ]);

        // If status is Absent, Leave, or Day Off → force time_in/out to null
        if (in_array($validated['status'], ['Absent', 'Leave', 'Day Off'])) {
            $validated['time_in'] = null;
            $validated['time_out'] = null;
            $validated['shift'] = null;
        } else {
            // If Scheduled, require time_in and time_out
            if (empty($validated['time_in']) || empty($validated['time_out'])) {
                return back()->withErrors([
                    'time_in' => 'Time In and Time Out are required for Scheduled status.'
                ]);
            }
        }

        // Prevent duplicate schedule for same date
        if ($employee->schedules()
            ->whereDate('schedule_date', $validated['schedule_date'])
            ->exists()) {
            return back()->withErrors([
                'schedule_date' => 'Schedule already exists for this date.'
            ]);
        }

        $schedule = $employee->schedules()->create($validated);
        
        return back()->with('success', 'Schedule added successfully');

    }



    public function updateSchedule(Request $request, EmployeeSchedule $schedule)
    {
        $request->validate([
            'schedule_date' => 'required|date',
            'shift'         => 'nullable|in:Morning,Night',
            'time_in'       => 'nullable|date_format:H:i',
            'time_out'      => 'nullable|date_format:H:i|after:time_in',
            'status'        => 'required|in:Scheduled,Absent,Leave,Day Off',
            'remarks'       => 'nullable|string|max:255',
        ]);

        // If Absent / Leave / Day Off → force time_in/out to null
        if (in_array($validated['status'], ['Absent', 'Leave', 'Day Off'])) {
            $validated['time_in'] = null;
            $validated['time_out'] = null;
            $validated['shift'] = null;
        } else {
            // If Scheduled → require time_in and time_out
            if (empty($validated['time_in']) || empty($validated['time_out'])) {
                return back()->withErrors([
                    'time_in' => 'Time In and Time Out are required for Scheduled status.'
                ]);
            }
        }


        $schedule->update([
            'daschedule_datey' => $request->schedule_date,
            'shift' => $request->shift,
            'time_in' => $request->time_in,
            'time_out' => $request->time_out,
            'status' => $request->status,
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', 'Schedule updated successfully.');
    }

    public function getEmployeeSchedules(Employee $employee)
    {
        $start = $request->start_date ?? now()->startOfMonth()->toDateString();
        $end   = $request->end_date ?? now()->endOfMonth()->toDateString();

        // $employee->load(['schedules' => function ($query) use ($start, $end) {
        //     $query->whereBetween('schedule_date', [$start, $end])
        //         ->orderBy('schedule_date');
        // }]);
        $employee->load('schedules');

        return Inertia::render('Employees/EmployeeSchedule', [
            'employee' => $employee,
            'filters' => [
                'start_date' => $start,
                'end_date' => $end,
            ]
        ]);
    }

    

    public function getAllEmployeeForManager()
    {
        $employees = Employee::with(['employmentDetails', 'schedules'])
            ->latest()
            ->get();

        // Log::info('Fetched employees: ', $employees->toArray());
        return Inertia::render('Employees/Schedule', [
            'employees' => $employees
        ]);
    }

    
    public function printSchedule(Employee $employee, Request $request)
    {
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        $schedules = $employee->schedules()
            ->whereBetween('schedule_date', [$start, $end])
            ->orderBy('schedule_date')
            ->get();
        
        $printed_by = auth()->user();

        $mpdf = new \Mpdf\Mpdf();
        $html = view('employees.pdf_schedule', compact('employee', 'schedules', 'start', 'end', 'printed_by'))->render();
        $mpdf->WriteHTML($html);

        return $mpdf->Output('Schedule_'.$employee->employee_code.'.pdf', 'I'); // Opens in new tab
    }

    public function printAllSchedules(Request $request)
    {
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        // Get all employees with schedules in the range
        $employees = Employee::with(['schedules' => function($q) use ($start, $end) {
            $q->whereBetween('schedule_date', [$start, $end])
            ->orderBy('schedule_date');
        }])->get();

        // Log::info('employees employees employees');
        // Log::info($employees);

        // Generate array of dates for columns
        $dates = [];
        $current = \Carbon\Carbon::parse($start);
        $endDate = \Carbon\Carbon::parse($end);
        while ($current <= $endDate) {
            $dates[] = $current->copy();
            $current->addDay();
        }

        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4-L', // Landscape
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $html = view('employees.pdf_all_schedules', compact('employees', 'dates'))->render();
        $mpdf->WriteHTML($html);

        return $mpdf->Output('All_Employees_Schedules.pdf', 'I');
    }

    // public function showDTR(Employee $employee)
    // {
    //     \Log::info('Fetching DTR for employee ID: ' . $employee->id);
    //     $startOfMonth = Carbon::now()->startOfMonth();
    //     $endOfMonth   = Carbon::now()->endOfMonth();

    //     // Load schedules and overtimes for current month only
    //     $employee->load([
    //         'schedules' => function ($query) use ($startOfMonth, $endOfMonth) {
    //             $query->whereBetween('schedule_date', [$startOfMonth, $endOfMonth])
    //                 ->orderBy('schedule_date');
    //         },
    //         'overtimes' => function ($query) use ($startOfMonth, $endOfMonth) {
    //             $query->whereBetween('ot_date', [$startOfMonth, $endOfMonth])
    //                 ->orderBy('ot_date');
    //         }
    //     ]);

    //     return response()->json([
    //         'employee'  => $employee,
    //         'schedules' => $employee->schedules,
    //         'overtimes' => $employee->overtimes,
    //         'period'    => [
    //             'start' => $startOfMonth->toDateString(),
    //             'end'   => $endOfMonth->toDateString(),
    //         ]
    //     ]);
    // }

    public function showDTR(Employee $employee)
    {
        \Log::info('Fetching DTR for employee ID: ' . $employee->id);

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth   = Carbon::now()->endOfMonth();

        $employee->load([
            'schedules' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('schedule_date', [$startOfMonth, $endOfMonth])
                    ->with('overtime') // 🔥 load overtime inside schedule
                    ->orderBy('schedule_date');
            }
        ]);

        return response()->json([
            'employee'  => $employee,
            'schedules' => $employee->schedules,
            'period'    => [
                'start' => $startOfMonth->toDateString(),
                'end'   => $endOfMonth->toDateString(),
            ]
        ]);
    }

    public function showDTR_orig(Employee $employee)
    {
        Log::info('employee');
        Log::info($employee);
        $schedules = $employee->schedules()->orderBy('schedule_date')->get();

        Log::info($schedules);

        return response()->json([
            'employee' => $employee,
            'schedules' => $schedules,
        ]);
    }

    public function updateDTR(Request $request, Employee $employee)
    {
        $request->validate([
            'schedules.*.id' => 'required|exists:employee_schedules,id',
            'schedules.*.actual_time_in' => 'nullable|date_format:H:i',
            'schedules.*.actual_time_out' => 'nullable|date_format:H:i',
        ]);

        foreach ($request->schedules as $sch) {
            $schedule = EmployeeSchedule::find($sch['id']);
            $schedule->update([
                'actual_time_in' => $sch['actual_time_in'],
                'actual_time_out' => $sch['actual_time_out'],
            ]);
        }

        return response()->json(['message' => 'DTR updated successfully']);
    }

    public function generateEmployeePayrollPdf(Request $request, Employee $employee)
    {
        // Optional payroll period filter
        $startDate = $request->start_date;
        $endDate   = $request->end_date;

        $query = $employee->schedules()->orderBy('schedule_date');

        if ($startDate && $endDate) {
            $query->whereBetween('schedule_date', [$startDate, $endDate]);
        }

        $schedules = $query->get();

        // Compute totals
        $totalLateMinutes = 0;
        $totalUndertimeMinutes = 0;

        foreach ($schedules as $sch) {

            if ($sch->status !== 'Scheduled') {
                continue;
            }

            // Late
            if ($sch->actual_time_in && $sch->time_in) {
                $late = Carbon::parse($sch->actual_time_in)
                    ->diffInMinutes(Carbon::parse($sch->time_in), false);

                if ($late > 0) {
                    $totalLateMinutes += $late;
                }
            }

            // Undertime
            if ($sch->actual_time_out && $sch->time_out) {
                $undertime = Carbon::parse($sch->time_out)
                    ->diffInMinutes(Carbon::parse($sch->actual_time_out), false);

                if ($undertime > 0) {
                    $totalUndertimeMinutes += $undertime;
                }
            }
        }

        $totalLate = floor($totalLateMinutes / 60) . ':' . str_pad($totalLateMinutes % 60, 2, '0', STR_PAD_LEFT);
        $totalUndertime = floor($totalUndertimeMinutes / 60) . ':' . str_pad($totalUndertimeMinutes % 60, 2, '0', STR_PAD_LEFT);

        $html = view('payroll.employee-payroll-attendance', [
            'employee'        => $employee,
            'schedules'       => $schedules,
            'totalLate'       => $totalLate,
            'totalUndertime'  => $totalUndertime,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
        ])->render();

        $mpdf = new Mpdf([
            'format' => 'A4',
            'margin_top' => 20,
            'margin_bottom' => 20,
        ]);

        $mpdf->SetTitle('Payroll Attendance - ' . $employee->employee_code);
        $mpdf->WriteHTML($html);

        return $mpdf->Output(
            'Payroll_Attendance_' . $employee->employee_code . '.pdf',
            'I' // I = open in browser
        );
    }
}

