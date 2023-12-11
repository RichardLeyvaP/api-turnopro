<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
    public function businessType()
    {
        return $this->belongsTo(BusinessTypes::class, 'business_type_id');
    }
    public function branchstores(){
        return $this->belongsToMany(Store::class)->withTimestamps();
    }

    public function stores(){
        return $this->belongsToMany(Store::class, 'branch_store')->withTimestamps();
    }

    public function branchServices(){
        return $this->hasMany(BranchService::class);
    }

    public function services(){
        return $this->belongsToMany(Service::class)->withTimestamps();
    }

    public function workplaces()
    {
        return $this->hasMany(Workplace::class);
    }

    public function schedule()
    {
        return $this->hasOne(Schedule::class);
    }

    public function rules(){
        return $this->belongsToMany(Rule::class, 'branch_rule')->withPivot('id')->withTimestamps();
    }
}