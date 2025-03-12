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
            ->first();

        if ($existingRecord) {
            // Si existe, actualizar el registro manualmente
            Log::info("Actualizando registro existente de cierre de caja cajera para branch_id: {$data['branch_id']}, user_id: {$data['user_id']}, data: {$data['data']}");

            // Asignar manualmente cada campo
            $existingRecord->totalTip = $data['totalTip'];
            $existingRecord->totalProduct = $data['totalProduct'];
            $existingRecord->totalService = $data['totalService'];
            $existingRecord->totalCash = $data['totalCash'];
            $existingRecord->totalCreditCard = $data['totalCreditCard'];
            $existingRecord->totalDebit = $data['totalDebit'];
            $existingRecord->totalTransfer = $data['totalTransfer'];
            $existingRecord->totalOther = $data['totalOther'];
            $existingRecord->totalMount = $data['totalMount'];
            $existingRecord->totalCardGif = $data['totalCardGif'];
            $existingRecord->existence = $data['existence'];
            $existingRecord->cashFound = $data['cashFound'];
            $existingRecord->extraccion = $data['extraccion'];
            $existingRecord->branch_id = $data['branch_id'];
            $existingRecord->data = $data['data'];
            $existingRecord->adelanto = $data['adelanto'];
            $existingRecord->bonos = $data['bonos'];
            $existingRecord->diferencia = $data['diferencia'];
            $existingRecord->user_id = $data['user_id'];
            $existingRecord->description = $data['description'];

            // Guardar el registro actualizado
            $existingRecord->save();

            return $existingRecord;
        } else {
            // Si no existe, crear un nuevo registro manualmente
            Log::info("Creando nuevo registro de cierre de caja cajera para branch_id: {$data['branch_id']}, user_id: {$data['user_id']}, data: {$data['data']}");

            // Crear una nueva instancia del modelo y asignar manualmente cada campo
            $newRecord = new CashierBoxClosing();
            $newRecord->totalTip = $data['totalTip'];
            $newRecord->totalProduct = $data['totalProduct'];
            $newRecord->totalService = $data['totalService'];
            $newRecord->totalCash = $data['totalCash'];
            $newRecord->totalCreditCard = $data['totalCreditCard'];
            $newRecord->totalDebit = $data['totalDebit'];
            $newRecord->totalTransfer = $data['totalTransfer'];
            $newRecord->totalOther = $data['totalOther'];
            $newRecord->totalMount = $data['totalMount'];
            $newRecord->totalCardGif = $data['totalCardGif'];
            $newRecord->existence = $data['existence'];
            $newRecord->cashFound = $data['cashFound'];
            $newRecord->extraccion = $data['extraccion'];
            $newRecord->branch_id = $data['branch_id'];
            $newRecord->data = $data['data'];
            $newRecord->adelanto = $data['adelanto'];
            $newRecord->bonos = $data['bonos'];
            $newRecord->diferencia = $data['diferencia'];
            $newRecord->user_id = $data['user_id'];
            $newRecord->description = $data['description'];

            // Guardar el nuevo registro
            $newRecord->save();

            return $newRecord;
        }
    }
}
