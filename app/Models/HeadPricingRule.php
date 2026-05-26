<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HeadPricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'pricing_scheme_id',
        'label',
        'min_age',
        'max_age',
        'min_height',
        'max_height',
        'price',
        'is_active',
    ];

    public function pricingScheme()
    {
        return $this->belongsTo(PricingScheme::class);
    }

    public function sessionHeads()
    {
        return $this->hasMany(TableSessionHead::class);
    }
}
