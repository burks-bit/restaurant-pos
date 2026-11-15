<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDeductionTemplate extends Model
{
    protected $fillable = [
        'employee_id',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function details()
    {
        return $this->hasMany(EmployeeDeductionTemplateDetail::class);
    }
}
