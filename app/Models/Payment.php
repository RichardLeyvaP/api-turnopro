<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function cashierSale()
    {
        return $this->belongsTo(CashierSale::class);
    }

    protected $casts = [
        'cash' => 'double',
        'creditCard' => 'double',
        'debit' => 'double',
        'transfer' => 'double',
        'other' => 'double',
        'cardGif' => 'double'
    ];
}
