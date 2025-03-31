<?php

namespace App\Traits;

trait CarActionLogger
{
    public function addActionDescription(string $actionType, string $description, string $nameProfessional)
    {
        // Obtener las descripciones actuales (maneja tanto array como string JSON)
        $descriptions = [];
        
        if (is_array($this->action_descriptions)) {
            $descriptions = $this->action_descriptions;
        } elseif (is_string($this->action_descriptions)) {
            $descriptions = json_decode($this->action_descriptions, true) ?: [];
        }
        
        // Agregar la nueva acción
        $descriptions[] = [
            'action_type' => $actionType,
            'description' => $description,
            'nameProfessional' => $nameProfessional,
            'timestamp' => now()->toDateTimeString()
        ];
        
        // Guardar (Laravel convertirá automáticamente a JSON por el cast)
        $this->action_descriptions = $descriptions;
    }
    
    public function logChanges(string $changes, string $nameProfessional, string $actionType)
{
    // Obtener el log actual de cambios (maneja tanto array como string JSON)
    $changeLog = [];
    
    if (is_array($this->change_log)) {
        $changeLog = $this->change_log;
    } elseif (is_string($this->change_log)) {
        $changeLog = json_decode($this->change_log, true) ?: [];
    }
    
    // Agregar la nueva entrada de log
    $changeLog[] = [
        'action_type' => $actionType,
        'changes' => $changes, // Guardamos el string directamente
        'nameProfessional' => $nameProfessional, // Professional puede ser diferente en cada cambio
        'timestamp' => now()->toDateTimeString()
    ];
    
    // Guardar (Laravel convertirá automáticamente a JSON por el cast)
    $this->change_log = $changeLog;
}
public function comparePaymentChanges(array $oldPayments, array $newPayments): string
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
            'tipByCash' => 'Método de propina'
        ];

        foreach ($fieldNames as $field => $name) {
            $oldValue = $oldPayments[$field] ?? null;
            $newValue = $newPayments[$field] ?? null;

            if ($oldValue != $newValue) {
                if (is_numeric($oldValue)) {
                    $difference = $newValue - $oldValue;
                    $formattedOld = number_format($oldValue, 2);
                    $formattedNew = number_format($newValue, 2);
                    $formattedDiff = number_format(abs($difference), 2);
                    
                    if ($difference > 0) {
                        $changes[] = "$name aumentó de $$formattedOld a $$formattedNew (+$$formattedDiff)";
                    } elseif ($difference < 0) {
                        $changes[] = "$name disminuyó de $$formattedOld a $$formattedNew (-$$formattedDiff)";
                    } else {
                        $changes[] = "$name cambió de $$formattedOld a $$formattedNew";
                    }
                } else {
                    $oldDisplay = $oldValue ?? 'N/A';
                    $newDisplay = $newValue ?? 'N/A';
                    $changes[] = "$name cambió de '{$oldDisplay}' a '{$newDisplay}'";
                }
            }
        }

        return empty($changes) ? 'Sin cambios en métodos de pago' : implode(', ', $changes);
    }
}