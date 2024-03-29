<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnrollmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return response()->json(['enrollments' => Enrollment::with(['business'])->get()], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las academias"], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required',
                'description' => 'required',
                'business_id' => 'required|numeric'
            ]);
            
            $enrolment = new Enrollment();
            $enrolment->name = $data['name'];
            $enrolment->description = $data['description'];
            $enrolment->business_id = $data['business_id'];
            $enrolment->save();

            return response()->json(['msg' => 'Academia insertada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al insertar la academia'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'business_id' => 'required|numeric'
            ]);
            $enrollments = Enrollment::where('business_id', $data['business_id'])->with(['business'])->get();
            return response()->json(['enrollments' => $enrollments], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las academias"], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required',
                'description' => 'required',
                'business_id' => 'required|numeric',
            ]);
            
            $enrolment = Enrollment::find($data['id']);
            $enrolment->name = $data['name'];
            $enrolment->description = $data['description'];
            $enrolment->business_id = $data['business_id'];
            $enrolment->save();

            return response()->json(['msg' => 'Academia actualizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al actualizar la academia'], 500);
        }
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
            Enrollment::destroy($data['id']);

            return response()->json(['msg' => 'academia eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar la academia'], 500);
        }
    }
}
