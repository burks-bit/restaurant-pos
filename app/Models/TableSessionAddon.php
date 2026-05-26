<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableSessionAddon extends Model
{
    protected $fillable = [
        'table_session_id',
        'inventory_item_id',
        'item_name',
        'unit',
        'quantity',
        'unit_price',
        'subtotal',
        'is_billed',
        'is_void',
        'void_remarks',
        'voided_by'  
    ];

    public function tableSession()
    {
        return $this->belongsTo(TableSession::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
