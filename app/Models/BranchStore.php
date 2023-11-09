<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchStore extends Model
{
    use HasFactory;

    public function branch()
    {
    return $this->belongsTo(Branch::class);
    }

    public function store()
    {
    return $this->belongsTo(Store::class);
    }


    //para decirle a q table debe administrar
    protected $table = "branch_store";
}
