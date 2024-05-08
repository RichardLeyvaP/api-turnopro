<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseProfessional extends Model
{
    use HasFactory;

    public function course()
    {
    return $this->belongsTo(Course::class);
    }

    public function professional()
    {
    return $this->belongsTo(Professional::class);
    }

    protected $table = "course_professional";
}
