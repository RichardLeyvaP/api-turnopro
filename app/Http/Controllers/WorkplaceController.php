<?php

namespace App\Http\Controllers;

use App\Models\Workplace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WorkplaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            Log::info("mostrar locales");
            return response()->json(['workplaces' => Workplace::with(['branch'])->get()], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los Locales de Trabajo"], 500);
        }
    }



    public function show(Request $request)
    {
        try {
            $workplace_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['workplaces' => Workplace::with(['branch'])->find($workplace_data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el Local de Trabajo"], 500);
        }
    }

    public function branch_workplaces_busy(Request $request)
    {
        try {
            $workplace_data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            return response()->json(['workplaces' => Workplace::where('branch_id', $workplace_data['branch_id'])->where('busy', 0)->get()], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el Local de Trabajo"], 500);
        }
    }

    public function branch_workplaces_select(Request $request)
    {
        try {
            $workplace_data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            return response()->json(['workplaces' => Workplace::where('branch_id', $workplace_data['branch_id'])->where('select', 0)->get()], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el Local de Trabajo"], 500);
        }
    }


    public function store(Request $request)
    {
        Log::info("Guardar");
        Log::info($request);
        try {
            $workplace_data = $request->validate([
                'name' => 'required|max:100|unique:workplaces',
                'busy' => 'required',
                'branch_id' => 'required|numeric',
            ]);

            $workplace = new Workplace();
            $workplace->name = $workplace_data['name'];
            $workplace->busy = $workplace_data['busy'];
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
                'busy' => 'required',
                'branch_id' => 'required|numeric',
            ]);

            $workplace = Workplace::find($workplace_data['id']);
            $workplace->name = $workplace_data['name'];
            $workplace->busy = $workplace_data['busy'];
            $workplace->branch_id = $workplace_data['branch_id'];
            $workplace->save();

            return response()->json(['msg' => 'Local de Trabajo actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al actualizar el Local de Trabajo'], 500);
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
            return response()->json(['msg' => 'Error al eliminar el Local de Trabajo'], 500);
        }
    }



}
