<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchServiceProfessional extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function branchService()
    {
    return $this->belongsTo(BranchService::class)->withTrashed();
    }

    public function professional()
    {
    return $this->belongsTo(Professional::class)->withTrashed();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    //para decirle a q table debe administrar
    protected $table = "branch_service_professional";
}
