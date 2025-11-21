<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchProfessional;
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
 * Lista todas las sucursales con sus profesionales asignados.
 *
 * Útil para visualizar la estructura general de asignación de personal.
 *
 * @authenticated
 *
 * @response 200 {
 *   "workplaces": [
 *     {
 *       "id": 3,
 *       "name": "Sucursal Centro",
 *       "professionals": [
 *         { "id": 10, "name": "Carlos Pérez", "state": 1 }
 *       ]
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los productos"}
 */
    public function index()
    {
        try {             
            return response()->json(['workplaces' => Branch::with('professionals')->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
        return response()->json(['msg' => "Error al mostrar los productos"], 500);
        }
    }

   /**
 * Asigna un profesional a un puesto de trabajo (workplace).
 *
 * Cambia el estado del profesional a `1` (activo) y marca el puesto como ocupado.
 * Si el profesional estaba en estado `2` (colación) o `0` (salida), actualiza su `end_time`.
 * Soporta asignación múltiple de puestos mediante el campo `places`.
 *
 * @authenticated
 * @bodyParam professional_id integer required ID del profesional. Example: 10
 * @bodyParam workplace_id integer required ID del puesto de trabajo principal. Example: 5
 * @bodyParam branch_id integer required ID de la sucursal. Example: 3
 * @bodyParam places array optional Lista de IDs adicionales de puestos (para técnicos). Example: [6, 7]
 *
 * @response 200 {"msg": "Puesto de trabajo seleccionado correctamente"}
 * @response 500 {"msg": "[error]Error al seleccionar el puesto de trabajo"}
 */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'workplace_id' => 'required|numeric',
                'places' => 'nullable',
                'branch_id' => 'required'
            ]);            
            $places = $data['places'];
            //return json_decode($places);
            $professional = Professional::find($data['professional_id']);
            if ($professional->state == 2) {
                $professional->end_time = Carbon::now();

                //actualizar lugar de llegada
                // Obtener el número máximo de llegada para la sucursal dada
                $maxArrival = BranchProfessional::where('branch_id', $data['branch_id'])->max('arrival');

                // Si no hay valores, inicializar a 0
                if (is_null($maxArrival)) {
                    $maxArrival = 0;
                }

                // Encontrar el registro específico y actualizar el campo arrival
                $branchProfessional = BranchProfessional::where('branch_id', $data['branch_id'])
                                                        ->where('professional_id', $data['professional_id'])
                                                        ->firstOrFail();

                // Asignar el siguiente número de llegada
                $branchProfessional->arrival = $maxArrival + 1;

                // Guardar los cambios
                $branchProfessional->save();

            }
             if ($professional->state == 0 && $professional->start_time != NULL) {
                $professional->end_time = Carbon::now();
            }
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
        return response()->json(['msg' =>$th->getMessage().'Error al seleccionar el puesto de trabajo'], 500);
        }
    }

   /**
 * Obtiene los puestos de trabajo asignados a un profesional (histórico del día).
 *
 * @authenticated
 * @queryParam professional_id integer required ID del profesional. Example: 10
 *
 * @response 200 {
 *   "professionals": [
 *     {
 *       "id": 5,
 *       "name": "Estación Técnico 1",
 *       "pivot": { "data": "2025-11-21", "places": "[6,7]", "state": 1 }
 *     }
 *   ]
 * }
 * @response 500 {"msg": "[error]Error al mostrar los clientes"}
 */
    public function show(Request $request)
    {
        try {             
            $data = $request->validate([
                'professional_id' => 'required|numeric'
            ]);
            $professional = Professional::find($data['professional_id']);
            return response()->json(['professionals' => $professional->workplaces],200, [], JSON_NUMERIC_CHECK); 
            
            } catch (\Throwable $th) {  
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los clientes"], 500);
        }
    }

    /**
 * Obtiene el ID del puesto de trabajo actual de un profesional.
 *
 * Diferencia el comportamiento según el cargo:  
 * - **Técnico**: busca puestos con `select = 1`  
 * - **Otros**: busca puestos con `busy = 1`
 *
 * @authenticated
 * @queryParam professional_id integer required ID del profesional. Example: 10
 * @queryParam charge string required Cargo del profesional. Example: "Tecnico"
 *
 * @response 200 5
 * @response 200 0
 * @response 500 {"msg": "[error]Error al mostrar los clientes"}
 */
    public function workplace_show_professional(Request $request)
    {
        try {             
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
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los clientes"], 500);
        }
    }

    /**
 * Obtiene el ID del puesto de trabajo actual de un profesional en una sucursal específica.
 *
 * Solo considera puestos con `busy = 1`.
 *
 * @authenticated
 * @queryParam professional_id integer required ID del profesional. Example: 10
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 *
 * @response 200 5
 * @response 200 0
 * @response 500 {"msg": "[error]Error al mostrar los clientes"}
 */
    public function workplace_show_professional2(Request $request)
    {
        try {             
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
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los clientes"], 500);
        }
    }

    /**
 * Obtiene detalles del puesto y hora de entrada de un profesional en su jornada actual.
 *
 * Retorna el ID, nombre del puesto y la hora de inicio de su turno.
 *
 * @authenticated
 * @queryParam professional_id integer required ID del profesional. Example: 10
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 *
 * @response 200 {
 *   "workplace_id": 5,
 *   "workplace_name": "Estación Barbero 3",
 *   "time": "09:15"
 * }
 * @response 500 {"msg": "[error]Error al mostrar los clientes"}
 */
    public function workplace_professional_day(Request $request)
    {
        try {             
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
                'time' => Carbon::createFromFormat('Y-m-d H:i', $record->start_time)->format('H:i')
            ];
            //return $workplace->workplace_id;
            //return response()->json(['professionals' => $professional->workplaces->get()],200, [], JSON_NUMERIC_CHECK); 
            
            } catch (\Throwable $th) {  
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los clientes"], 500);
        }
    }
    
    /**
 * Actualiza la asignación de puestos de trabajo para un profesional.
 *
 * Libera los puestos previamente asignados (`select = 0`) y asigna los nuevos (`select = 1`).
 *
 * @authenticated
 * @bodyParam professional_id integer required ID del profesional. Example: 10
 * @bodyParam workplace_id integer required ID del nuevo puesto principal. Example: 6
 * @bodyParam places array optional Nuevos puestos adicionales. Example: [7, 8]
 *
 * @response 200 {"msg": "Puesto de trabajo seleccionado correctamente"}
 * @response 500 {"msg": "[error]Error al seleccionar el puesto de trabajo"}
 */
    public function update(Request $request)
    {
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
        return response()->json(['msg' =>$th->getMessage().'Error al seleccionar el puesto de trabajo'], 500);
        }
    }

    /**
 * Libera el puesto de trabajo de un profesional.
 *
 * Cambia su estado a `0` (inactivo) y libera los puestos asignados (`select = 0`).
 *
 * @authenticated
 * @bodyParam professional_id integer required ID del profesional. Example: 10
 * @bodyParam workplace_id integer required ID del puesto a liberar. Example: 5
 *
 * @response 200 {"msg": "Puesto de trabajo liberado correctamente"}
 * @response 500 {"msg": "[error]Error al liberar el puesto de trabajo"}
 */
    public function destroy(Request $request)
    {
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
        return response()->json(['msg' =>$th->getMessage().'Error al liberar el puesto de trabajo'], 500);
        }
    }
}
