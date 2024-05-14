<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restday extends Model
{
    use HasFactory;

    protected $fillable = ['day', 'state', 'professional_id'];

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }
}
