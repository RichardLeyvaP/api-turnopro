<?php

namespace App\Models;

use App\Traits\CarActionLogger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;
    use CarActionLogger;

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

    public function operationTip()
    {
        return $this->belongsTo(OperationTip::class, 'operation_tip_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    protected $casts = [
        'amount' => 'double',
        'action_descriptions' => 'array',  // Convierte JSON a array
        'change_log' => 'array',            // Convierte JSON a array
        'payment' => 'array'            // Convierte JSON a array
    ];

    protected function comparePayments(array $oldPayments, array $newPayments): string
{
    $changes = [];
    $fieldNames = [
        'cash' => 'Efectivo',
        'creditCard' => 'Tarjeta de Crédito',
        'debit' => 'Tarjeta de Débito',
        'transfer' => 'Transferencia',
        'other' => 'Otro método',
        'cardGift' => 'Tarjeta de Regalo',
        'tip' => 'Propina',
        'tipByCash' => 'Método de pago de la propina'
    ];

    foreach ($fieldNames as $field => $name) {
        $oldValue = $oldPayments[$field] ?? null;
        $newValue = $newPayments[$field] ?? null;

        // Solo registrar cambios si hay diferencia
        if ($oldValue != $newValue) {
            if (is_numeric($oldValue)) {
                // Para campos numéricos (montos)
                $difference = $newValue - $oldValue;
                if ($difference > 0) {
                    $changes[] = "$name aumentó de $$oldValue a $$newValue (+$$difference)";
                } elseif ($difference < 0) {
                    $changes[] = "$name disminuyó de $$oldValue a $$newValue (-$$" . abs($difference) . ")";
                }
            } else {
                // Para campos no numéricos (como tipByCash)
                $changes[] = "$name cambió de '{$oldValue}' a '{$newValue}'";
            }
        }
    }

    return implode(', ', $changes);
}
}
