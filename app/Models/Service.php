<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    public function branchServices(){
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    protected $casts = [
        'simultaneou' => 'integer',
        'price_service' => 'double:8,2',
        'profit_percentaje' => 'double:8,2',
        'duration_service' => 'integer'
    ];
}
