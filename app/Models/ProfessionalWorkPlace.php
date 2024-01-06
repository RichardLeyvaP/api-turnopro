<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessionalWorkPlace extends Model
{
    use HasFactory;

    public function professional()
    {
    return $this->belongsTo(Professional::class);
    }

    public function workplace()
    {
    return $this->belongsTo(Workplace::class);
    }

    protected $casts = [
        'places' => 'integer'
    ];

     //para decirle a q table debe administrar
    protected $table = "professional_workplace";
}
