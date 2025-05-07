<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerPurchase extends Model
{
    use HasFactory;

    protected $table = 'worker_purchases';

    protected $fillable = [
        'professional_id',
        'branch_id',
        'product_id',
        'percent_wint',
        'date',
        'price',
        'discount',
        'cant',
        'total',
        'discount_date',
        'status'
    ];

    protected $casts = [
        'date' => 'date',
        'discount_date' => 'date',
        'price' => 'float',
        'discount' => 'float',
        'total' => 'float',
    ];

    // Relaciones
    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Relación con Product
    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
