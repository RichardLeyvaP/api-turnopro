<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;

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
    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment method deleted successfully.'
        ]);
    }
}