<?php

namespace App\Services;
use App\Models\BranchServiceProfessional;
use Illuminate\Support\Facades\Log;

class BranchServiceProfessionalService
{

    public function branch_service_professional($branch_service_id, $professional_id)
    {
        Log::info( "Entra a buscar el id de la relacion entre un professional y el servicio brundado en una branch");
            $branchServiceProfessional = BranchServiceProfessional::where('branch_service_id', $branch_service_id)->where('professional_id', $professional_id)->first();
            if (!$branchServiceProfessional) {
                $branchServiceProfessional = new BranchServiceProfessional();
                $branchServiceProfessional->branch_service_id = $branch_service_id;
                $branchServiceProfessional->professional_id = $professional_id;
                $branchServiceProfessional->save();
            }
            return $branchServiceProfessional->id;
    }

}