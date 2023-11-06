<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    public function servicebranchees(){
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function branchServices(){
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }
}
