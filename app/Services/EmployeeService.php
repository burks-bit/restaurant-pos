<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeDocument;
use App\Models\Configuration;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    public function index()
    {
        $roles = User::roles();
        $branches = Branch::all();

        $employees = Employee::with([
            'employmentDetails.documents',
            'schedules',
            'user'
        ])->latest()->get();

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'roles' => $roles,
            'branches' => $branches,
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

        return Inertia::render('Employees/Biometrix', [
            'employee' => $employee
        ]);
    }

    public function batchStore(Request $request, Employee $employee)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'time_in'    => 'nullable|date_format:H:i',
            'time_out'   => 'nullable|date_format:H:i|after:time_in',
            'status'     => 'required|string',
            'remarks'    => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $employee = Employee::findOrFail($employee->id);

            $statusWithoutTime = ['Day Off', 'Leave', 'Absent'];

            $timeIn = $request->time_in;
            $timeOut = $request->time_out;

            if (in_array($request->status, $statusWithoutTime)) {
                $timeIn = null;
                $timeOut = null;
            }

            $dates = CarbonPeriod::create($request->start_date, $request->end_date);

            foreach ($dates as $date) {
                EmployeeSchedule::updateOrCreate(
                    [
                        'employee_id'   => $employee->id,
                        'schedule_date' => $date->format('Y-m-d'),
                    ],
                    [
                        'time_in' => $timeIn,
                        'time_out' => $timeOut,
                        'status' => $request->status,
                        'remarks' => $request->remarks,
                    ]
                );
            }

            DB::commit();

            return redirect()->back()->with('success', 'Batch schedule posted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Employee batchStore failed: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Failed to post batch schedule.');
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name'  => 'required',
        ]);

        DB::beginTransaction();

        try {
            if ($request->email && User::where('email', $request->email)->exists()) {
                DB::rollBack();
                Log::warning('Duplicate email prevented: ' . $request->email);
                return redirect()->back()->with('error', 'Account already exists.');
            }

            $employeeCodePrefix = Configuration::where('name', 'EmployeeCode')->pluck('value')->first();
            $year = Carbon::now()->format('y');

            $lastEmployee = Employee::where('employee_code', 'like', "{$employeeCodePrefix}{$year}-%")
                ->orderBy('id', 'desc')
                ->first();

            if ($lastEmployee) {
                $lastNumber = (int) substr($lastEmployee->employee_code, -5);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            $formattedNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
            $emp_code = "{$employeeCodePrefix}{$year}-{$formattedNumber}";

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
                'branch_id'      => 1,
            ]);

            $name = trim($request->first_name . " " . $request->middle_name . " " . $request->last_name);

            $usernamePrefix = Configuration::where('name', 'UsernameEmailPrefix')->pluck('value')->first();

            $middleInitial = !empty($request->middle_name) ? strtoupper(substr($request->middle_name, 0, 1)) : '';
            $lastInitial = !empty($request->last_name) ? strtoupper(substr($request->last_name, 0, 1)) : '';
            $initials = $middleInitial . $lastInitial;
            $username = strtolower(str_replace(' ', '', $request->first_name) . $initials) . $usernamePrefix;
            
            $roles = User::roles();
            // convert string "5" → int 5
            $accessValue = (int) $request->access;

            // validate if exists in constants
            if (!array_key_exists($accessValue, $roles)) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Invalid access role.');
            }

            $roleConstant = $accessValue;
            $positionName = $roles[$accessValue];

            $employee->user()->create([
                'name'     => $name,
                'email'    => $username,
                'password' => Hash::make('1'),
                'role'     => $roleConstant,
                'branch_id' => 1,
            ]);

            EmploymentDetail::create([
                'employee_id'     => $employee->id,
                'position'        => $positionName,
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
    
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'middle_name'    => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'contact_number' => 'nullable|string|max:50',
            'birth_date'     => 'nullable|date',
            'gender'         => 'nullable|string',
            'address'        => 'nullable|string',
            'status'         => 'nullable|string',
            'access'         => 'required|integer',
        ]);

        $roles = User::roles();
        $roleConstant = (int) $validated['access'];

        if (!array_key_exists($roleConstant, $roles)) {
            return redirect()->back()->with('error', 'Invalid access role.');
        }

        $employee = Employee::with('employmentDetails')->findOrFail($id);

        DB::transaction(function () use ($employee, $validated, $roleConstant) {
            $employee->update([
                // ...Arr::except($validated, 'access'),
                ...$validated,
                'branch_id' => 1,
            ]);

            $employee->user()->update([
                'name'      => $this->buildFullName($validated),
                'email'     => $this->buildUsername($validated),
                'role'      => $roleConstant,
                'branch_id' => 1,
            ]);
        });

        return redirect()->back()->with('success', 'Employee updated successfully.');
    }

    private function buildFullName(array $data): string
    {
        return trim("{$data['first_name']} {$data['middle_name']} {$data['last_name']}");
    }

    private function buildUsername(array $data): string
    {
        $prefix = Configuration::where('name', 'UsernameEmailPrefix')->value('value');

        $middleInitial = !empty($data['middle_name']) ? strtoupper($data['middle_name'][0]) : '';
        $lastInitial   = !empty($data['last_name']) ? strtoupper($data['last_name'][0]) : '';

        $base = strtolower(str_replace(' ', '', $data['first_name']) . $middleInitial . $lastInitial);

        return $base . $prefix;
    }

    public function newEmployment(Request $request, $id)
    {
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
        $validated = $request->validate([
            'position' => 'required|integer|max:255',
            'department' => 'required|string|max:255',
            'hire_date' => 'required|date',
            'salary' => 'nullable|numeric',
            'daily_rate' => 'required|numeric',
        ]);

        $employmentRecord = EmploymentDetail::where('id', $employment)
            ->where('employee_id', $employee)
            ->firstOrFail();

        $employmentRecord->update($validated);

        $employees = Employee::with([
            'employmentDetails.documents',
            'schedules',
            'user'
        ])->latest()->get();

        return redirect()->back()->with('success', 'Employment updated successfully.');
    }

    public function uploadEmploymentDocument(Request $request, $employeeId, $employmentId)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'document_type' => 'required|string',
        ]);

        $file = $request->file('file');
        $documentType = $request->document_type;

        // Get employee
        $employee = Employee::findOrFail($employeeId);
        $fullname = $employee->last_name . '' . $employee->first_name . '' . $employee->middle_name;

        // Clean employee name
        $employeeName = strtolower(
            str_replace(' ', '_', $fullname)
        );

        // Extension
        $extension = $file->getClientOriginalExtension();

        // Generate filename
        $filename = $employeeName . '_' . now()->format('Ymd_His') . '.' . $extension;

        // Save directly to employment_documents
        $path = $file->storeAs(
            'employment_documents',
            $filename,
            'public'
        );

        $doc = EmployeeDocument::updateOrCreate(
            [
                'employment_detail_id' => $employmentId,
                'document_type' => $documentType
            ],
            [
                'file_path' => $path,
                'employee_id' => $employeeId,
            ]
        );

        return response()->json([
            'success' => true,
            'document' => $doc
        ]);
    }

    public function deleteEmploymentDocument($docId)
    {
        $document = EmployeeDocument::findOrFail($docId);

        // Delete file from storage
        if ($document->file_path && \Storage::disk('public')->exists($document->file_path)) {
            \Storage::disk('public')->delete($document->file_path);
        }

        // Delete database record
        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.'
        ]);
    }

    public function getSchedules()
    {
        $employees = Employee::with(['schedules'])
            ->latest()
            ->get();

        return Inertia::render('Employees/Schedule', [
            'employees' => $employees
        ]);
    }

    public function storeBatchSchedule(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'time_in'    => 'nullable|date_format:H:i',
            'time_out'   => 'nullable|date_format:H:i',   // removed |after:time_in
            'status'     => 'required|in:Scheduled,Absent,Leave,Day Off',
            'remarks'    => 'nullable|string|max:255',
        ]);

        if (in_array($validated['status'], ['Absent', 'Leave', 'Day Off'])) {
            $validated['time_in']  = null;
            $validated['time_out'] = null;
        } else {
            if (empty($validated['time_in']) || empty($validated['time_out'])) {
                return back()->withErrors([
                    'time_in' => 'Time In and Time Out are required for Scheduled status.',
                ]);
            }

            // Detect midnight-crossing shifts
            $timeIn  = Carbon::createFromFormat('H:i', $validated['time_in']);
            $timeOut = Carbon::createFromFormat('H:i', $validated['time_out']);

            if ($timeOut->lte($timeIn)) {
                // e.g. 15:00 → 00:00, shift ends next day
                $crossesMidnight = true;
            } else {
                $crossesMidnight = false;
            }
        }

        try {
            $period = CarbonPeriod::create($validated['start_date'], $validated['end_date']);

            foreach ($period as $date) {
                $exists = $employee->schedules()
                    ->whereDate('schedule_date', $date->format('Y-m-d'))
                    ->exists();

                if ($exists) {
                    continue;
                }

                // Compute shift_end_date per iteration date
                if (!in_array($validated['status'], ['Absent', 'Leave', 'Day Off'])) {
                    $shiftEndDate = $crossesMidnight
                        ? $date->copy()->addDay()->toDateString()
                        : $date->copy()->toDateString();
                } else {
                    $shiftEndDate = null;
                }

                $employee->schedules()->create([
                    'schedule_date'  => $date->format('Y-m-d'),
                    'time_in'        => $validated['time_in'],
                    'time_out'       => $validated['time_out'],
                    'shift_end_date' => $shiftEndDate,
                    'status'         => $validated['status'],
                    'remarks'        => $validated['remarks'] ?? null,
                ]);
            }

            return back()->with('success', 'Batch schedule added successfully.');
        } catch (\Throwable $e) {
            Log::error('storeBatchSchedule failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to save batch schedule.');
        }
    }

    public function storeSchedule(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'schedule_date'  => 'required|date',
            'shift'          => 'nullable|in:Morning,Night',
            'time_in'        => 'nullable|date_format:H:i',
            'time_out'       => 'nullable|date_format:H:i',
            'status'         => 'required|in:Scheduled,Absent,Leave,Day Off',
            'remarks'        => 'nullable|string|max:255',
        ]);

        if (in_array($validated['status'], ['Absent', 'Leave', 'Day Off'])) {
            $validated['time_in']  = null;
            $validated['time_out'] = null;
            $validated['shift']    = null;
            $validated['shift_end_date'] = null;
        } else {
            if (empty($validated['time_in']) || empty($validated['time_out'])) {
                return back()->withErrors([
                    'time_in' => 'Time In and Time Out are required for Scheduled status.'
                ]);
            }

            $timeIn  = Carbon::createFromFormat('H:i', $validated['time_in']);
            $timeOut = Carbon::createFromFormat('H:i', $validated['time_out']);

            // If time_out <= time_in, the shift crosses midnight — end date is next day
            $scheduleDate = Carbon::parse($validated['schedule_date']);
            $shiftEndDate = $timeOut->lte($timeIn)
                ? $scheduleDate->copy()->addDay()
                : $scheduleDate->copy();

            $validated['shift_end_date'] = $shiftEndDate->toDateString();

            // Validate: shift must not exceed 24 hours
            $start = $scheduleDate->copy()->setTimeFromTimeString($validated['time_in']);
            $end   = $shiftEndDate->copy()->setTimeFromTimeString($validated['time_out']);

            if ($end->diffInHours($start) > 24) {
                return back()->withErrors([
                    'time_out' => 'Shift duration cannot exceed 24 hours.'
                ]);
            }
        }

        if ($employee->schedules()->whereDate('schedule_date', $validated['schedule_date'])->exists()) {
            return back()->withErrors([
                'schedule_date' => 'Schedule already exists for this date.'
            ]);
        }

        $employee->schedules()->create($validated);

        return back()->with('success', 'Schedule added successfully.');
    }

    public function updateSchedule(Request $request, EmployeeSchedule $schedule)
    {
        $validated = $request->validate([
            'schedule_date' => 'required|date',
            // 'shift'         => 'nullable|in:Morning,Night',
            'time_in'       => 'nullable|date_format:H:i,H:i:s',
            'time_out'      => 'nullable|date_format:H:i,H:i:s|after:time_in',
            'status'        => 'required|in:Scheduled,Absent,Leave,Day Off',
            'remarks'       => 'nullable|string|max:255',
        ]);

        if (in_array($validated['status'], ['Absent', 'Leave', 'Day Off'])) {
            $validated['time_in'] = null;
            $validated['time_out'] = null;
            // $validated['shift'] = null;
        } else {
            if (empty($validated['time_in']) || empty($validated['time_out'])) {
                return back()->withErrors([
                    'time_in' => 'Time In and Time Out are required for Scheduled status.'
                ]);
            }
        }

        $schedule->update([
            'schedule_date' => $validated['schedule_date'],
            // 'shift' => $validated['shift'],
            'time_in' => $validated['time_in'],
            'time_out' => $validated['time_out'],
            'status' => $validated['status'],
            'remarks' => $validated['remarks'],
        ]);

        return back()->with('success', 'Schedule updated successfully.');
    }

    public function getEmployeeSchedules(Request $request, Employee $employee)
    {
        $start = $request->start_date ?? now()->startOfMonth()->toDateString();
        $end   = $request->end_date ?? now()->endOfMonth()->toDateString();

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

        return $mpdf->Output('Schedule_' . $employee->employee_code . '.pdf', 'I');
    }

    public function printAllSchedules(Request $request)
    {
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        $employees = Employee::with(['schedules' => function ($q) use ($start, $end) {
            $q->whereBetween('schedule_date', [$start, $end])
                ->orderBy('schedule_date');
        }])->get();

        $dates = [];
        $current = \Carbon\Carbon::parse($start);
        $endDate = \Carbon\Carbon::parse($end);

        while ($current <= $endDate) {
            $dates[] = $current->copy();
            $current->addDay();
        }

        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4-L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $html = view('employees.pdf_all_schedules', compact('employees', 'dates'))->render();
        $mpdf->WriteHTML($html);

        return $mpdf->Output('All_Employees_Schedules.pdf', 'I');
    }

    public function printPosAccounts(){

        $userAccounts = User::orderBy('name')
            ->where('role', '!=', User::ROLE_ADMIN)
            ->get();

        $type = 1; // pos accounts

        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $html = view('employees.pdf_pos_accounts', compact('userAccounts', 'type'))->render();
        $mpdf->WriteHTML($html);

        return $mpdf->Output('POS_Accounts.pdf', 'I');
    }

    public function printEmployeePersonalDetails(){

        $employeeDetails = Employee::orderBy('first_name')
            ->where('access', '!=', User::ROLE_ADMIN)
            ->get();

        $type = 2; // employee personal details

        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4-L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $html = view('employees.pdf_pos_accounts', compact('employeeDetails', 'type'))->render();
        $mpdf->WriteHTML($html);

        return $mpdf->Output('Employee_Personal_Details.pdf', 'I');
    }

    public function showDTR(Employee $employee)
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth   = Carbon::now()->endOfMonth();

        $employee->load([
            'schedules' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('schedule_date', [$startOfMonth, $endOfMonth])
                    ->with('overtime')
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
        $schedules = $employee->schedules()->orderBy('schedule_date')->get();

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
        $startDate = $request->start_date;
        $endDate   = $request->end_date;

        $query = $employee->schedules()->orderBy('schedule_date');

        if ($startDate && $endDate) {
            $query->whereBetween('schedule_date', [$startDate, $endDate]);
        }

        $schedules = $query->get();

        $totalLateMinutes = 0;
        $totalUndertimeMinutes = 0;

        foreach ($schedules as $sch) {
            if ($sch->status !== 'Scheduled') {
                continue;
            }

            if ($sch->actual_time_in && $sch->time_in) {
                $late = Carbon::parse($sch->actual_time_in)
                    ->diffInMinutes(Carbon::parse($sch->time_in), false);

                if ($late > 0) {
                    $totalLateMinutes += $late;
                }
            }

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
            'I'
        );
    }
}