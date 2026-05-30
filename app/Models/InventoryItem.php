<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'type',
        'unit',
        'current_quantity',
        'unit_price',
        'orderable',
        'status',
        'created_by',
        'updated_by',
        'is_dry',
        'remarks',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2', // or 'float'
        'current_quantity' => 'decimal:1',
    ];

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function orderAddons()
    {
        return $this->hasMany(OrderAddon::class, 'inventory_item_id');
    }
}
