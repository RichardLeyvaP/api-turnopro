<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    public function clientProfessional()
    {
        return $this->belongsTo(ClientProfessional::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reservations()
    {
        return $this->hasOne(Reservation::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    protected $casts = [
        'amount' => 'double'
    ];
}
