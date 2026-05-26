<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'requires_reference',
        'is_cash',
        'is_active',
    ];

    protected $casts = [
        'requires_reference' => 'boolean',
        'is_cash' => 'boolean',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // One payment method can be used in many order payments
    public function orderPayments()
    {
        return $this->hasMany(OrderPayment::class);
    }
}
