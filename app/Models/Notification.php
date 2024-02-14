<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
