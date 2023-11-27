<?php

namespace App\Services;
use App\Models\Service;

class ServiceService {
    public function show($id)
    {
        return Service::find($id);
    }
}