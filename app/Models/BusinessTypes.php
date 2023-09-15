<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessTypes extends Model
{
    use HasFactory;

    public function branch()
    {
        return $this->hasMany(Branch::class, 'business_type_id');
    }
}