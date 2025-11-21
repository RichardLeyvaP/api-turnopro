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
 * Asigna un profesional a un curso.
 *
 * Crea una relación muchos a muchos entre un curso y un profesional.
 *
 * @authenticated
 * @bodyParam course_id integer required ID del curso. Example: 5
 * @bodyParam professional_id integer required ID del profesional. Example: 3
 *
 * @response 200 {"msg": "Professional asignado correctamente al curso"}
 * @response 500 {"msg": "[mensaje de error]Error interno del sistema"}
 */
    public function store(Request $request)
    {
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
        return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    /**
 * Obtiene los profesionales asignados a un curso específico.
 *
 * Retorna una lista enriquecida con nombre completo, email, imagen (con timestamp anti-caché) y cargo del profesional.
 *
 * @authenticated
 * @queryParam course_id integer required ID del curso. Example: 5
 *
 * @response 200 {
 *   "courseProfessionals": [
 *     {
 *       "id": 12,
 *       "course_id": 5,
 *       "professional_id": 3,
 *       "name": "Carlos Pérez",
 *       "email": "carlos@example.com",
 *       "image_url": "professionals/3.jpg?$2025-11-21 15:30:00",
 *       "charge": "Instructor Senior"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "[mensaje de error]Error interno del servidor"}
 */
    public function show(Request $request)
    {
        try {
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
            return response()->json(['msg' => $th->getMessage()."Error interno del servidor"], 500);
        }
    }

    /**
 * Obtiene los profesionales **no asignados** a un curso.
 *
 * Útil para interfaces de gestión donde se muestra la lista de profesionales disponibles para asignar.
 *
 * @authenticated
 * @queryParam course_id integer required ID del curso. Example: 5
 *
 * @response 200 {
 *   "professionals": [
 *     {
 *       "id": 7,
 *       "name": "María López",
 *       "image_url": "professionals/7.jpg",
 *       "charge": "Asistente"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "[mensaje de error]Error al mostrar las branches"}
 */
    public function show_Notin(Request $request)
    {
        try {             
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
 * Elimina la asignación de un profesional a un curso.
 *
 * Rompe la relación entre el curso y el profesional en la tabla pivote `course_professional`.
 *
 * @authenticated
 * @bodyParam course_id integer required ID del curso. Example: 5
 * @bodyParam professional_id integer required ID del profesional. Example: 3
 *
 * @response 200 {"msg": "Afiliación eliminado correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
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
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }
}
