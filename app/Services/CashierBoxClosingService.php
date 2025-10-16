<?php

namespace App\Services;

use App\Models\CashierBoxClosing;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CashierBoxClosingService
{

    public function upsertCashierBoxClosing(array $data)
    {
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
            'details',
            'box_close_id'
        ];
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
