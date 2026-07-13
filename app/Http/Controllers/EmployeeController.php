<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    public function index()
    {
        try {
            return $this->employeeService->index();
        } catch (\Throwable $e) {
            Log::error('EmployeeController@index failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load employees.');
        }
    }

    public function getMyRecords()
    {
        try {
            return $this->employeeService->getMyRecords();
        } catch (\Throwable $e) {
            Log::error('EmployeeController@getMyRecords failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load employee records.');
        }
    }

    public function store(Request $request)
    {
        try {
            return $this->employeeService->store($request);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@store failed: ' . $e->getMessage());
            return back()->with('error', 'Employee creation failed.');
        }
    }
    
    public function storeBatchSchedule(Request $request, Employee $employee)
    {
        try {
            return $this->employeeService->storeBatchSchedule($request, $employee);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@batchStore failed: ' . $e->getMessage());
            return back()->with('error', 'Employee batchStore failed.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            return $this->employeeService->update($request, $id);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@update failed: ' . $e->getMessage());
            return back()->with('error', 'Employee update failed.');
        }
    }

    public function newEmployment(Request $request, $id)
    {
        try {
            return $this->employeeService->newEmployment($request, $id);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@newEmployment failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to add employment.');
        }
    }

    public function updateEmployment(Request $request, $employee, $employment)
    {
        try {
            return $this->employeeService->updateEmployment($request, $employee, $employment);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@updateEmployment failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to update employment.');
        }
    }

    public function uploadEmploymentDocument(Request $request, $employeeId, $employmentId)
    {
        try {
            return $this->employeeService->uploadEmploymentDocument($request, $employeeId, $employmentId);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@uploadEmploymentDocument failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload employment document.'
            ], 500);
        }
    }

    public function deleteEmploymentDocument($docId)
    {
        try {
            return $this->employeeService->deleteEmploymentDocument($docId);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@deleteEmploymentDocument failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete employment document.'
            ], 500);
        }
    }

    public function getSchedules()
    {
        try {
            return $this->employeeService->getSchedules();
        } catch (\Throwable $e) {
            Log::error('EmployeeController@getSchedules failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load schedules.');
        }
    }

    public function storeSchedule(Request $request, Employee $employee)
    {
        try {
            return $this->employeeService->storeSchedule($request, $employee);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@storeSchedule failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to add schedule.');
        }
    }

    public function updateSchedule(Request $request, EmployeeSchedule $schedule)
    {
        try {
            return $this->employeeService->updateSchedule($request, $schedule);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@updateSchedule failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to update schedule.');
        }
    }

    public function getEmployeeSchedules(Request $request, Employee $employee)
    {
        try {
            return $this->employeeService->getEmployeeSchedules($request, $employee);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@getEmployeeSchedules failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load employee schedules.');
        }
    }

    public function getAllEmployeeForManager()
    {
        try {
            return $this->employeeService->getAllEmployeeForManager();
        } catch (\Throwable $e) {
            Log::error('EmployeeController@getAllEmployeeForManager failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load employees for manager.');
        }
    }

    public function printSchedule(Employee $employee, Request $request)
    {
        try {
            return $this->employeeService->printSchedule($employee, $request);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@printSchedule failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to print schedule.');
        }
    }

    public function printAllSchedules(Request $request)
    {
        try {
            return $this->employeeService->printAllSchedules($request);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@printAllSchedules failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to print all schedules.');
        }
    }

    public function printPosAccounts(Request $request)
    {
        try {
            return $this->employeeService->printPosAccounts($request);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@printPosAccounts failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to print POS accounts.');
        }
    }

    public function printEmployeePersonalDetails(Request $request)
    {
        try {
            return $this->employeeService->printEmployeePersonalDetails($request);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@printEmployeePersonalDetails failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to print employee personal details.');
        }
    }

    public function showDTR(Employee $employee)
    {
        try {
            return $this->employeeService->showDTR($employee);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@showDTR failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load DTR.'], 500);
        }
    }
    
    public function fetchDTR(Request $request, Employee $employee)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate   = $request->input('end_date');

            return $this->employeeService->fetchDTR($employee, $startDate, $endDate);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@fetchDTR failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load DTR.'], 500);
        }
    }

    public function showDTR_orig(Employee $employee)
    {
        try {
            return $this->employeeService->showDTR_orig($employee);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@showDTR_orig failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load DTR.'], 500);
        }
    }

    public function updateDTR(Request $request, Employee $employee)
    {
        try {
            return $this->employeeService->updateDTR($request, $employee);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@updateDTR failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update DTR.'], 500);
        }
    }

    public function generateEmployeePayrollPdf(Request $request, Employee $employee)
    {
        try {
            return $this->employeeService->generateEmployeePayrollPdf($request, $employee);
        } catch (\Throwable $e) {
            Log::error('EmployeeController@generateEmployeePayrollPdf failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate payroll PDF.');
        }
    }
}