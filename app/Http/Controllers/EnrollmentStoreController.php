<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\EnrollmentStore;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnrollmentStoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
 * Asigna un almacén a una academia.
 *
 * Crea una relación muchos a muchos entre una academia (`Enrollment`) y un almacén (`Store`).
 *
 * @authenticated
 * @bodyParam enrollment_id integer required ID de la academia. Example: 4
 * @bodyParam store_id integer required ID del almacén. Example: 7
 *
 * @response 200 {"msg": "Almacén asignado correctamente a la academia"}
 * @response 500 {"msg": "Error al asignar el producto a este almacén"}
 */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'enrollment_id' => 'required|numeric',
                'store_id' => 'required|numeric'
            ]);
            $enrollment = Enrollment::find($data['enrollment_id']);
            $store = Store::find($data['store_id']);

            $enrollment->stores()->attach($store->id);

            return response()->json(['msg' => 'Almacén asignado correctamente a la academia'], 200);
        } catch (\Throwable $th) {
        return response()->json(['msg' =>'Error al asignar el producto a este almacén'], 500);
    }
    }

    /**
 * Lista los almacenes asignados a una academia.
 *
 * Retorna información relevante de cada almacén: dirección, descripción, referencia e ID.
 *
 * @authenticated
 * @queryParam enrollment_id integer required ID de la academia. Example: 4
 *
 * @response 200 {
 *   "enrollmentStores": [
 *     {
 *       "id": 12,
 *       "store_id": 7,
 *       "address": "Av. Siempre Viva 123",
 *       "description": "Almacén principal",
 *       "reference": "Frente al parque"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al actualizar el almacén en esta sucursal"}
 */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'enrollment_id' => 'required|numeric'
            ]);
            $enrollmentStores = EnrollmentStore::where('enrollment_id', $data['enrollment_id'])->get()->map(function ($enrollmentStore) {
                return [
                    'id' => $enrollmentStore->id,
                    'address' => $enrollmentStore->store->address,
                    'description' => $enrollmentStore->store->description,
                    'reference' => $enrollmentStore->store->reference,
                    'store_id' => $enrollmentStore->store_id
                ];
            });
            return response()->json(['enrollmentStores' => $enrollmentStores], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar el almacén en esta sucursal'], 500);
        }
    }

    /**
 * Obtiene los almacenes **no asignados** a una academia.
 *
 * Útil para interfaces de gestión donde se muestran opciones disponibles para asignar.
 *
 * @authenticated
 * @queryParam enrollment_id integer required ID de la academia. Example: 4
 *
 * @response 200 {
 *   "stores": [
 *     {
 *       "id": 8,
 *       "name": "Almacén Secundario",
 *       "address": "Calle Falsa 456",
 *       "description": "Almacén de respaldo",
 *       "reference": "Junto a la farmacia"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "[mensaje de error]Error al mostrar los productos"}
 */
    public function show_notIn(Request $request)
    {
        try {             
            $data = $request->validate([
                'enrollment_id' => 'numeric'
            ]);
            $storeIds = Enrollment::find($data['enrollment_id'])->stores()->pluck('store_id');

            // Obtener los associates que NO están en esa lista de IDs asociados
            $storeNotInEnrollment = Store::whereNotIn('id', $storeIds)->get();

                return response()->json(['stores' => $storeNotInEnrollment],200); 
            
            } catch (\Throwable $th) { 
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los productos"], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EnrollmentStore $enrollmentStore)
    {
        //
    }

    /**
 * Elimina la asignación de un almacén a una academia.
 *
 * Rompe la relación en la tabla pivote `enrollment_store`.
 *
 * @authenticated
 * @bodyParam enrollment_id integer required ID de la academia. Example: 4
 * @bodyParam store_id integer required ID del almacén. Example: 7
 *
 * @response 200 {"msg": "Almacén eliminado correctamente"}
 * @response 500 {"msg": "[mensaje de error]Error al eliminar el almacén en esta academia"}
 */
    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'store_id' => 'required|numeric',
                'enrollment_id' => 'required|numeric'
            ]);
            $store = Store::find($data['store_id']);
            $enrollment = Enrollment::find($data['enrollment_id']);
            $enrollment->stores()->detach($store->id);
            return response()->json(['msg' => 'Almacén eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error al eliminar el almacén en esta academia'], 500);
        }
    }
}
