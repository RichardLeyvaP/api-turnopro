<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentMethodController extends Controller
{
    
    /**
 * Lista todos los métodos de pago disponibles.
 *
 * Retorna una colección de métodos de pago con `id`, `name` y `type`.
 *
 * @authenticated
 *
 * @response 200 {
 *   "paymentOptions": [
 *     {
 *       "id": 1,
 *       "name": "Tarjeta de Crédito",
 *       "type": "digital"
 *     },
 *     {
 *       "id": 2,
 *       "name": "Efectivo",
 *       "type": "physical"
 *     }
 *   ]
 * }
 */
    public function index()
    {
        $paymentMethods = PaymentMethod::all()->map(function ($method) {
            return [
                'id' => $method->id,
                'name' => $method->name,
                'type' => $method->type
            ];
        });

        return response()->json([
            'paymentOptions' => $paymentMethods
        ]);
    }

    /**
 * Crea un nuevo método de pago.
 *
 * No permite asignación masiva; los campos se asignan explícitamente.
 *
 * @authenticated
 * @bodyParam name string required Nombre del método de pago. Max: 255 caracteres. Example: "Transferencia Bancaria"
 * @bodyParam type string required Tipo del método (ej. "digital", "physical", "online"). Max: 255. Example: "bank"
 * @bodyParam description string optional Descripción adicional. Example: "Transferencia desde cualquier banco"
 *
 * @response 201 {
 *   "success": true,
 *   "paymentOption": {
 *     "id": 3,
 *     "name": "Transferencia Bancaria",
 *     "type": "bank"
 *   }
 * }
 */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Create without mass assignment
        $paymentMethod = new PaymentMethod();
        $paymentMethod->name = $validated['name'];
        $paymentMethod->type = $validated['type'];
        $paymentMethod->description = $validated['description'] ?? null;
        $paymentMethod->save();

        return response()->json([
            'success' => true,
            'paymentOption' => [
                'id' => $paymentMethod->id,
                'name' => $paymentMethod->name,
                'type' => $paymentMethod->type
            ]
        ], 201);
    }

    /**
 * Actualiza un método de pago existente.
 *
 * Requiere el `id` del método a modificar.
 *
 * @authenticated
 * @bodyParam id integer required ID del método de pago. Example: 3
 * @bodyParam name string required Nuevo nombre. Max: 50 caracteres. Example: "Transferencia SPEI"
 * @bodyParam type string required Nuevo tipo. Max: 255. Example: "bank"
 * @bodyParam description string optional Nueva descripción. Example: "Sistema de pagos electrónicos interbancarios"
 *
 * @response 200 {"msg": "Metodo de ingreso actualizado correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function update(Request $request)
    {
        try {
             $data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'type' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);
            $method = PaymentMethod::find( $data['id']);
            $method->name = $data['name'];
            $method->type = $data['type'];
            $method->description = $data['description'] ?? null;
            $method->save();

            return response()->json(['msg' => 'Metodo de ingreso actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

  /**
 * Muestra los detalles de un método de pago específico.
 *
 * Se accede por ruta con parámetro `{paymentMethod}` (inyección de modelo).
 *
 * @urlParam id integer required ID del método de pago. Example: 1
 *
 * @response 200 {
 *   "paymentOption": {
 *     "id": 1,
 *     "name": "Tarjeta de Crédito",
 *     "type": "digital"
 *   }
 * }
 */
    public function show(PaymentMethod $paymentMethod)
    {
        return response()->json([
            'paymentOption' => [
                'id' => $paymentMethod->id,
                'name' => $paymentMethod->name,
                'type' => $paymentMethod->type
            ]
        ]);
    }

  /**
 * Elimina un método de pago.
 *
 * @authenticated
 * @bodyParam id integer required ID del método a eliminar. Example: 3
 *
 * @response 200 {"msg": "Metodo de ingreso eliminado correctamente"}
 * @response 500 {"msg": "Error inerno del sistema"}
 */
    public function destroy(Request $request)
    {
        try {
            
            $data = $request->validate([
               'id' => 'required|numeric'
           ]);
           PaymentMethod::destroy( $data['id']);

           return response()->json(['msg' => 'Metodo de ingreso eliminado correctamente'], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => 'Error inerno del sistema'], 500);
       }
    }
}