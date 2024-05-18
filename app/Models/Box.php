<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Box extends Model
{
    use HasFactory;

    public function branches()
    {
        return $this->belongsTo(Branch::class);
    }

    public function boxClose(){
        return $this->hasOne(BoxClose::class);
    }

    
    protected $casts = [
        'cashFound' => 'double',
        'extraction' => 'double',
        'existence' => 'double'
    ];
}
