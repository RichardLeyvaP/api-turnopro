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
        return $this->belongsTo(Professional::class)->withTrashed();
    }

    public function productStore()
    {
        return $this->belongsTo(ProductStore::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'cashiersale_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    protected $table = "cashiersales";
}
