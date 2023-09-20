<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    public function branches()
    {
        return $this->belongsTo(Branch::class);
    }
    public function products(){
        return $this->belongsToMany(Product::class)->withPivot('product_quantity','product_exit','number_notification')->as('storeproducts')->withTimestamps();
    }
}
