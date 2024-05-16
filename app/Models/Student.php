<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    public function courses(){
        return $this->belongsToMany(Course::class)->withPivot('id','course_id','student_id ', 'reservation_payment','total_payment', 'image_url', 'enabled', 'payment_status', 'amount_pay')->withTimestamps();
    }

    public function productsales()
    {
        return $this->hasMany(ProductSale::class);
    }
}
