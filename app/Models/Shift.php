<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
    ];

    // Cash registers under this shift
    public function cashRegisters()
    {
        return $this->hasMany(CashRegister::class, 'shift_id');
    }

    // Optional: Orders per shift
    public function orders()
    {
        return $this->hasMany(Order::class, 'shift_id');
    }
}
