<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'control_no',       // Unique identifier
        'type',             // '20%', '30%', '40%', '50%', 'Free Meal'
        'status',           // 'available', 'used', 'expired'
        'issued_at',        // Date it was given out
        'used_at',          // Date it was redeemed
        'used_by_order_id', // FK to orders table
        'notes',            // Optional remarks
        'validity',            // Optional remarks
    ];

    protected $casts = [
        'validity' => 'date',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'used_by_order_id');
    }
}
