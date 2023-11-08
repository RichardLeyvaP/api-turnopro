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
    public function branchservices(){
        return $this->belongsToMany(Service::class)->withTimestamps();
    }
}