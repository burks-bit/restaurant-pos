<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Table extends Model
{
    use HasFactory;
    protected $fillable = ['name','capacity'];

    public function sessions()
    {
        return $this->hasMany(TableSession::class);
    }

    // Get current active session
    public function currentSession()
    {
        return $this->hasOne(TableSession::class)->where('status', 'open');
    }

    public function parent()
    {
        return $this->belongsTo(Table::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Table::class, 'parent_id');
    }
    
}
