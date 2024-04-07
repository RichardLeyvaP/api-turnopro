<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function finances(){
        return $this->HasMany(Finance::class);
    }
}
