<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableSessionHead extends Model
{
    protected $fillable = [
        'table_session_id',
        'head_pricing_rule_id',
        'qty',
        'price_snapshot',
        'subtotal',
    ];

    public function session()
    {
        return $this->belongsTo(TableSession::class, 'table_session_id');
    }

    public function headRule()
    {
        return $this->belongsTo(HeadPricingRule::class, 'head_pricing_rule_id');
    }
}
