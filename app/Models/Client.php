<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    public function professionals(){
        return $this->belongsToMany(Professional::class)->withTimestamps();
    }

    public function clientProfessionals(){
        return $this->hasMany(ClientProfessional::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function comments(){
        return $this->hasManyThrough(Comment::class, ClientProfessional::class);
    }

}
