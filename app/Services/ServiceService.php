<?php
namespace App\Services;

use App\Models\Service;

class ServiceService{

    public function show($data)
    {
        return Service::find($data);
    }

}
