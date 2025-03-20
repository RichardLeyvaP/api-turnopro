<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientProfessional extends Model
{
    use HasFactory;

    public function cars()
    {
        return $this->hasMany(Car::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function professional()
    {
        return $this->belongsTo(Professional::class)->withTrashed();
    }
    //para decirle a q table debe administrar
    protected $table = "client_professional";
}
