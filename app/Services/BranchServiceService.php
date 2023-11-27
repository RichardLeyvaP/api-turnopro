<?php

namespace App\Services;
use App\Models\BranchService;
use Illuminate\Support\Facades\Log;

class BranchServiceService {
    public function branch_service_show($service_id, $branch_id)
    {
        Log::info( "Entra a buscar id de la relacion entre una sucursal y un servicio determinado servicio");
        $branchservice = BranchService::where('branch_id', $branch_id)->where('service_id', $service_id)->first();
        if (!$branchservice) {
            $branchservice = new BranchService();
            $branchservice->branch_id = $branch_id;
            $branchservice->service_id = $service_id;
            $branchservice->save();
        }
        return $branchservice->id;
    }
}