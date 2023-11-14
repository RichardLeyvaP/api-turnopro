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

    public function professional()
    {
    return $this->belongsTo(Professional::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    //para decirle a q table debe administrar
    protected $table = "branch_service_professional";
}
