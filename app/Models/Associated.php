<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Associated extends Model
{
    use HasFactory;

    public function branches(){
        return $this->belongsToMany(Branch::class, 'associate_branch')->withPivot('id')->withTimestamps();
    }

    protected $table = "associates";
}
