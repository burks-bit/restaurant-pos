<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\EmployeeSchedule;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $firstNames = [
            'Juan', 'Jose', 'Antonio', 'Maria', 'Carmen', 'Angel', 'Rafael',
            'Isabella', 'Mark', 'Kristine', 'Michael', 'Luis', 'Daniel', 'Grace',
            'Jovelyn', 'Paulo', 'Ella', 'Francis', 'Carlo', 'Patricia'
        ];

        $middleNames = [
            'Santos', 'Reyes', 'Cruz', 'Delgado', 'Lopez', 'Garcia', 'Dela Cruz',
            'Martinez', 'Ramos', 'Flores'
        ];

        $lastNames = [
            'Dela Rosa', 'Torres', 'Villanueva', 'Mendoza', 'Castillo', 'Santiago', 
            'Navarro', 'Valdez', 'Aquino', 'Padilla', 'Guerrero', 'Ortiz', 'Alvarez'
        ];

        $genders = ['male', 'female'];
        $statuses = ['active', 'inactive'];
        $positions = ['Cashier', 'Manager', 'Supervisor', 'Clerk', 'HR Staff'];
        $departments = ['Operations', 'HR', 'Finance', 'Admin'];

        for ($i = 1; $i <= 20; $i++) {

            $firstName = $firstNames[array_rand($firstNames)];
            $middleName = $middleNames[array_rand($middleNames)];

            do {
                $lastName = $lastNames[array_rand($lastNames)];
            } while ($lastName === $middleName);

            $gender = $genders[array_rand($genders)];
            $email = strtolower($firstName . '.' . str_replace(' ', '', $lastName) . $i . '@example.com');

            // ✅ Create Employee
            $employee = Employee::create([
                'employee_code' => 'EMP' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'email' => $email,
                'contact_number' => '09' . rand(100000000, 999999999),
                'birth_date' => now()->subYears(rand(20, 50))->format('Y-m-d'),
                'gender' => $gender,
                'address' => 'Barangay ' . rand(1, 100) . ', City ' . rand(1, 20),
                'status' => $statuses[array_rand($statuses)],
                'access' => rand(0, 7),
            ]);

            $role = $employee->access;

            // ✅ Create Linked User Account
            $employee->user()->create([
                'name' => $firstName . ' ' . $middleName . ' ' . $lastName,
                'email' => $email,
                'password' => Hash::make('1'),
                'role' => $role,
            ]);

            // ==================================================
            // ✅ Create Employment Detail
            // ==================================================
            EmploymentDetail::create([
                'employee_id' => $employee->id,
                'position' => $positions[array_rand($positions)],
                'department' => $departments[array_rand($departments)],
                'hire_date' => now()->subYears(rand(1, 5))->format('Y-m-d'),
                'salary' => rand(20000, 40000),
                'daily_rate' => rand(800, 1500),
                'employment_type' => rand(0, 1) ? 'regular' : 'contractual',
            ]);

            // ==================================================
            // ✅ Create February Schedule (Morning 8AM–5PM)
            // ==================================================
            $year = now()->year;
            $startDate = Carbon::create($year, 3, 1);
            $endDate = $startDate->copy()->endOfMonth();

            while ($startDate <= $endDate) {

                $status = 'Scheduled';
                $remarks = null;

                // Sunday = Day Off
                if ($startDate->isSunday()) {
                    $status = 'Day Off';
                }

                // Random Absent (5% chance)
                if (rand(1, 100) <= 5 && $status === 'Scheduled') {
                    $status = 'Absent';
                    $remarks = 'Unexcused absence';
                }

                $actualTimeIn = null;
                $actualTimeOut = null;

                if ($status === 'Scheduled') {

                    // Base schedule
                    $scheduledIn = '08:00:00';
                    $scheduledOut = '17:00:00';

                    // Random early or late (±30 mins)
                    $minuteVarianceIn = rand(-30, 30);
                    $minuteVarianceOut = rand(-30, 30);

                    $actualTimeIn = Carbon::createFromTime(8, 0, 0)
                        ->addMinutes($minuteVarianceIn)
                        ->format('H:i:s');

                    $actualTimeOut = Carbon::createFromTime(17, 0, 0)
                        ->addMinutes($minuteVarianceOut)
                        ->format('H:i:s');
                }

                EmployeeSchedule::create([
                    'employee_id' => $employee->id,
                    'schedule_date' => $startDate->format('Y-m-d'),
                    'shift' => 'Morning',
                    'time_in' => '08:00:00',
                    'time_out' => '17:00:00',
                    'status' => $status,
                    'remarks' => $remarks,
                    'actual_time_in' => $actualTimeIn,
                    'actual_time_out' => $actualTimeOut,
                ]);

                $startDate->addDay();
            }
        }
    }
}