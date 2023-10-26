<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    public function professional()
    {
        return $this->belongsTo(Professional::class, 'professional_id');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class, 'business_id');
    }
    
}