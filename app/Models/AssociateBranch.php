<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssociateBranch extends Model
{
    use HasFactory;

    public function associate()
    {
    return $this->belongsTo(Associated::class);
    }

    public function branch()
    {
    return $this->belongsTo(Branch::class);
    }

    protected $table = "associate_branch";
}
