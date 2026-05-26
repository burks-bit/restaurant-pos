<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAccess extends Model
{
    protected $fillable = [
        'user_id',
        'route_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appRoute()
    {
        return $this->belongsTo(AppRoute::class, 'route_id');
    }
}