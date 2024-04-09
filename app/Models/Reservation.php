<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function tail() 
    {
        return $this->hasOne(Tail::class);
     }
}
