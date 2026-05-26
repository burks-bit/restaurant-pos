<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    protected $fillable = [
        'cashier_id',
        'shift_id',
        'date',
        'cash_sales',
        'gcash_sales',
        'other_sales',
        'total_expenses',
        'expected_cash_on_hand',
        'cash_on_hand',
        'over_short',
        'denomination'
    ];

    protected $casts = [
        'date'         => 'date',
    ];

    // Cashier (User)
    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    // Shift
    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    // Orders handled in this register (optional but useful)
    public function orders()
    {
        return $this->hasMany(Order::class, 'cash_register_id');
    }

    // Expenses (if tied to register)
    public function expenses()
    {
        return $this->hasMany(Expense::class, 'cash_register_id');
    }
}
