<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchProfessional extends Model
{
    use HasFactory;

    public function professional()
    {
    return $this->belongsTo(Professional::class);
    }

    public function branch()
    {
    return $this->belongsTo(Branch::class);
    }

     //para decirle a q table debe administrar
     protected $table = "branch_professional";
}
