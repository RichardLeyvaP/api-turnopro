<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    public function clientperson()
    {
        return $this->belongsTo(ClientPerson::class, 'client_person_id');
    }

    public function carorder()
    {
        return $this->hasMany(Order::class);
    }
}
