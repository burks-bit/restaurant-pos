<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    protected $fillable = [
        'order_id',
        'payment_method_id',
        'amount',
        'amount_tendered',
        'reference_no',
        'remarks',
        'received_by',
        'is_void',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_void' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // Each payment belongs to one order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Each payment uses one payment method
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    // Who received the payment (cashier)
    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
