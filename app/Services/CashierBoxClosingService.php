<?php

namespace App\Services;

use App\Models\CashierBoxClosing;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CashierBoxClosingService
{

    public function upsertCashierBoxClosing(array $data)
    {
        // Verificar si ya existe un registro
        $existingRecord = CashierBoxClosing::where('branch_id', $data['branch_id'])
            ->where('user_id', $data['user_id'])
            ->whereDate('data', $data['data'])
            ->where('type', $data['type'])
            ->first();

        // Campos permitidos
        $fields = [
            'totalTip',
            'totalProduct',
            'totalService',
            'totalCash',
            'totalCreditCard',
            'totalDebit',
            'totalTransfer',
            'totalOther',
            'totalMount',
            'totalCardGif',
            'existence',
            'cashFound',
            'extraction',
            'branch_id',
            'data',
            'advancement',
            'totalBonus',
            'difference',
            'user_id',
            'description',
            'differenceBox',
            'differenceAccounts',
            'differencePay',
            'type',
            'details'
        ];

        if ($existingRecord) {
            // Actualizar registro existente
            Log::info("Actualizando registro existente de cierre de caja cajera para branch_id: {$data['branch_id']}, user_id: {$data['user_id']}, data: {$data['data']}");

            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $existingRecord->$field = ($field === 'details' && is_array($data[$field]))
                        ? json_encode($data[$field])
                        : $data[$field];
                }
            }

            $existingRecord->save();
            return $existingRecord;
        } else {
            // Crear nuevo registro asignando campo por campo
            Log::info("Creando nuevo registro de cierre de caja cajera para branch_id: {$data['branch_id']}, user_id: {$data['user_id']}, data: {$data['data']}");

            $newRecord = new CashierBoxClosing();

            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $newRecord->$field = ($field === 'details' && is_array($data[$field]))
                        ? json_encode($data[$field])
                        : $data[$field];
                }
            }

            $newRecord->save();
            return $newRecord;
        }
    }
}
