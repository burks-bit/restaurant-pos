<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderLeftover extends Model
{
    protected $fillable = [
        'order_id',
        'weight',
        'amount'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
