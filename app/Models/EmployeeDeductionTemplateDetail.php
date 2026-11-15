<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDeductionTemplateDetail extends Model
{
    protected $fillable = [
        'employee_deduction_template_id',
        'deduction_type_id',
        'cutoff',
        'default_value',
    ];

    protected $casts = [
        'default_value' => 'decimal:2',
    ];

    public function template()
    {
        return $this->belongsTo(EmployeeDeductionTemplate::class, 'employee_deduction_template_id');
    }

    public function deductionType()
    {
        return $this->belongsTo(DeductionType::class);
    }
}
