<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function productsales()
    {
        return $this->hasMany(ProductSale::class);
    }

    public function finances(){
        return $this->HasMany(Finance::class);
    }

    public function stores(){
        return $this->belongsToMany(Store::class, 'enrollment_store')->withTimestamps();
    }

    public function professionalPayments()
    {
        return $this->hasMany(ProfessionalPayment::class, 'enrollment_id');
    }
}
