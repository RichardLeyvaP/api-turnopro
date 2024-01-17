<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    public function clientProfessional()
    {
        return $this->belongsTo(ClientProfessional::class);
    }

}
