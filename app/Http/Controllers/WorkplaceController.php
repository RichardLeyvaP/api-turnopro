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
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            Log::info("mostrar locales");
            return response()->json(['workplaces' => Workplace::with(['branch'])->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los Locales de Trabajo"], 500);
        }
    }



    public function show(Request $request)
    {
        try {
            $workplace_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['workplaces' => Workplace::with(['branch'])->find($workplace_data['id'])], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar el Local de Trabajo"], 500);
        }
    }

    public function branch_show(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            return response()->json(['workplaces' => Workplace::where('branch_id', $data['branch_id'])->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    public function branch_workplaces_busy(Request $request)
    {
        try {
            $workplace_data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            return response()->json(['workplaces' => Workplace::where('branch_id', $workplace_data['branch_id'])->where('busy', 0)->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar el Local de Trabajo"], 500);
        }
    }

    public function branch_workplaces_select(Request $request)
    {
        try {
            $workplace_data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            return response()->json(['workplaces' => Workplace::where('branch_id', $workplace_data['branch_id'])->where('select', 0)->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar el Local de Trabajo"], 500);
        }
    }


    public function store(Request $request)
    {
        Log::info("Guardar");
        Log::info($request);
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
            Log::error($th);
            return response()->json(['msg' => 'Error al insertar el Local de Trabajo'], 500);
        }
    }

    public function update(Request $request)
    {
        try {

            Log::info("Editar");
            Log::info($request);
            $workplace_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:100',
            ]);

            $workplace = Workplace::find($workplace_data['id']);
            $workplace->name = $workplace_data['name'];
            $workplace->save();

            return response()->json(['msg' => 'Local de Trabajo actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    public function update_state_prof(Request $request)
    {
        try {

            Log::info("Editar");
            Log::info($request);
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
            Log::info($th);
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    public function update_state_tec(Request $request)
    {
        try {

            Log::info("Editar");
            Log::info($request);
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
            Log::info($th);
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }



    public function destroy(Request $request)
    {
        try {
            $workplace_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Workplace::destroy($workplace_data['id']);

            return response()->json(['msg' => 'Local de Trabajo eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al eliminar el Local de Trabajo'], 500);
        }
    }

    public function resetWorkplaces()
    {
        try{
        Workplace::query()->update(['busy' => 0, 'select' => 0]);

        return response()->json(['msg' => 'Puestos de Trabajo actualizados correctamente'], 200);
    } catch (\Throwable $th) {
        Log::info($th);
        return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
    }
    }

}
