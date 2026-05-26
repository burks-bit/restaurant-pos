<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationPax extends Model
{
    protected $fillable = [
        'reservation_id', 'head_pricing_rule_id',
        'qty', 'price_snapshot', 'subtotal',
    ];

    protected $casts = [
        'price_snapshot' => 'decimal:2',
        'subtotal'       => 'decimal:2',
    ];

    public function headPricingRule()
    {
        return $this->belongsTo(HeadPricingRule::class);
    }
}