<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollDeduction extends Model
{
    protected $fillable = [
        'payroll_item_id',
        'deduction_type',
        'amount',
        'remarks',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Belongs to PayrollItem
    public function payrollItem()
    {
        return $this->belongsTo(PayrollItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Shortcut Relationship (Optional Advanced)
    |--------------------------------------------------------------------------
    */

    public function payroll()
    {
        return $this->hasOneThrough(
            Payroll::class,
            PayrollItem::class,
            'id',           // Foreign key on payroll_items table
            'id',           // Foreign key on payrolls table
            'payroll_item_id', // Local key on payroll_deductions
            'payroll_id'    // Local key on payroll_items
        );
    }
}