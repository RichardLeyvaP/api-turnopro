<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\ProfessionalPayment;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ProfessionalPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required',
                'professional_id' => 'required',
                'amount' => 'required|numeric',
                'type' => 'required|string',
            ]);

            $professionalPayment = new ProfessionalPayment();
            $professionalPayment->branch_id = $data['branch_id'];
            $professionalPayment->professional_id = $data['professional_id'];
            $professionalPayment->date = Carbon::now();
            $professionalPayment->amount = $data['amount'];
            $professionalPayment->type = $data['type'];

            // Guardar el modelo
            $professionalPayment->save();

            if ($professionalPayment->type != 'Adelanto') {
                // Actualizar carros con professional_payment_id
                $carIds = $request->input('car_ids', []);
                Car::whereIn('id', $carIds)->update(['professional_payment_id' => $professionalPayment->id]);
            }

            return response()->json($professionalPayment, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Error de validación: ' . $e->getMessage()], 400);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }
    

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            $request->validate([
                'professional_id' => 'required|exists:professionals,id',
                'branch_id' => 'required|exists:branches,id',
            ]);

            $professionalId = $request->professional_id;
            $branchId = $request->branch_id;

            $payments = ProfessionalPayment::where('professional_id', $professionalId)
                                          ->where('branch_id', $branchId)
                                          ->get()->map(function ($query){
                                            return [
                                                'id' => $query->id,
                                                'branch_id ' =>$query->branch_id,
                                                'professional_id' => $query->professional_id,
                                                'date' => $query->date.' '.Carbon::parse($query->created_at)->format('H:i:s'),
                                                'type' => $query->type,
                                                'amount' => $query->amount
                                            ];
                                          });

            return response()->json($payments, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Error de validación: ' . $e->getMessage()], 400);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProfessionalPayment $professionalPayment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProfessionalPayment $professionalPayment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProfessionalPayment $professionalPayment)
    {
        //
    }
}
