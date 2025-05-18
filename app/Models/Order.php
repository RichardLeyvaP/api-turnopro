<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public function branchServiceProfessional()
    {
        return $this->belongsTo(BranchServiceProfessional::class)->withTrashed();
    }

    public function productStore()
    {
        return $this->belongsTo(ProductStore::class)->withTrashed();
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function professional()
    {
        return $this->belongsTo(Professional::class)->withTrashed();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    protected $casts = [
        'is_product' => 'integer',
        'price' => 'double',
        'request_delete' => 'integer'
    ];
}
