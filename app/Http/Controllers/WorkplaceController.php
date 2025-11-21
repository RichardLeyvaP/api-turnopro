<?php

namespace App\Http\Controllers;

use App\Models\ProfessionalWorkPlace;
use App\Models\Workplace;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\select;

class WorkplaceController extends Controller
{
    
    /**
 * Obtiene todos los puestos de trabajo con su sucursal asociada.
 *
 * @authenticated
 *
 * @response 200 {
 *   "workplaces": [
 *     {
 *       "id": 1,
 *       "name": "Puesto 1",
 *       "branch_id": 5,
 *       "busy": 0,
 *       "select": 0,
 *       "branch": { "name": "Centro", ... }
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los Locales de Trabajo"}
 */
    public function index()
    {
        try {
            return response()->json(['workplaces' => Workplace::with(['branch'])->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los Locales de Trabajo"], 500);
        }
    }

    /**
 * Obtiene los detalles de un puesto de trabajo específico.
 *
 * @authenticated
 * @queryParam id integer required ID del puesto. Example: 1
 *
 * @response 200 {
 *   "workplaces": {
 *     "id": 1,
 *     "name": "Puesto 1",
 *     "branch_id": 5,
 *     "busy": 0,
 *     "select": 0,
 *     "branch": { "name": "Centro" }
 *   }
 * }
 * @response 500 {"msg": "Error al mostrar el Local de Trabajo"}
 */
    public function show(Request $request)
    {
        try {
            $workplace_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['workplaces' => Workplace::with(['branch'])->find($workplace_data['id'])], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el Local de Trabajo"], 500);
        }
    }

    /**
 * Obtiene todos los puestos de trabajo de una sucursal específica.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "workplaces": [
 *     {
 *       "id": 1,
 *       "name": "Puesto 1",
 *       "branch_id": 5,
 *       "busy": 0,
 *       "select": 0
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function branch_show(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            return response()->json(['workplaces' => Workplace::where('branch_id', $data['branch_id'])->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }
    
    /**
 * Obtiene los puestos de trabajo **disponibles** (no ocupados) en una sucursal.
 *
 * `busy = 0` significa disponible para barberos.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "workplaces": [
 *     {
 *       "id": 1,
 *       "name": "Puesto 1",
 *       "busy": 0
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar el Local de Trabajo"}
 */
    public function branch_workplaces_busy(Request $request)
    {
        try {
            $workplace_data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);

            $workplaces = Workplace::where('branch_id', $workplace_data['branch_id'])->where('busy', 0)->get();
            return response()->json(['workplaces' => $workplaces], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el Local de Trabajo"], 500);
        }
    }

    /**
 * Obtiene los puestos de trabajo **disponibles para técnicos capilares** en una sucursal.
 *
 * `select = 0` significa disponible.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "workplaces": [
 *     {
 *       "id": 2,
 *       "name": "Puesto Técnico 1",
 *       "select": 0
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar el Local de Trabajo"}
 */
    public function branch_workplaces_select(Request $request)
    {
        try {
            $workplace_data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            return response()->json(['workplaces' => Workplace::where('branch_id', $workplace_data['branch_id'])->where('select', 0)->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el Local de Trabajo"], 500);
        }
    }

    /**
 * Crea un nuevo puesto de trabajo en una sucursal.
 *
 * @authenticated
 * @bodyParam name string required Nombre del puesto (máx. 100 caracteres). Example: Puesto 3
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {"msg": "Local de Trabajo insertado correctamente"}
 * @response 500 {"msg": "Error al insertar el Local de Trabajo"}
 */
    public function store(Request $request)
    {
        try {
            $workplace_data = $request->validate([
                'name' => 'required|max:100',
                'branch_id' => 'required|numeric',
            ]);

            $workplace = new Workplace();
            $workplace->name = $workplace_data['name'];
            $workplace->branch_id = $workplace_data['branch_id'];
            $workplace->save();

            return response()->json(['msg' => 'Local de Trabajo insertado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al insertar el Local de Trabajo'], 500);
        }
    }

    /**
 * Actualiza el nombre de un puesto de trabajo.
 *
 * @authenticated
 * @bodyParam id integer required ID del puesto. Example: 1
 * @bodyParam name string required Nuevo nombre. Example: Puesto Principal
 *
 * @response 200 {"msg": "Local de Trabajo actualizado correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function update(Request $request)
    {
        try {
            $workplace_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:100',
            ]);

            $workplace = Workplace::find($workplace_data['id']);
            $workplace->name = $workplace_data['name'];
            $workplace->save();

            return response()->json(['msg' => 'Local de Trabajo actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    /**
 * Libera un puesto de trabajo ocupado por un barbero (marca como disponible).
 *
 * - Pone `busy = 0` en el puesto.
 * - Actualiza el estado del registro en `ProfessionalWorkPlace`.
 *
 * @authenticated
 * @queryParam id integer required ID del puesto. Example: 1
 * @queryParam busy integer required Siempre `0`. Example: 0
 * @queryParam professional_id integer required ID del barbero. Example: 123
 *
 * @response 200 {"msg": "Puesto de Trabajo actualizado correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function update_state_prof(Request $request)
    {
        try {
            $workplace_data = $request->validate([
                'id' => 'required|numeric',
                'busy' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);

            $workplace = Workplace::find($workplace_data['id']);
            $professionalWorkplace = ProfessionalWorkPlace::where('professional_id', $workplace_data['professional_id'])->whereDate('data', Carbon::now())->where('state', 1)->orderByDesc('created_at')->first();
            $professionalWorkplace->state = 0;
            $professionalWorkplace->save();
            $workplace->busy = $workplace_data['busy'];
            $workplace->save();

            return response()->json(['msg' => 'Puesto de Trabajo actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    /**
 * Libera uno o varios puestos de trabajo ocupados por un técnico capilar.
 *
 * Usa el registro en `ProfessionalWorkPlace` para identificar los puestos asignados.
 *
 * @authenticated
 * @queryParam id integer required ID del puesto (no se usa directamente, pero está en la ruta). Example: 1
 * @queryParam select integer required Siempre `0` (liberar). Example: 0
 * @queryParam professional_id integer required ID del técnico. Example: 124
 *
 * @response 200 {"msg": "Puesto de Trabajo actualizado correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function update_state_tec(Request $request)
    {
        try {
            $workplace_data = $request->validate([
                'id' => 'required|numeric',
                'select' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $workplace = ProfessionalWorkPlace::where('professional_id', $workplace_data['professional_id'])->whereDate('data', Carbon::now())->where('state', 1)->orderByDesc('created_at')->first();

            $places = $workplace->places;
            if($places){
                $placesId = json_decode($places, true);
                Workplace::whereIn('id', $placesId)->update(['select' => $workplace_data['select']]);
            }
            $workplace->state = 0;
            $workplace->save();
            return response()->json(['msg' => 'Puesto de Trabajo actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    /**
 * Elimina un puesto de trabajo del sistema.
 *
 * @authenticated
 * @bodyParam id integer required ID del puesto. Example: 1
 *
 * @response 200 {"msg": "Local de Trabajo eliminado correctamente"}
 * @response 500 {"msg": "Error al eliminar el Local de Trabajo"}
 */
    public function destroy(Request $request)
    {
        try {
            $workplace_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Workplace::destroy($workplace_data['id']);

            return response()->json(['msg' => 'Local de Trabajo eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el Local de Trabajo'], 500);
        }
    }

    /**
 * Reinicia todos los puestos de trabajo (marca como disponibles).
 *
 * Endpoint protegido por código de acceso en la URL.
 *
 * @queryParam codigo string required Código de seguridad. Example: P{\nkNgP9hjm/L*~Sks25h^C30_|17
 *
 * @response 200 {"msg": "Puestos de Trabajo actualizados correctamente"}
 * @response 403 {"msg": "Código inválido"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function resetWorkplaces(Request $request)
    {
        $codigo = $request->query('codigo'); 

        

        if ($codigo != 'P{\nkNgP9hjm/L*~Sks25h^C30_|17') {
            return response()->json(['msg' => 'Código inválido'], 403);
        }
        try{
        Workplace::query()->update(['busy' => 0, 'select' => 0]);

        return response()->json(['msg' => 'Puestos de Trabajo actualizados correctamente'], 200);
    } catch (\Throwable $th) {
        return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
    }
    }

}
