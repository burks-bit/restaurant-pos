<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PettyCash extends Model
{
    protected $fillable = [
        'date',
        'total_amount',
        'amount_used',
        'remaining',
        'denominations',
        'notes',
        'posted_by',
        'updated_by'
    ];

    protected $casts = [
        'total_amount' => 'float',
        'amount_used' => 'float',
        'remaining' => 'float',
        'date' => 'date:Y-m-d', // format the date as YYYY-MM-DD
        'denominations' => 'array', // if stored as JSON
    ];

    public function details()
    {
        return $this->hasMany(PettyCashDetail::class);
    }

    // User who posted this petty cash
    public function postedByUser()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    // User who last updated this petty cash
    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
