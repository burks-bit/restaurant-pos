<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PettyCashDetail extends Model
{
    protected $fillable = [
        'petty_cash_id',
        'purpose',
        'amount',
        'denominations',
        'notes',
        'posted_by'
    ];

    protected $casts = [
        'denominations' => 'array',
    ];

    public function pettyCash()
    {
        return $this->belongsTo(PettyCash::class);
    }

    // User who posted this detail
    public function postedByUser()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
