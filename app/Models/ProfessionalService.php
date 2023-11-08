<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessionalService extends Model
{
    use HasFactory;

    public function serviceorders()
    {
        return $this->hasMany(Order::class);
    }

    public function branchService()
    {
        return $this->belongsTo(BranchService::class);
    }

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }

    //para decirle a q table debe administrar
    protected $table = "branch_service_professional";
}
