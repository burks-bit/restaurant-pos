<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmploymentDetail extends Model
{
    protected $fillable = [
        'employee_id',
        'position',
        'department',
        'hire_date',
        'salary',
        'daily_rate',
        'overtime_rate',
        'employment_type',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'hire_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class, 'employment_detail_id');
    }
}
