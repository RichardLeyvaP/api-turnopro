<?php
namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StoreController extends Controller
{

    /**
 * Lista todos los almacenes del sistema.
 *
 * @authenticated
 *
 * @response 200 {
 *   "stores": [
 *     {
 *       "id": 5,
 *       "reference": "ALM-001",
 *       "description": "Almacén principal",
 *       "address": "Av. Siempre Viva 123"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los almacenes"}
 */
    public function index()
    {
        try { 
            
            return response()->json(['stores' => Store::all()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los almacenes"], 500);
        }
    }

    /**
 * Lista todos los almacenes del sistema (alias de `index`).
 *
 * @authenticated
 *
 * @response 200 { "stores": [...] }
 * @response 500 {"msg": "Error al mostrar el almacén"}
 */
    public function show(Request $request)
    {
        try {
            return response()->json(['stores' => Store::all()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el almacén"], 500);
        }
    }

    /**
 * Obtiene todos los almacenes excepto uno específico.
 *
 * Útil para interfaces de selección donde se debe excluir el almacén actual.
 *
 * @authenticated
 * @queryParam store_id integer required ID del almacén a excluir. Example: 5
 *
 * @response 200 {
 *   "stores": [
 *     { "id": 6, "reference": "ALM-002", ... }
 *   ]
 * }
 * @response 500 {"msg": "[error]Error al mostrar el almacén"}
 */
    public function show_NotIn(Request $request)
    {
        try {
            $data = $request->validate([
                'store_id' => 'required|numeric'
            ]);
            return response()->json(['stores' => Store::where('id', '!=',$data['store_id'])->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()."Error al mostrar el almacén"], 500);
        }
    }

    /**
 * Lista almacenes asociados a una sucursal.
 *
 * Si el usuario es **Administrador**, devuelve todos los almacenes.
 * De lo contrario, solo devuelve los asociados a la sucursal especificada.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 *
 * @response 200 { "stores": [...] }
 * @response 500 {"msg": "Error al mostrar el almacén"}
 */
    public function show_branch(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            if (auth()->user()->professional->charge->name == 'Administrador'){
                $stores = Store::all();
            }else {
                $stores = Store::whereHas('branches', function ($query) use ($data){
                    $query->where('branch_id', $data['branch_id']);
                })->get();
            }
            return response()->json(['stores' => $stores], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el almacén"], 500);
        }
    }

    /**
 * Lista almacenes asociados a una academia.
 *
 * @authenticated
 * @queryParam enrollment_id integer required ID de la academia. Example: 4
 *
 * @response 200 {
 *   "stores": [
 *     { "id": 5, "reference": "ALM-ACAD", ... }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar el almacén"}
 */
    public function store_academy_show(Request $request)
    {
        try {
            $data = $request->validate([
                'enrollment_id' => 'required|numeric'
            ]);
            return response()->json(['stores' => Store::whereHas('enrollments', function ($query) use ($data){
                $query->where('enrollment_id', $data['enrollment_id']);
            })->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el almacén"], 500);
        }
    }

    /**
 * Crea un nuevo almacén.
 *
 * @authenticated
 * @bodyParam reference string required Referencia interna. Max: 50 caracteres. Example: "ALM-001"
 * @bodyParam description string required Descripción. Max: 50 caracteres. Example: "Almacén principal"
 * @bodyParam address string required Dirección física. Max: 50 caracteres. Example: "Av. Siempre Viva 123"
 *
 * @response 200 {"msg": "Almacén insertado correctamente"}
 * @response 500 {"msg": "Error al insertar el almacén"}
 */
    public function store(Request $request)
    {
        try {
            $stores_data = $request->validate([
                'reference' => 'required|max:50',
                'description' => 'required|max:50',
                'address' => 'required|max:50'
              
            ]);

            $store = new Store();
            $store->reference = $stores_data['reference'];
            $store->description = $stores_data['description'];
            $store->address = $stores_data['address'];
       
            $store->save();

            return response()->json(['msg' => 'Almacén insertado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al insertar el almacén'], 500);
        }
    }

    /**
 * Actualiza un almacén existente.
 *
 * @authenticated
 * @bodyParam id integer required ID del almacén. Example: 5
 * @bodyParam reference string required Nueva referencia. Max: 50. Example: "ALM-PRINCIPAL"
 * @bodyParam description string required Nueva descripción. Max: 50. Example: "Almacén central"
 * @bodyParam address string required Nueva dirección. Max: 50. Example: "Calle Falsa 456"
 *
 * @response 200 {"msg": "Almacén actualizado correctamente"}
 * @response 500 {"msg": "Error al actualizar el almacén"}
 */
    public function update(Request $request)
    {
        try {  
            $stores_data = $request->validate([
                'id' => 'required|numeric',
                'reference' => 'required|max:50',
                'description' => 'required|max:50',
                'address' => 'required|max:50'
              
            ]);
            $store = Store::find($stores_data['id']);
            $store->reference = $stores_data['reference'];
            $store->description = $stores_data['description'];
            $store->address = $stores_data['address'];
            $store->save();

            return response()->json(['msg' => 'Almacén actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar el almacén'], 500);
        }
    }

    /**
 * Elimina un almacén del sistema.
 *
 * ⚠️ **Advertencia**: Esta acción es irreversible y puede afectar productos y movimientos asociados.
 *
 * @authenticated
 * @bodyParam id integer required ID del almacén a eliminar. Example: 5
 *
 * @response 200 {"msg": "Almacén eliminado correctamente"}
 * @response 500 {"msg": "Error al eliminar el almacén"}
 */
    public function destroy(Request $request)
    {
        try {
            
            $stores_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Store::destroy($stores_data['id']);

            return response()->json(['msg' => 'Almacén eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el almacén'], 500);
        }
    }
}