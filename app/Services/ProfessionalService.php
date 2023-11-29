<?php

namespace App\Services;
use App\Models\Professional;
use Carbon\Carbon;

class ProfessionalService
{
    public function professionals_branch($branch_id, $professional_id)
    {
        $professionals = Professional::whereHas('branchServices', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
           })->find($professional_id);
                      
           $dataUser = [];
           if ($professionals) {
                $date = Carbon::now();
                $dataUser['id'] = $professionals->id;
                $dataUser['usuario'] = $professionals->name;
                $dataUser['fecha'] = $date->toDateString();
                $dataUser['hora'] = $date->Format('g:i:s A');
           }

           return $dataUser;
    }

    public function branch_professionals($branch_id)
    {
        return $professionals = Professional::whereHas('branchServices', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
           })->get();
    }

    public function get_professionals_service($data)
    {
        return $professionals = Professional::whereHas('branchServices', function ($query) use ($data) {
            $query->where('branch_id', $data['branch_id'])->where('service_id', $data['service_id']);
        })->select('id', 'name','surname','second_surname')->get();
    }

    public function professionals_ganancias($data)
    {
        return $professionals = Professional::whereHas('branchServices', function ($query) use ($data) {
            $query->where('branch_id', $data['branch_id'])->where('service_id', $data['service_id']);
        })->select('id', 'name','surname','second_surname')->get();
    }

}