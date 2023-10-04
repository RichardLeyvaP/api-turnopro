<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonService extends Model
{
    use HasFactory;

    public function serviceorders()
    {
        return $this->hasMany(Order::class, 'service_id');
    }
    //para decirle a q table debe administrar
    protected $table = "branch_service_person";
}
