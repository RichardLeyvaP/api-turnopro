<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseStudent extends Model
{
    use HasFactory;

    public function student()
    {
    return $this->belongsTo(Student::class);
    }

    public function course()
    {
    return $this->belongsTo(Course::class);
    }

    public function finances()
    {
        return $this->hasMany(Finance::class);
    }

    protected $table = "course_student";
}
