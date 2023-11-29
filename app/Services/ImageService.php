<?php

namespace App\Services;
use App\Models\BranchServiceProfessional;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class ImageService
{
    public function subirImagen($image, $dir, $campo){
            Log::info($image);
            $filename = $image->file($campo)->storeAs($dir,$image->file($campo)->getClientOriginalName(),'public');
            return $filename;
    }

    public function destroyImagen($image_url){
        $destination=public_path("storage\\".$image_url);
        if (File::exists($destination)) {
            File::delete($destination);
        }
    }

}