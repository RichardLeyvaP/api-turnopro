<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyClosure extends Model
{
    use HasFactory;

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relación con el negocio (opcional)
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Relación con el usuario que realizó el cierre
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $fillable = [
        'branch_id',
        'business_id',
        'available_money',
        'utility',
        'net_utility',
        'retention',
        'discounts',
        'differences',
        'month',
        'incomes', // Si es JSON
        'expenses', // Si es JSON
        'user_id',
        'data', // Agregado basado en tu código anterior
        'client_utility',
        'client_retention',
        'spent',
        'system_incomes',
        'difference_incomes',
        'difference_utility',
        'difference_retention',
        'difference_spent',
        'description'
    ];

    protected $casts = [
        'incomes' => 'array',
        'expenses' => 'array',
        'available_money' => 'float',
        'utility' => 'float',
        'net_utility' => 'float',
        'retention' => 'float',
        'discounts' => 'float',
        'differences' => 'float',
        'client_utility',
        'client_retention' => 'float',
        'spent' => 'float',
        'system_incomes' => 'float',
        'difference_incomes' => 'float',
        'difference_utility' => 'float',
        'difference_retention' => 'float',
        'difference_spent' => 'float'
    ];
}
