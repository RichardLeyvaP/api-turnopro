<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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
                'business_id' => 'required|numeric',
                'location' => 'nullable',
                'address' => 'nullable',
                'phone' => 'nullable'
            ]);
            
            $enrolment = new Enrollment();
            $enrolment->name = $data['name'];
            $enrolment->description = $data['description'];
            $enrolment->business_id = $data['business_id'];
            $enrolment->location = $data['location'];
            $enrolment->address = $data['address'];
            $enrolment->phone = $data['phone'];
            $enrolment->save();
            $filename = "enrollments/default.jpg";
            if ($request->hasFile('image_data')) {
                $filename = $request->file('image_data')->storeAs('enrollments', $enrolment->id . '.' . $request->file('image_data')->extension(), 'public');
            }
            $enrolment->image_data = $filename;
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
                'location' => 'nullable',
                'address' => 'nullable',
                'phone' => 'nullable'
            ]);
            Log::info($data);
            $enrollment = Enrollment::find($data['id']);
            if ($request->hasFile('image_data')) {
                if($enrollment->image_data != 'enrollments/default.jpg'){
                $destination = public_path("storage\\" . $enrollment->image_data);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
                }
                $enrollment->image_data = $request->file('image_data')->storeAs('enrollments', $enrollment->id . '.' . $request->file('image_data')->extension(), 'public');
            }
            $enrollment->name = $data['name'];
            $enrollment->description = $data['description'];
            $enrollment->business_id = $data['business_id'];
            $enrollment->location = $data['location'];
            $enrollment->address = $data['address'];
            $enrollment->phone = $data['phone'];
            $enrollment->save();

            return response()->json(['msg' => 'Academia actualizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage().'Error al actualizar la academia'], 500);
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
            $enrollment = Enrollment::find($data['id']);
            if ($enrollment->image_data != "enrollments/default.jpg") {
                $destination = public_path("storage\\" . $enrollment->image_data);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
            }
            Enrollment::destroy($data['id']);

            return response()->json(['msg' => 'academia eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar la academia'], 500);
        }
    }
}
