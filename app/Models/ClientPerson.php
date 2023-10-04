<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientPerson extends Model
{
    use HasFactory;

    public function cars()
    {
        return $this->hasMany(Car::class, 'client_person_id');
    }
    //para decirle a q table debe administrar
    protected $table = "client_person";
}
