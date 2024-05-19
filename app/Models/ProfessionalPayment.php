<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessionalPayment extends Model
{
    use HasFactory;

    protected $table = 'professionals_payments';

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function professional()
    {
        return $this->belongsTo(Professional::class, 'professional_id');
    }

    public function cars()
    {
        return $this->hasMany(Car::class, 'professional_payment_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(Branch::class, 'enrollment_id');
    }
}
