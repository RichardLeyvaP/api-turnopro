<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Car;
use App\Models\Comment;
use App\Models\Product;
use App\Services\BranchService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

//todo Richard comentario nuevo

class BranchController extends Controller
{

    private BranchService $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    public function index()
    {
        try {
            return response()->json(['branches' => Branch::with(['business', 'businessType'])->get()], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las sucursales"], 500);
        }
    }
    public function show(Request $request)
    {
        try {
            $branch_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['branch' => Branch::with('professional')->find($branch_data['id'])], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar la sucursal"], 500);
        }
    }

    public function show_Business(Request $request)
    {
        try {
            $branch_data = $request->validate([
                'business_id' => 'required|numeric'
            ]);
            Log::info($branch_data['business_id']);
            return response()->json(['branches' => Branch::where('business_id', $branch_data['business_id'])->select('id', 'name', 'image_data', 'address')->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar la sucursal"], 500);
        }
    }

    public function branch_winner(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);

            if ($request->has('mes')) {
                return response()->json($this->branchService->branch_winner_month($data['branch_id'], $request->mes, $request->year), 200, [], JSON_NUMERIC_CHECK);
            }
            if ($request->has('startDate') && $request->has('endDate')) {
                return response()->json($this->branchService->branch_winner_periodo($data['branch_id'], $request->startDate, $request->endDate), 200, [], JSON_NUMERIC_CHECK);
            } else {
                return response()->json($this->branchService->branch_winner_date($data['branch_id']), 200, [], JSON_NUMERIC_CHECK);
            }
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()], 500);
        }
    }

    public function branch_winner_icon(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);

            /*if ($request->has('mes')) {
                return response()->json($this->branchService->branch_winner_month_icon($data['branch_id'], $request->mes, $request->year), 200, [], JSON_NUMERIC_CHECK);
            }*/
            if ($request->has('startDate') && $request->has('endDate')) {
                return response()->json($this->branchService->branch_winner_periodo_icon($data['branch_id'], $request->startDate, $request->endDate), 200, [], JSON_NUMERIC_CHECK);
            } else {
                return response()->json($this->branchService->branch_winner_date_icon($data['branch_id']), 200, [], JSON_NUMERIC_CHECK);
            }
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()], 500);
        }
    }

    public function company_winner(Request $request)
    {
        try {
            $data = $request->validate([
                'business_id' => 'required|numeric'
            ]);
            /*if ($request->has('mes')) {
                return response()->json($this->branchService->company_winner_month($request->mes, $request->year, $data), 200, [], JSON_NUMERIC_CHECK);
            }*/
            if ($request->has('startDate') && $request->has('endDate')) {
                return response()->json($this->branchService->company_winner_periodo($request->startDate, $request->endDate, $data), 200, [], JSON_NUMERIC_CHECK);
            } else {
                return response()->json($this->branchService->company_winner_date($data), 200, [], JSON_NUMERIC_CHECK);
            }
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "La branch no obtuvo ganancias en este dia"], 500);
        }
    }

    public function company_close_cars(Request $request)
    {
        try {
            $data = $request->validate([
                'business_id' => 'required|numeric'
            ]);
            /*if ($request->has('mes')) {
                return response()->json($this->branchService->company_close_car_month($request->mes, $request->year, $data), 200, [], JSON_NUMERIC_CHECK);
            }*/
            if ($request->has('startDate') && $request->has('endDate')) {
                return response()->json($this->branchService->company_close_car_periodo($request->startDate, $request->endDate, $data), 200, [], JSON_NUMERIC_CHECK);
            } else {
                return response()->json($this->branchService->company_close_car_date($data), 200, [], JSON_NUMERIC_CHECK);
            }
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "La branch no obtuvo ganancias en este dia"], 500);
        }
    }

    public function branch_professionals_winner(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);

            /*if ($request->has('mes')) {
                return response()->json($this->branchService->branch_professionals_winner_month($data['branch_id'], $request->mes, $request->year), 200, [], JSON_NUMERIC_CHECK);
            }*/
            if ($request->has('startDate') && $request->has('endDate')) {
                return response()->json($this->branchService->branch_professionals_winner_periodo($request->startDate, $request->endDate, $data['branch_id']), 200, [], JSON_NUMERIC_CHECK);
            } else {
                return response()->json($this->branchService->branch_professionals_winner_date($data['branch_id']), 200, [], JSON_NUMERIC_CHECK);
            }
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "La branch no obtuvo ganancias en este dia"], 500);
        }
    }

    public function branches_professional(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric'
            ]);
            return response()->json(['branches' => Branch::whereHas('professionals', function ($query) use ($data) {
                $query->where('professional_id', $data['professional_id']);
            })->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las branch"], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info("Guardar");
        Log::info($request);
        try {
            $branch_data = $request->validate([
                'name' => 'required|max:50|unique:branches',
                'phone' => 'required',
                'address' => 'required|max:50',
                'business_id' => 'required|numeric',
                'business_type_id' => 'required|numeric',
                'useTechnical' => 'required|numeric',
                'location' => 'nullable'
            ]);

            $branch = new Branch();
            $branch->name = $branch_data['name'];
            $branch->phone = $branch_data['phone'];
            $branch->address = $branch_data['address'];
            $branch->business_id = $branch_data['business_id'];
            $branch->business_type_id = $branch_data['business_type_id'];
            $branch->useTechnical = $branch_data['useTechnical'];
            $branch->location = $branch_data['location'];
            $branch->save();
            $filename = "branches/default.jpg";
            if ($request->hasFile('image_data')) {
                $filename = $request->file('image_data')->storeAs('branches', $branch->id . '.' . $request->file('image_data')->extension(), 'public');
            }
            $branch->image_data = $filename;
            $branch->save();
            return response()->json(['msg' => 'Sucursal insertada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al insertar la sucursal'], 500);
        }
    }

    public function update(Request $request)
    {
        try {

            Log::info("Editar");
            Log::info($request);
            $branch_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'phone' => 'required',
                'address' => 'required|max:50',
                'business_id' => 'required|numeric',
                'business_type_id' => 'required|numeric',
                'useTechnical' => 'required|numeric',
                'location' => 'nullable'
            ]);
            Log::info($branch_data);
            $branch = Branch::find($branch_data['id']);
            if ($request->hasFile('image_data')) {
                if($branch->image_data != 'branches/default.jpg'){
                $destination = public_path("storage\\" . $branch->image_data);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
                }
                $branch->image_data = $request->file('image_data')->storeAs('branches', $branch->id . '.' . $request->file('image_data')->extension(), 'public');
            }
            $branch->name = $branch_data['name'];
            $branch->phone = $branch_data['phone'];
            $branch->address = $branch_data['address'];
            $branch->business_id = $branch_data['business_id'];
            $branch->business_type_id = $branch_data['business_type_id'];
            $branch->useTechnical = $branch_data['useTechnical'];
            $branch->location = $branch_data['location'];
            $branch->save();

            return response()->json(['msg' => 'Sucursal actualizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $branch_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $branch = Branch::find($branch_data['id']);
            if ($branch->image_data != "branches/default.jpg") {
                $destination = public_path("storage\\" . $branch->image_data);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
            }
            Branch::destroy($branch_data['id']);

            return response()->json(['msg' => 'Sucursal eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al eliminar la sucursal'], 500);
        }
    }

    
}
