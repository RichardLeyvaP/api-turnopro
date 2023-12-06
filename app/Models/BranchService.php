<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchService extends Model
{
    use HasFactory;

    public function branchServiceProfessional()
    {
         return $this->belongsToMany(Professional::class, 'branch_service_professional');
    }

    public function branchServiceProfessionals()
    {
         return $this->hasMany(BranchServiceProfessional::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

        //para decirle a q table debe administrar
    protected $table = "branch_service";
}
