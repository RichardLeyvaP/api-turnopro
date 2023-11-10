<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public function branchServiceProfessional()
    {
        return $this->belongsTo(ProfessionalService::class);
    }

    public function productStore()
    {
        return $this->belongsTo(ProductStore::class);
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    protected $casts = [
        'is_product' => 'integer',
        'price' => 'double',
        'request_delete' => 'integer'
    ];
}
