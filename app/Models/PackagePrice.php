<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackagePrice extends Model
{
    protected $fillable = [
        'package_id',
        'pax',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
