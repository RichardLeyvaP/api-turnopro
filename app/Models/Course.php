<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function students(){
        return $this->belongsToMany(Student::class)->withPivot('id','course_id','student_id ', 'reservation_payment','total_payment', 'image_url')->withTimestamps();
    }

    public function professionals(){
        return $this->belongsToMany(Professional::class, 'course_professional')->withPivot('id')->withTimestamps();
    }

}
