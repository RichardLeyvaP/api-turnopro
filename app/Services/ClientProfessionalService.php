<?php

namespace App\Services;
use App\Models\ClientProfessional;
use Illuminate\Support\Facades\Log;

class ClientProfessionalService {

public function client_professional($client_id, $professional_id)
    {     
        Log::info('client_professional');
            $clientprofessional = ClientProfessional::where('client_professional.client_id',$client_id)->where('client_professional.professional_id',$professional_id)->first();
            if (!$clientprofessional) {
                $clientprofessional = new ClientProfessional();
                $clientprofessional->client_id = $client_id;
                $clientprofessional->professional_id = $professional_id;
                $clientprofessional->save();
            }
            Log::info($clientprofessional);
            return $clientprofessional->id;
    }

}