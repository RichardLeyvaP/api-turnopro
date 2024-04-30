<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Client extends Model
{
    use HasFactory;
    use HasRelationships;

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

    public function cars(){
        return $this->hasManyThrough(Car::class, ClientProfessional::class);
    }
    public function reservations(){
        return $this->hasManyDeep(Reservation::class, [ClientProfessional::class, Car::class]);
    }

    public function surveys()
    {
        return $this->belongsToMany(Survey::class, 'client_survey')->withTimestamps();
    }
}
