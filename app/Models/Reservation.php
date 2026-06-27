<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'pricing_scheme_id', 'pax',
        'reservation_datetime', 'contact_number', 'remarks', 'status',
        'reservation_fee', 'fee_payment_method', 'fee_reference_no',
        'table_session_id','shift_id'
    ];
 
    protected $casts = [
        'reservation_datetime' => 'datetime',
        'reservation_fee'      => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getFormattedDateAttribute()
    {
        return Carbon::parse($this->reservation_datetime)->format('M d, Y');
    }

    public function getFormattedTimeAttribute()
    {
        return Carbon::parse($this->reservation_datetime)->format('h:i A');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeUpcoming($query)
    {
        return $query->where('reservation_datetime', '>=', now())
                     ->orderBy('reservation_datetime');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('reservation_datetime', today());
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isPast()
    {
        return $this->reservation_datetime < now();
    }

    public function isToday()
    {
        return Carbon::parse($this->reservation_datetime)->isToday();
    }

    // relationships
    public function pricingScheme()
    {
        return $this->belongsTo(PricingScheme::class);
    }
 
    public function reservationPax()
    {
        return $this->hasMany(ReservationPax::class);
    }
 
    public function tableSession()
    {
        return $this->belongsTo(TableSession::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }
}