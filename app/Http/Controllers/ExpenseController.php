<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExpenseController extends Controller
{
    /**
 * Obtiene la lista de todas las operaciones de gasto disponibles.
 *
 * @authenticated
 *
 * @response 200 {
 *   "expenses": [
 *     {
 *       "id": 1,
 *       "name": "Pago de bonos"
 *     },
 *     {
 *       "id": 2,
 *       "name": "Compra de productos"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function index()
    {
        try {
            return response()->json(['expenses' => Expense::all()], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    /**
 * Crea una nueva operación de gasto.
 *
 * @authenticated
 * @bodyParam name string required Nombre de la operación de gasto. Example: Compra de insumos
 *
 * @response 200 {"msg": "Operación de Gasto creado correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required',

            ]);

            $expense = new Expense();
            $expense->name = $data['name'];
            $expense->save();

            return response()->json(['msg' => 'Operación de Gasto creado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    /**
 * Obtiene los detalles de una operación de gasto específica.
 *
 * @authenticated
 * @queryParam id integer required ID de la operación de gasto. Example: 1
 *
 * @response 200 {
 *   "businessTypes": {
 *     "id": 1,
 *     "name": "Pago de bonos"
 *   }
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['businessTypes' => Expense::find($data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    /**
 * Actualiza una operación de gasto existente.
 *
 * @authenticated
 * @bodyParam id integer required ID de la operación de gasto. Example: 1
 * @bodyParam name string required Nuevo nombre. Example: Bonos mensuales
 *
 * @response 200 {"msg": "Operación de Gasto actualizado correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function update(Request $request)
    {
        try {

            $data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required'
            ]);
            $expense = Expense::find($data['id']);
            $expense->name = $data['name'];
            $expense->save();

            return response()->json(['msg' => 'Operación de Gasto actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    /**
 * Elimina una operación de gasto del sistema.
 *
 * @authenticated
 * @bodyParam id integer required ID de la operación de gasto. Example: 1
 *
 * @response 200 {"msg": "Operación de Gasto eliminado correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function destroy(Request $request)
    {
        try {

            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Expense::destroy($data['id']);

            return response()->json(['msg' => 'Operación de Gasto eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }
}
