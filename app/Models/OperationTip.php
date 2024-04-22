<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationTip extends Model
{
    use HasFactory;

    protected $table = 'operation_tip';

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
        return $this->hasMany(Car::class, 'operation_tip_id');
    }
}
