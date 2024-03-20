<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Finance extends Model
{
    use HasFactory;

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function revenue()
    {
        return $this->belongsTo(Revenue::class);
    }

    protected $casts = [
        'amount' => 'double',
        'control' => 'integer'
    ];
}
