<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSale extends Model
{
    use HasFactory;

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function productStore()
    {
        return $this->belongsTo(ProductStore::class);
    }
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    protected $casts = [
        'price' => 'double',
        'cant' => 'integer'
    ];

     //para decirle a q table debe administrar
    protected $table = "productsales";
}
