<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    public function car ()
    {
        return $this->belongsTo(Car::class);
    }

    public function tail() 
    {
        return $this->hasOne(Tail::class);
     }
}
