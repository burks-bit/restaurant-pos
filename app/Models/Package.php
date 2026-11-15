<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name',
        'choices',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function prices()
    {
        return $this->hasMany(PackagePrice::class)->orderBy('pax');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
