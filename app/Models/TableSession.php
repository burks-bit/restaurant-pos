<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TableSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'pricing_scheme_id',
        'ref_no',
        'table_id',
        'pax',
        'status',
        'frontdoor_id',
        'cashier_id',
        'total_amount',
        'opened_at',
        'closed_at',
        'order_id',
        'remarks',
        'customer_name',
        'is_shared'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Each session belongs to a table
    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function headCounts()
    {
        return $this->hasMany(TableSessionHead::class, 'table_session_id');
    }

    // Add-ons posted during dining
    public function addons()
    {
        return $this->hasMany(TableSessionAddon::class);
    }

    // Staff who opened the session
    public function frontdoor()
    {
        return $this->belongsTo(User::class, 'frontdoor_id');
    }

    // Staff who closed / received payment
    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function reservation()
    {
        return $this->hasOne(Reservation::class, 'table_session_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isOpen()
    {
        return $this->status === 'open';
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function isClosed()
    {
        return $this->status === 'closed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
