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

    public function clients(){
        return $this->belongsToMany(Client::class)->withTimestamps();
    }

    public function branchServices(){
        return $this->belongsToMany(BranchService::class)->withTimestamps();
    }

    public function branchServiceProfessionals(){
        return $this->hasMany(BranchServiceProfessional::class);
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

    public function branches(){
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function branchRules(){
        return $this->belongsToMany(BranchRule::class, 'branch_rule_professional')->withPivot('data','estado')->withTimestamps();
    }
    
    public function branchRuleProfessionals()
    {
        return $this->hasMany(branchRuleProfessional::class);
    }

    protected $table = "professionals";
}