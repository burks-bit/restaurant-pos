<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppRoute extends Model
{
    protected $table = 'app_routes';

    protected $fillable = [
        'route_name',
        'route',
    ];

    public function userAccesses()
    {
        return $this->hasMany(UserAccess::class, 'route_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_accesses', 'route_id', 'user_id')
            ->withTimestamps();
    }
}