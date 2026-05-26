<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PricingScheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'is_active',
    ];

    public function headRules()
    {
        return $this->hasMany(HeadPricingRule::class);
    }

    public function tableSessions()
    {
        return $this->hasMany(TableSession::class);
    }
}