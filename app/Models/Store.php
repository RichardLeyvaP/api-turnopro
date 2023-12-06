<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    public function products(){
        return $this->belongsToMany(Product::class, 'product_store')->withPivot('product_quantity','product_exit','number_notification')->withTimestamps();
    }
    public function productStores(){
        return $this->hasMany(ProductStore::class);
    }
    public function storebranchees(){
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function branches(){
        return $this->belongsToMany(Branch::class, 'branch_store')->withTimestamps();
    }
}
