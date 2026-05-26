<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeductionType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status'
    ];

    public function deductions()
    {
        return $this->hasMany(PayrollDeduction::class);
    }
}
