<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStore extends Model
{
    use HasFactory;
    
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function productsales()
    {
        return $this->hasMany(ProductSale::class);
    }

    public function product()
    {
    return $this->belongsTo(Product::class);
    }

    public function store()
    {
    return $this->belongsTo(Store::class);
    }

    public function cashiersales()
    {
        return $this->hasMany(CashierSale::class);
    }

    protected $casts = [
        'product_quantity' => 'integer',
        'product_exit' => 'integer',
        'number_notification' => 'integer'
    ];

     //para decirle a q table debe administrar
    protected $table = "product_store";
}
