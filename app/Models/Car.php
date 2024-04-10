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

    public function reservation()
    {
        return $this->hasOne(Reservation::class);
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function professionalPayment()
    {
        return $this->belongsTo(ProfessionalPayment::class, 'professional_payment_id');
    }

    protected $casts = [
        'amount' => 'double'
    ];
}
