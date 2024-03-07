<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Reservation extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasRelationships;

    public function car ()
    {
        return $this->belongsTo(Car::class);
    }

    public function tail() 
    {
        return $this->hasOne(Tail::class);
     }

     public function reservations() 
    {
        return $this->belongstToDeep(Tail::class);
     }
}
