<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource in the specified format.
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
     * Store a newly created resource in storage (without mass assignment).
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

    public function update(Request $request)
    {
        try {

            Log::info("entra a actualizar un metodo de ingreso");
             $data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'type' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);
            Log::info($request);
            $method = PaymentMethod::find( $data['id']);
            $method->name =  $data['name'];
            $method->type =  $data['type'];
            $method->description =  $data['description'];
            $method->save();

            return response()->json(['msg' => 'Metodo de ingreso actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    /**
     * Display the specified resource.
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
     * Remove the specified resource from storage.
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
           Log::error($th);
           return response()->json(['msg' => 'Error inerno del sistema'], 500);
       }
    }
}