<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Car;
use App\Models\Product;
use App\Services\BranchService;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
            return response()->json(['msg' => "Error al mostrar las sucursales"], 500);
        }
    }
    public function show(Request $request)
    {
        try {
            $branch_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['branch' => Branch::with('professional')->find($branch_data['id'])], 200);
        } catch (\Throwable $th) {
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
                $result = $this->branchService->branch_winner_month($data['branch_id'], $request->mes);
            }
            if ($request->has('startDate') && $request->has('endDate')) {
                $result = $this->branchService->branch_winner_periodo($data['branch_id'], $request->startDate, $request->endDate);
            }
            else {
                $result = $this->branchService->branch_winner_date($data['branch_id']);
            }
          return response()->json($result, 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."La branch no obtuvo ganancias en este dia"], 500);
       }
    }

    public function company_winner(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
           ]);

           if ($request->has('mes')) {
            $result = $this->branchService->company_winner_month($request->mes);
            }
            if ($request->has('startDate') && $request->has('endDate')) {
                $result = $this->branchService->company_winner_periodo($request->startDate, $request->endDate);
            }            
            else {
                $result = $this->branchService->company_winner_date();
            }
          return response()->json($result, 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."La branch no obtuvo ganancias en este dia"], 500);
       }
    }

    public function branches_professional(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric'
            ]);
            return response()->json(['branches' => Branch::whereHas('professionals', function ($query) use ($data){
                $query->where('professional_id', $data['professional_id']);
            })->get()], 200);
        } catch (\Throwable $th) {
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
            ]);

            $branch = new Branch();
            $branch->name = $branch_data['name'];
            $branch->phone = $branch_data['phone'];
            $branch->address = $branch_data['address'];
            $branch->business_id = $branch_data['business_id'];
            $branch->business_type_id = $branch_data['business_type_id'];
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
            ]);

            $branch = Branch::find($branch_data['id']);
            $branch->name = $branch_data['name'];
            $branch->phone = $branch_data['phone'];
            $branch->address = $branch_data['address'];
            $branch->business_id = $branch_data['business_id'];
            $branch->business_type_id = $branch_data['business_type_id'];
            $branch->save();

            return response()->json(['msg' => 'Sucursal actualizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al actualizar la sucursal'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $branch_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Branch::destroy($branch_data['id']);

            return response()->json(['msg' => 'Sucursal eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar la sucursal'], 500);
        }
    }
}