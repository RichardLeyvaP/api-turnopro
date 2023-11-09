<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchServiceProfessional extends Model
{
    use HasFactory;

    public function branchService()
    {
    return $this->belongsTo(BranchService::class);
    }

    //para decirle a q table debe administrar
    protected $table = "branch_service_professional";
}
