<?php

namespace App\Services;

use App\Models\CashierBoxClosing;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CashierBoxClosingService
{

    public function upsertCashierBoxClosing(array $data)
    {
        // Verificar si ya existe un registro con los mismos branch_id, user_id y data
        $existingRecord = CashierBoxClosing::where('branch_id', $data['branch_id'])
            ->where('user_id', $data['user_id'])
            ->whereDate('data', $data['data'])
            ->where('type', $data['type'])
            ->first();
    
        // Campos que se pueden asignar dinámicamente
        $fields = [
            'totalTip', 'totalProduct', 'totalService', 'totalCash', 'totalCreditCard',
            'totalDebit', 'totalTransfer', 'totalOther', 'totalMount', 'totalCardGif',
            'existence', 'cashFound', 'extraction', 'branch_id', 'data', 'advancement',
            'totalBonus', 'difference', 'user_id', 'description', 'differenceBox', 'differenceAccounts', 'differencePay', 'type'
        ];
    
        if ($existingRecord) {
            // Si existe, actualizar el registro
            Log::info("Actualizando registro existente de cierre de caja cajera para branch_id: {$data['branch_id']}, user_id: {$data['user_id']}, data: {$data['data']}");
    
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $existingRecord->$field = $data[$field];
                }
            }
    
            $existingRecord->save();
            return $existingRecord;
        } else {
            // Si no existe, crear un nuevo registro
            Log::info("Creando nuevo registro de cierre de caja cajera para branch_id: {$data['branch_id']}, user_id: {$data['user_id']}, data: {$data['data']}");
    
            $newRecord = new CashierBoxClosing();
    
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $newRecord->$field = $data[$field];
                }
            }
    
            $newRecord->save();
            return $newRecord;
        }
    }
}
