<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnrollmentStore extends Model
{
    use HasFactory;

    public function enrollment()
    {
    return $this->belongsTo(Enrollment::class);
    }

    public function store()
    {
    return $this->belongsTo(Store::class);
    }


    //para decirle a q table debe administrar
    protected $table = "enrollment_store";
}
