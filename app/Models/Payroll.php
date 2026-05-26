<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'cutoff_start',
        'cutoff_end',
        'status',
        'total_gross',
        'total_deductions',
        'total_net',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // One Payroll has many PayrollItems (employees inside payroll)
    public function items()
    {
        return $this->hasMany(PayrollItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers (Optional but Recommended)
    |--------------------------------------------------------------------------
    */

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isPosted()
    {
        return $this->status === 'posted';
    }

    public function isFinalized()
    {
        return $this->status === 'finalized';
    }
}