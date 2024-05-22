<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierSale extends Model
{
    use HasFactory;
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }

    public function productStore()
    {
        return $this->belongsTo(ProductStore::class);
    }

    protected $table = "cashiersales";
}
