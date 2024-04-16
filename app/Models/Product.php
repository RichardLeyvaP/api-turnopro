<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function stores(){
        return $this->belongsToMany(Store::class, 'product_store')->withPivot('product_quantity','product_exit','number_notification', 'stock_depletion','id')->withTimestamps();
    }

    public function orders(){
        return $this->hasManyThrough(Order::class, ProductStore::class);
    }

    public function productSales(){
        return $this->hasManyThrough(ProductSale::class, ProductStore::class);
    }

    public function productStores(){
        return $this->hasMany(ProductStore::class);
    }
    
    protected $casts = [
        'purchase_price' => 'double',
        'sale_price' => 'double'
    ];
}
