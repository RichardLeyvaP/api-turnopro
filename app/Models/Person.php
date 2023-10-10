<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

    public function business()
    {
        return $this->hasMany(Business::class, 'person_id');
    }

    public function personclients(){
        return $this->belongsToMany(Client::class)->withTimestamps();
    }

    public function branchServices(){
        return $this->belongsToMany(BranchService::class, 'branch_service_person')->withTimestamps();
    }
}