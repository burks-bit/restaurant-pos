<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_id',
        'employee_id',

        'days',
        'hours',
        'overtime_hours',

        'basic_pay',
        'overtime_pay',
        'allowances',

        'gross_pay',
        'total_earnings',
        'total_deductions',
        'net_pay',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Belongs to Payroll
    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    // Belongs to Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Has many deductions
    public function deductions()
    {
        return $this->hasMany(PayrollDeduction::class);
    }

    public function earnings()
    {
        return $this->hasMany(PayrollEarning::class, 'payroll_item_id');
    }
}