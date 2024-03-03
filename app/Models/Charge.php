<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Charge extends Model
{
    use HasFactory;

    public function professionals()
    {
        return $this->hasMany(Professional::class);
    }

    public function permissions(){
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }
}
