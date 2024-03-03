<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Professional;
use App\Models\Record;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return response()->json(['records' => Record::with(['branch', 'professional'])->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el historial de records"], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info("Guardar");
        Log::info($request);
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);

            $record = new Record();
            $record->professional_id = $data['professional_id'];
            $record->branch_id = $data['branch_id'];
            $record->start_time = Carbon::now();
            $record->save();
            return response()->json(['msg' => 'Record creado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al crear un record'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            $branch_data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            return response()->json(['records' => Record::with('professional', 'branch')->where('branch_id', $branch_data['branch_id'])], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar la sucursal"], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        Log::info("Guardar");
        Log::info($request);
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);

            $record = Record::where('branch_id', $data['branch_id'])->where('professional_id', $data['professional_id'])->whereDate('start_time', Carbon::now())->first();
            $record->end_time = Carbon::now();
            $record->save();
            return response()->json(['msg' => 'Record creado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al crear un record'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $branch = Record::find($data['id']);

            Record::destroy($data['id']);

            return response()->json(['msg' => 'Record eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el record'], 500);
        }
    }

    public function arriving_late_branch_periodo(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
            ]);
            $llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            if($branchId)
            $llegadasTardias = Record::withCount('professional')->with('professional')->where('branch_id', $branchId->id)
                ->whereBetween('start_time', [$request->start_date, $request->end_date])
                ->get()
                ->filter(function ($registro) {
                    // Considera llegada tardía si es después de las 9:00 AM
                    return Carbon::parse($registro->start_time)->hour >= 9;
                })->groupBy('professional_id')->map(function ($group){
                    return [
                        'professional_id' => $group->first()->professional_id,
                        'name' => $group->first()->professional->name.' '.$group->first()->professional->surname.' '.$group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,
                        'cant' => $group->sum('professional_count')
                    ];
                })->sortByDesc('cant')->values();

            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error al eliminar el record'], 500);
        }
    }

    public function arriving_late_branch_date(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
            ]);
            $today = now()->endOfDay(); // Incluye toda la jornada del último día

            $llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            if($branchId)
            $llegadasTardias = Record::withCount('professional')->with('professional')->where('branch_id', $branchId->id)
                ->whereDate('start_time', $today)
                ->get()
                ->filter(function ($registro) {
                    // Considera llegada tardía si es después de las 9:00 AM
                    return Carbon::parse($registro->start_time)->hour >= 9;
                })->groupBy('professional_id')->map(function ($group){
                    return [
                        'professional_id' => $group->first()->professional_id,
                        'name' => $group->first()->professional->name.' '.$group->first()->professional->surname.' '.$group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,
                        'cant' => $group->sum('professional_count')
                    ];
                })->sortByDesc('cant')->values();

            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error al mostrar las llegadas tardes'], 500);
        }
    }

    public function arriving_late_branch_month(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
            ]);
            $llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            if($branchId)
            $llegadasTardias = Record::withCount('professional')->with('professional')->where('branch_id', $branchId->id)
                ->whereMonth('start_time', $request->mes)->whereYear('start_time', $request->year)
                ->get()
                ->filter(function ($registro) {
                    // Considera llegada tardía si es después de las 9:00 AM
                    return Carbon::parse($registro->start_time)->hour >= 9;
                })->groupBy('professional_id')->map(function ($group){
                    return [
                        'professional_id' => $group->first()->professional_id,
                        'name' => $group->first()->professional->name.' '.$group->first()->professional->surname.' '.$group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,
                        'cant' => $group->sum('professional_count')
                    ];
                })->sortByDesc('cant')->values();

            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error al eliminar el record'], 500);
        }
    }

    public function arriving_late_professional_date(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $cant = 0;             
            $llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            $professionalId = Professional::find($data['professional_id']);
            $today = now()->endOfDay(); // Incluye toda la jornada del último día
            if ($branchId && $professionalId) {
            $llegadasTardias = Record::with('professional')->where('branch_id', $branchId->id)->where('professional_id', $professionalId->id)
                ->whereDate('start_time', $today)
                ->get()
                ->filter(function ($registro) {
                    // Considera llegada tardía si es después de las 9:00 AM
                    return Carbon::parse($registro->start_time)->hour >= 9;
                })->map(function ($group){   
                    return [
                        /*'professional_id' => $group->first()->professional_id,
                        /*'name' => $group->first()->professional->name.' '.$group->first()->professional->surname.' '.$group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,*/
                        'start_time' =>$group->start_time,
                        'end_time' => $group->end_time
                    ];
                })->values();
                                 
                $cant = $llegadasTardias->count();
                $total = [
                    /*'professional_id' => 0,
                        'name' => 'Total',
                        'image_url' => '',
                        'charge' => '',*/
                        'start_time' =>'Total',
                        'end_time' => $cant
                ];
                $llegadasTardias[] = $total;                
            }
            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error al mostrar las llegadas tardes'], 500);
        }
    }

    public function arriving_late_professional_periodo(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $cant = 0; 
            $llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            $professionalId = Professional::find($data['professional_id']);
            if ($branchId && $professionalId) {
            $llegadasTardias = Record::with('professional')->where('branch_id', $branchId->id)->where('professional_id', $professionalId->id)
                ->whereBetween('start_time', [$request->start_date, $request->end_date])
                ->get()
                ->filter(function ($registro) {
                    // Considera llegada tardía si es después de las 9:00 AM
                    return Carbon::parse($registro->start_time)->hour >= 9;
                })->map(function ($group) use ($cant){   
                    return [
                        /*'professional_id' => $group->first()->professional_id,
                        /*'name' => $group->first()->professional->name.' '.$group->first()->professional->surname.' '.$group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,*/
                        'start_time' =>$group->start_time,
                        'end_time' => $group->end_time
                    ];
                })->values();
                                 
                $cant = $llegadasTardias->count();
                $total = [
                    /*'professional_id' => 0,
                        'name' => 'Total',
                        'image_url' => '',
                        'charge' => '',*/
                        'start_time' =>'Total',
                        'end_time' => $cant
                ];
                $llegadasTardias[] = $total;
            }


            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error al mostrar las llegadas tardes'], 500);
        }
    }
    public function arriving_late_professional_month(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $cant = 0; 
            $llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            $professionalId = Professional::find($data['professional_id']);
            $today = now()->endOfDay(); // Incluye toda la jornada del último día
            if ($branchId && $professionalId) {
            $llegadasTardias = Record::with('professional')->where('branch_id', $branchId->id)->where('professional_id', $professionalId->id)
                ->whereMonth('start_time', $request->mes)->whereYear('start_time', $request->year)
                ->get()
                ->filter(function ($registro) {
                    // Considera llegada tardía si es después de las 9:00 AM
                    return Carbon::parse($registro->start_time)->hour >= 9;
                })->map(function ($group) use ($cant){   
                    return [
                        /*'professional_id' => $group->first()->professional_id,
                        /*'name' => $group->first()->professional->name.' '.$group->first()->professional->surname.' '.$group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,*/
                        'start_time' =>$group->start_time,
                        'end_time' => $group->end_time
                    ];
                })->values();
                                 
                $cant = $llegadasTardias->count();
                $total = [
                    /*'professional_id' => 0,
                        'name' => 'Total',
                        'image_url' => '',
                        'charge' => '',*/
                        'start_time' =>'Total',
                        'end_time' => $cant
                ];
                $llegadasTardias[] = $total;
            }


            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error al mostrar las llegadas tardes'], 500);
        }
    }

    public function arriving_branch_periodo(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
            ]);
            $llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            if($branchId)
            $llegadasTardias = Record::withCount('professional')->with('professional')->where('branch_id', $branchId->id)
                ->whereBetween('start_time', [$request->start_date, $request->end_date])
                ->get()
                ->filter(function ($registro) {
                    // Considera llegada tardía si es después de las 9:00 AM
                    return Carbon::parse($registro->start_time)->hour < 9;
                })->groupBy('professional_id')->groupBy('professional_id')->map(function ($group){
                    return [
                        'professional_id' => $group->first()->professional_id,
                        'name' => $group->first()->professional->name.' '.$group->first()->professional->surname.' '.$group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,
                        'cant' => $group->sum('professional_count')
                    ];
                })->sortByDesc('cant')->values();

            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error'], 500);
        }
    }

    public function arriving_branch_date(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
            ]);
            $today = now()->endOfDay(); // Incluye toda la jornada del último día

            $llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            if($branchId)
            $llegadasTardias = Record::withCount('professional')->with('professional')->where('branch_id', $branchId->id)
                ->whereDate('start_time', $today)
                ->get()
                ->filter(function ($registro) {
                    // Considera llegada tardía si es después de las 9:00 AM
                    return Carbon::parse($registro->start_time)->hour <  9;
                })->groupBy('professional_id')->map(function ($group){
                    return [
                        'professional_id' => $group->first()->professional_id,
                        'name' => $group->first()->professional->name.' '.$group->first()->professional->surname.' '.$group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,
                        'cant' => $group->sum('professional_count')
                    ];
                })->sortByDesc('cant')->values();

            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error'], 500);
        }
    }

    public function arriving_branch_month(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
            ]);
            $llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            if($branchId)
            $llegadasTardias = Record::withCount('professional')->where('branch_id', $branchId->id)
                ->whereMonth('start_time', $request->mes)->whereYear('start_time', $request->year)                
                ->get()
                ->filter(function ($registro) {
                    // Considera llegada tardía si es después de las 9:00 AM
                    return Carbon::parse($registro->start_time)->hour < 9;
                })->groupBy('professional_id')->map(function ($group){
                    return [
                        'professional_id' => $group->first()->professional_id,
                        'name' => $group->first()->professional->name.' '.$group->first()->professional->surname.' '.$group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,
                        'cant' => $group->sum('professional_count')
                    ];
                })->sortByDesc('cant')->values();

            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error'], 500);
        }
    }
    
}
