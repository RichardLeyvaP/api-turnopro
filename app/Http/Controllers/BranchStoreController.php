<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BranchStoreController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Entra a buscar los almacenes por sucursales");
            return response()->json(['branch' => Branch::with('branchstores')->get()], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => "Error al mostrar los almacenes por sucursales"], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info("Asignar almacén a una sucursal");
        Log::info($request);
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'store_id' => 'required|numeric'
            ]);
            $branch = Branch::find($data['branch_id']);
            $store = Store::find($data['store_id']);

            $branch->branchstores()->attach($store->id);

            return response()->json(['msg' => 'Almacén asignado correctamente a la sucursal'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' =>'Error al asignar el producto a este almacén'], 500);
        }
    }

    public function show(Request $request)
    {
        try {             
            Log::info( "Entra a buscar los almacenes de una sucursal o la sucursal de un almacén");
            $data = $request->validate([
                'branch_id' => 'numeric'
            ]);
            $branch = Branch::with('stores')->find($data['branch_id']);

                return response()->json(['stores' => $branch->stores],200); 
            
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los productos"], 500);
        }
    }

    public function show_notIn(Request $request)
    {
        try {             
            Log::info( "Entra a buscar los almacenes de una sucursal");
            $data = $request->validate([
                'branch_id' => 'numeric'
            ]);
            $storeIds = Branch::find($data['branch_id'])->stores()->pluck('store_id');

            // Obtener los associates que NO están en esa lista de IDs asociados
            $storeNotInBranch = Store::whereNotIn('id', $storeIds)->get();

                return response()->json(['stores' => $storeNotInBranch],200); 
            
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los productos"], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'store_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $store = Store::find($data['store_id']);
            $branch = Branch::find($data['branch_id']);
            $branch->branchstores()->sync($store->id);
            return response()->json(['msg' => 'Almacén actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al actualizar el almacén en esta sucursal'], 500);
        }
        
    }

    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'store_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $store = Store::find($data['store_id']);
            $branch = Branch::find($data['branch_id']);
            $branch->branchstores()->detach($store->id);
            return response()->json(['msg' => 'Almacén eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al eliminar el almacén en esta sucursal'], 500);
        }
    }
}
