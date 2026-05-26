<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeOvertime extends Model
{
    protected $fillable = [
        'employee_id',
        'employee_schedule_id',
        'ot_date',
        'start_time',
        'end_time',
        'total_hours',
        'type',
        'status',
        'remarks',
    ];

    protected $casts = [
        'ot_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'total_hours' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function schedule()
    {
        return $this->belongsTo(EmployeeSchedule::class, 'employee_schedule_id');
    }
}
