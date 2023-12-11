<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    use HasFactory;

    public function branches(){
        return $this->belongsToMany(Branch::class, 'branch_rule')->withPivot('id')->withTimestamps();
    }

    public function branchRules(){
        return $this->hasMany(BranchRule::class);
    }
}
