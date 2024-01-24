<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\ClientProfessional;
use App\Models\Professional;
use App\Services\ImageService;
use App\Services\ProfessionalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ProfessionalController extends Controller
{

    private ProfessionalService $professionalService;

    public function __construct(ProfessionalService $professionalService)
    {
        $this->professionalService = $professionalService;
    }


    public function index()
    {
        try {
            return response()->json(['professionals' => Professional::with('user', 'charge')->get()], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar las professionales"], 500);
        }
    }
    public function show(Request $request)
    {
        try {
            $professionals_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['professional' => Professional::with('user', 'charge')->find($professionals_data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el professional"], 500);
        }
    }

    public function professionals_branch(Request $request)
    {
        try {
            $data = $request->validate([
               'professional_id' => 'required|numeric',
               'branch_id' => 'required|numeric'
           ]);
           $professional = $this->professionalService->professionals_branch($data['branch_id'], $data['professional_id']);
           /*$professionals = Professional::whereHas('branchServices', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
           })->find($data['professional_id']);
           
           if ($professionals) {
                $date = Carbon::now();
                $dataUser = [];
                $dataUser['id'] = $professionals->id;
                $dataUser['usuario'] = $professionals->name;
                $dataUser['fecha'] = $date->toDateString();
                $dataUser['hora'] = $date->Format('g:i:s A');
                return response()->json(['professional_branch' => $dataUser], 200);
           }*/
           return response()->json(['professional_branch' => $professional], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Professionals no pertenece a esta Sucursal"], 500);
       }
    }

    public function branch_professionals(Request $request)
    {
        try {
            $data = $request->validate([
               'branch_id' => 'required|numeric'
           ]);
           $professionals = $this->professionalService->branch_professionals($data['branch_id']);
           /*$professionals = Professional::whereHas('branchServices', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
           })->get();
           */
           return response()->json(['professionals' => $professionals], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => "Professionals no pertenece a esta Sucursal"], 500);
       }
    }

    public function get_professionals_service(Request $request)
    {
        try {
            $data = $request->validate([
               'service_id' => 'required|numeric',
               'branch_id' => 'required|numeric'

           ]);
           $professionals =$this->professionalService->get_professionals_service($data);
           /*$professionals = Professional::whereHas('branchServices', function ($query) use ($data) {
            $query->where('branch_id', $data['branch_id'])->where('service_id', $data['service_id']);
        })->select('id', 'name','surname','second_surname')->get();*/
           
           return response()->json(['professionals' => $professionals], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => "Professionals"], 500);
       }
    }

    public function professionals_ganancias(Request $request)
    {
        try {
            $data = $request->validate([
               'professional_id' => 'required|numeric',
               'day' => 'required',
               'startDate' => 'required|date',
               'branch_id' => 'required|numeric',
               'endDate' => 'required|date'
           ]);
           $ganancias = $this->professionalService->professionals_ganancias($data);
          return response()->json(['earningByDay' => $ganancias], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => "Profssional no obtuvo ganancias en este período"], 500);
       }
    }

    public function professionals_ganancias_branch(Request $request)
    {
        try {
            $data = $request->validate([

                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
           ]);
           if ($request->has('mes')) {
            return response()->json(['earningPeriodo' => $this->professionalService->professionals_ganancias_branch_month($data, $request->mes)], 200);
            }
            if ($request->has('startDate') && $request->has('endDate')) {
                return response()->json(['earningPeriodo' => $this->professionalService->professionals_ganancias_branch_Periodo($data, $request->startDate, $request->endDate)], 200);
            }
            else {
                return response()->json(['earningPeriodo' => $this->professionalService->professionals_ganancias_branch_date($data)], 200);
            }
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Profssional no obtuvo ganancias en este período"], 500);
       }
    }
    
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|max:50',
                'surname' => 'required|max:50',
                'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email|unique:professionals',
                'phone' => 'required|max:15',
                'charge_id' => 'required|numeric',
                'user_id' => 'required|numeric'
            ]);            
            $professional = new Professional();
            Log::info($professional->id);
            $filename = "image/default.png";
            if ($request->hasFile('image_url')) {
                //$filename = $this->imageService->subirImagen($request, 'professionals', 'image_url');
                $filename = $request->file('image_url')->storeAs('professionals',$professional->id.'.'.$request->file('image_url')->extension(),'public');
                //$data['image_url'] = $filename;
            }
            $professional->name = $data['name'];
            $professional->surname = $data['surname'];
            $professional->second_surname = $data['second_surname'];
            $professional->email = $data['email'];
            $professional->phone = $data['phone'];
            $professional->charge_id = $data['charge_id'];
            $professional->user_id = $data['user_id'];
            $professional->image_url = $filename;
            $professional->state = 0;
            $professional->save();

            return response()->json(['msg' => 'Profesional insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' =>  $th->getMessage().'Error al insertar el professional'], 500);
        }
    }

    public function update(Request $request)
    {
        try {

            Log::info("entra a actualizar");
           $professionals_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'surname' => 'required|max:50',
                'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email',
                'phone' => 'required|max:15',
                'charge_id' => 'required|numeric',
                'user_id' => 'required|numeric',
                'state' => 'required|numeric',
                'image_url' => 'nullable'
            ]);
            Log::info($request);
            $professional = Professional::find($professionals_data['id']);
            if($professional->image_url != $request['image_url'])
                {
                    $destination=public_path("storage\\".$professional->image_url);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }                    
                    $professional->image_url = $request->file('image_url')->storeAs('professionals',$professional->id.'.'.$request->file('image_url')->extension(),'public');
                }
            $professional->name = $professionals_data['name'];
            $professional->surname = $professionals_data['surname'];
            $professional->second_surname = $professionals_data['second_surname'];
            $professional->email = $professionals_data['email'];
            $professional->phone = $professionals_data['phone'];
            $professional->charge_id = $professionals_data['charge_id'];
            $professional->user_id = $professionals_data['user_id'];
            $professional->state = $professionals_data['state'];
            //$professional->image_url = $filename;
            $professional->save();

            return response()->json(['msg' => 'Profesional actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => $th->getMessage().'Error al actualizar el professional'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            
            $professionals_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $professional = Professional::find($professionals_data['id']);
            if ($professional->image_url != "image/default.png") {
                //$this->imageService->destroyImagen($professional->image_url);
                $destination=public_path("storage\\".$professional->image_url);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }
                }
            Professional::destroy($professionals_data['id']);
            return response()->json(['msg' => 'Profesional eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar la professional'], 500);
        }
    }
}