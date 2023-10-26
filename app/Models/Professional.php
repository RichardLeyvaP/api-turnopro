<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Professional extends Model
{
    use HasFactory;

    public function business()
    {
        return $this->hasMany(Business::class, 'professional_id');
    }

    public function professionalClients(){
        return $this->belongsToMany(Client::class)->withTimestamps();
    }

    public function branchServices(){
        return $this->belongsToMany(BranchService::class, 'branch_service_professional')->withTimestamps();
    }

    protected $table = "professionals";
}