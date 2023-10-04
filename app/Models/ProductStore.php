<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStore extends Model
{
    use HasFactory;
    
    public function productorders()
    {
        return $this->hasMany(Order::class, 'product_id');
    }
     //para decirle a q table debe administrar
    protected $table = "product_store";
}
