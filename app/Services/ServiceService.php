<?php
namespace App\Services;

use App\Models\Service;
use Illuminate\Support\Facades\File;

class ServiceService{

    public function index()
    {
        return Service::all();
    }

    public function show($data)
    {
        return Service::find($data);
    }

    public function store(array $data)
    {
        return Service::create($data);
    }

    public function delete_image($id)
    {
        $service = Service::find($id);
        if ($service->image_service) {
            $destination=public_path("storage\\".$service->image_service);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
            }
    }

    public function update($data)
    {
        $service = Service::find($data['id']);
        return $service->update($data);
    }

    public function delete($id)
    {
        $service = Service::find($id)->delete();
        //return $service->update($data);
    }
}
