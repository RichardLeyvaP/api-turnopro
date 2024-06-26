<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseProfessional;
use App\Models\Professional;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CourseProfessionalController extends Controller
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
        Log::info("Asignar professional a un curso");
        try {
            $data = $request->validate([
                'course_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $course = Course::find($data['course_id']);
            $professional = Professional::find($data['professional_id']);

            $professional->courses()->attach($course->id);

            return response()->json(['msg' => 'Professional asignado correctamente al curso'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            Log::info("Entra a buscar los professionals de un curso");
            $data = $request->validate([
                'course_id' => 'nullable|numeric'
            ]);
            //$course = Course::find($data['course_id']);
            $now = Carbon::now();
            $courseprofessional = CourseProfessional::where('course_id', $data['course_id'])->get()->map(function ($course) use ($now){
                $professional = $course->professional;
                return [
                    'id' => $course->id,
                    'course_id' => $course->course_id,
                    'professional_id' => $course->professional_id,
                    'name' => $professional->name.' '.$professional->surname,
                    'email' => $professional->email,
                    'image_url' => $professional->image_url.'?$'.$now,
                    'charge' => $professional->charge->name
                ];
            });
            //$result = BranchServiceProfessional::with('branchService.service', 'professional')->find($data['id']);

            return response()->json(['courseProfessionals' => $courseprofessional], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()."Error interno del servidor"], 500);
        }
    }

    public function show_Notin(Request $request)
    {
        try {             
            Log::info("Dado un curso devuelve los professionales asocoados a el");
            $data = $request->validate([
                'course_id' => 'required|numeric'
            ]);
            $courseProfessionals = CourseProfessional::where('course_id', $data['course_id'])->get()->pluck('professional_id');
            $professionals = Professional::whereNotin('id', $courseProfessionals)->get()->map(function ($professional) {
                return [
                    'id' => intval($professional->id),
                    'name' => $professional->name . ' ' . $professional->surname,
                    'image_url' => $professional->image_url,
                    'charge' => $professional->charge->name

                ];
            });
                return response()->json(['professionals' => $professionals],200, [], JSON_NUMERIC_CHECK); 
          
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error al mostrar las branches"], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CourseProfessional $courseProfessional)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'course_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $course = Course::find($data['course_id']);
            $professional = Professional::find($data['professional_id']);

            $course->professionals()->detach($professional->id);
            return response()->json(['msg' => 'Afiliación eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }
}
