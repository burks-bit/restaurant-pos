<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSchedule extends Model
{
    protected $fillable = [
        'employee_id',
        'schedule_date',
        'shift',
        'time_in',
        'time_out',
        'status',
        'remarks',
        'actual_time_in',
        'actual_time_out',
        'holiday_type',
        'overtime',
        'time_in_photo',
        'time_out_photo',
        'shift_end_date',
    ];

    protected $casts = [
        'schedule_date' => 'string',
        'actual_time_in' => 'datetime:H:i',   // only store the time part
        'actual_time_out' => 'datetime:H:i',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function overtime()
    {
        return $this->hasOne(EmployeeOvertime::class);
    }

}
