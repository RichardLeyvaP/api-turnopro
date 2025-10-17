<?php

namespace App\Services;
use Illuminate\Support\Facades\Log;

use App\Models\Trace;
use Illuminate\Support\Carbon;

class TraceService {
    public function store($data){
        try{
             $trace = new Trace();
        $trace->branch = $data['branch'];
        $trace->client = $data['client'];
        $trace->amount = $data['amount'];
        $trace->cashier = $data['cashier'];
        $trace->data = Carbon::now();
        $trace->operation = $data['operation'];
        $trace->details = $data['details'];
        $trace->description = $data['description'];
        // Solo asigna car_id si existe en el array
        if (array_key_exists('car_id', $data)) {
            $trace->car_id = $data['car_id'];
        }
        $trace->save();

        return $trace;
        }
        catch (\Throwable $th) {
           
        }
            
        
       
    }
}