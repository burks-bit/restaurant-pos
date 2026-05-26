<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $fillable = [
        'type',
        'percentage',
    ];

    protected $casts = [
        'percentage' => 'integer',
    ];


}
