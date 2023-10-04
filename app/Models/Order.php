<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public function orderservice()
    {
        return $this->belongsTo(PersonService::class, 'service_id');
    }

    public function orderproduct()
    {
        return $this->belongsTo(ProductStore::class, 'product_id');
    }

    public function ordercar()
    {
        return $this->belongsTo(Car::class);
    }
}
