<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovementProduct extends Model
{
    use HasFactory;

    protected $casts = [
        'store_out_exit' => 'integer',
        'store_int_exit' => 'integer'
    ];

    protected $table = "movement_product";
}
