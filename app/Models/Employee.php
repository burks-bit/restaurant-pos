<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'employee_code',
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'contact_number',
        'birth_date',
        'gender',
        'address',
        'status',
        'access',
        'branch_id',
    ];

    public function employmentDetails()
    {
        return $this->hasMany(EmploymentDetail::class);
    }

    public function latestEmployment()
    {
        return $this->hasOne(EmploymentDetail::class)->latestOfMany();
    }

    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function user()
    {
        return $this->hasOne(User::class, 'employee_id');
    }

    public function overtimes()
    {
        return $this->hasMany(EmployeeOvertime::class);
    }

    public function payrollItems()
    {
        return $this->hasMany(PayrollItem::class);
    }
}
