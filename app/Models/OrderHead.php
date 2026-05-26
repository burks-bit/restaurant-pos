<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderHead extends Model
{
    protected $fillable = [
        'order_id',       
        'head_pricing_rule_id',
        'quantity',
        'price_snapshot',
        'subtotal'     
    ];

    public function headPricingRule()
    {
        return $this->belongsTo(HeadPricingRule::class, 'head_pricing_rule_id');
    }
}
