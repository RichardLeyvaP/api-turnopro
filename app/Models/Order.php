<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public function branchServicePperson()
    {
        return $this->belongsTo(PersonService::class);
    }

    public function productStore()
    {
        return $this->belongsTo(ProductStore::class);
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }
}
