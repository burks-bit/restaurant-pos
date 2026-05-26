<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EarningType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status'
    ];

    public function earnings()
    {
        return $this->hasMany(PayrollEarning::class);
    }
}
