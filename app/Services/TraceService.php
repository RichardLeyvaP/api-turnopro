<?php

namespace App\Services;

use App\Models\Trace;
use Illuminate\Support\Carbon;

class TraceService {
    public function store($data){
        $trace = new Trace();
        $trace->branch = $data['branch'];
        $trace->client = $data['client'];
        $trace->amount = $data['amount'];
        $trace->cashier = $data['cashier'];
        $trace->data = Carbon::now();
        $trace->operation = $data['operation'];
        $trace->details = $data['details'];
        $trace->description = $data['description'];
        $trace->save();

        return $trace;
    }
}