<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workplace extends Model
{
    use HasFactory;

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function professionals(){
        return $this->belongsToMany(Professional::class, 'professional_workplace')->withPivot('data')->withTimestamps();
    }
}
