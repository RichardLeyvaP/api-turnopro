<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Professional extends Model
{
    use HasFactory;
    use HasRelationships;

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

    public function branches(){
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function branchRules(){
        return $this->belongsToMany(BranchRule::class, 'branch_rule_professional')->withPivot('data','estado')->withTimestamps();
    }
    
    public function branchRuleProfessionals()
    {
        return $this->hasMany(BranchRuleProfessional::class);
    }

    public function clientProfessionals()
    {
        return $this->hasMany(ClientProfessional::class);
    }

    public function orders(){
        return $this->hasManyDeep(Order::class, [ClientProfessional::class, Car::class]);
    }

    public function workplaces(){
        return $this->belongsToMany(Workplace::class, 'professional_workplace')->withPivot('data')->withTimestamps();
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function reservations(){
        return $this->hasManyDeep(Reservation::class, [ClientProfessional::class, Car::class]);
    }

    protected $table = "professionals";
}