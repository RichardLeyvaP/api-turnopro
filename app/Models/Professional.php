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

    public function clients(){
        return $this->belongsToMany(Client::class)->withTimestamps();
    }

    public function branchServices(){
        return $this->belongsToMany(BranchService::class)->with('branch')->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function charge()
    {
        return $this->belongsTo(Charge::class);
    }

    public function workplaces()
    {
        return $this->hasMany(Workplace::class);
    }

    protected $table = "professionals";
}