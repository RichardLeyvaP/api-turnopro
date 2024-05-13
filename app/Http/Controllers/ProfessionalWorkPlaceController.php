<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Professional;
use App\Models\ProfessionalWorkPlace;
use App\Models\Record;
use App\Models\Workplace;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfessionalWorkPlaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {             
            Log::info( "Entra a buscar los puestos de trabajos por branches");
            return response()->json(['workplaces' => Branch::with('professionals')->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => "Error al mostrar los productos"], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info("Asignar Puesto de trabajo");
        Log::info($request);
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'workplace_id' => 'required|numeric',
                'places' => 'nullable'
            ]);            
            $places = $data['places'];
            //return json_decode($places);
            $professional = Professional::find($data['professional_id']);
            $professional->state = 1;
            $professional->save();
            $workplace = Workplace::find($data['workplace_id']);
            $professional->workplaces()->attach($workplace->id, ['data'=>Carbon::now(), 'places'=>json_encode($places), 'state' => 1]);
            if(!$places){
            $workplace->busy = 1;
            $workplace->save();
            }
            else{
            Workplace::whereIn('id', $places)->update(['select'=> 1]);
            }
            return response()->json(['msg' => 'Puesto de trabajo seleccionado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' =>$th->getMessage().'Error al seleccionar el puesto de trabajo'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {             
            Log::info( "Entra a buscar los puestos de trabajo de un professionals");
            $data = $request->validate([
                'professional_id' => 'required|numeric'
            ]);
            $professional = Professional::find($data['professional_id']);
            return response()->json(['professionals' => $professional->workplaces],200, [], JSON_NUMERIC_CHECK); 
            
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los clientes"], 500);
        }
    }

    public function workplace_show_professional(Request $request)
    {
        try {             
            Log::info( "Entra a buscar el puestos de trabajo de un professionals");
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'charge' => 'required'
            ]);
            if($data['charge'] === 'Tecnico'){
                $workplace = ProfessionalWorkPlace::where('professional_id', $data['professional_id'])->whereDate('data', Carbon::now())->whereHas('workplace', function ($query){
                    $query->where('select', 1);
                })->where('state', 1)->orderByDesc('created_at')->first();
            }
            else{
                $workplace = ProfessionalWorkPlace::where('professional_id', $data['professional_id'])->whereDate('data', Carbon::now())->whereHas('workplace', function ($query){
                    $query->where('busy', 1);
                })->where('state', 1)->orderByDesc('created_at')->first();
            }
            //$professional = Professional::find($data['professional_id']);
            
            if(!$workplace){
                return 0;
            }
            return $workplace->workplace_id;
            //return response()->json(['professionals' => $professional->workplaces->get()],200, [], JSON_NUMERIC_CHECK); 
            
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los clientes"], 500);
        }
    }
    public function workplace_show_professional2(Request $request)
    {
        try {             
            Log::info( "Entra a buscar el puestos de trabajo de un professionals");
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $professional = Professional::find($data['professional_id']);
            $workplace = ProfessionalWorkPlace::where('professional_id', $professional->id)->whereDate('data', Carbon::now())->whereHas('workplace', function ($query) use ($data){
                $query->where('busy', 1)->where('branch_id', $data['branch_id']);
            })->first();
            if(!$workplace){
                return 0;
            }
            return $workplace->workplace_id;
            //return response()->json(['professionals' => $professional->workplaces->get()],200, [], JSON_NUMERIC_CHECK); 
            
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los clientes"], 500);
        }
    }

    public function workplace_professional_day(Request $request)
    {
        try {             
            Log::info( "Entra a buscar el puesto de trabajo de un professionals");
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $professional = Professional::find($data['professional_id']);
            $workplace = ProfessionalWorkPlace::where('professional_id', $professional->id)->whereDate('data', Carbon::now())->whereHas('workplace', function ($query) use ($data){
                $query->where('busy', 1)->where('branch_id', $data['branch_id']);
            })->first();
            $record = Record::where('professional_id', $professional->id)->whereDate('start_time', Carbon::now())->where('branch_id', $data['branch_id'])->first();
            
            return $entrada = [
                'workplace_id' => $workplace->workplace_id,
                'workplace_name' => $workplace->workplace->name,
                'time' => Carbon::createFromFormat('Y-m-d H:i:s', $record->start_time)->format('H:i:s')
            ];
            //return $workplace->workplace_id;
            //return response()->json(['professionals' => $professional->workplaces->get()],200, [], JSON_NUMERIC_CHECK); 
            
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los clientes"], 500);
        }
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        Log::info("Actualizar Productos a un almacen");
        Log::info($request);
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'workplace_id' => 'required|numeric',
                'places' => 'nullable'
            ]);            
            $places = $data['places'];

            $professional = Professional::find($data['professional_id']);
            $workplace = Workplace::find($data['workplace_id']);
            $professionalworkplace = ProfessionalWorkPlace::where('professional_id', $data['professional_id'])->where('workplace_id', $data['workplace_id'])->whereDate('data', Carbon::now())->selectRaw('*, CAST(places AS CHAR) AS places_decodificado')->first();
            Workplace::whereIn('id', json_decode($professionalworkplace->places_decodificado, true))->update(['select'=> 0]);
            $professional->workplaces()->wherePivot('data', Carbon::now()->format('Y-m-d'))->updateExistingPivot($workplace->id,['data'=>Carbon::now(), 'places'=>json_encode($places)]);
            if($places)
            Workplace::whereIn('id', $places)->update(['select'=> 1]);
            return response()->json(['msg' => 'Puesto de trabajo seleccionado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' =>$th->getMessage().'Error al seleccionar el puesto de trabajo'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        Log::info("Actualizar Productos a un almacen");
        Log::info($request);
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'workplace_id' => 'required|numeric'
            ]);            
            $professional = Professional::find($data['professional_id']);
            $professional->state = 0;
            $professional->save();
            $workplace = Workplace::find($data['workplace_id']);
            //return $professional->workplaces()->wherePivot('data', Carbon::now()->format('Y-m-d'))->withPivot('id', 'places')->get()->map->pivot;
            $professionalworkplace = ProfessionalWorkPlace::where('professional_id', $data['professional_id'])->where('workplace_id', $data['workplace_id'])->whereDate('data', Carbon::now())->selectRaw('*, CAST(places AS CHAR) AS places_decodificado')->first();
            Workplace::whereIn('id', json_decode($professionalworkplace->places_decodificado, true))->update(['select'=> 0]);

            $professional->workplaces()->wherePivot('data', Carbon::now()->format('Y-m-d'))->detach($workplace->id);
            return response()->json(['msg' => 'Puesto de trabajo liberado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' =>$th->getMessage().'Error al liberar el puesto de trabajo'], 500);
        }
    }
}
