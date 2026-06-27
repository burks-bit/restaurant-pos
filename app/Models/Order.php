<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\hasOne;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'subtotal',
        'total_discount',
        'order_no',
        'total',
        'discount_type',
        'status',
        'cancelled',
        'cancelled_by',
        'cancellation_remarks',
        'voucher_no_used',
        'voucher_discount_used',
        'payment_method',
        'cash_amount',
        'change_amount',
        'table_number',
        'discount_approving_manager_id',
        'cancel_approving_manager_id',
        'shift_id',
        'reservation_id',
        'reservation_fee_used',
    ];

    // protected $casts = [
    //     'subtotal' => 'decimal:2',
    //     'discount' => 'decimal:2',
    //     'total' => 'decimal:2',
    // ];

    protected $casts = [
        'subtotal' => 'float',
        'total_discount' => 'float',
        'total'    => 'float',
        'voucher_discount_used'    => 'float',
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderHeads(): HasMany
    {
        return $this->hasMany(OrderHead::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(OrderAddon::class);
    }

    public function leftover(): hasOne
    {
        return $this->hasOne(OrderLeftover::class);
    }

    public function tableSession(): BelongsTo
    {
        return $this->belongsTo(TableSession::class, 'id', 'order_id');
    }

    // public function tableSession(): BelongsTo
    // {
    //     return $this->belongsTo(TableSession::class, 'table_session_id');
    // }

    public function voucher()
    {
        return $this->hasOne(Voucher::class, 'used_by_order_id');
    }

    public function payments()
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public static function getCashierIdOnDuty($now, ?int $shiftId): ?int
    {
        if (!$shiftId) {
            return null;
        }

        $order = self::whereDate('created_at', $now)
            ->where('shift_id', $shiftId)
            ->whereNotNull('user_id')
            ->latest('created_at')
            ->first();

        return $order?->user_id;
    }
}
