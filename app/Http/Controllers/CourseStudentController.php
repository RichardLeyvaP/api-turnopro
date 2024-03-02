<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseStudent;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourseStudentController extends Controller
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
        Log::info("Matricular estudiante al curso");
        Log::info($request);
        try {
            $data = $request->validate([
                'course_id' => 'required|numeric',
                'student_id' => 'required|numeric'
            ]);
            $course = Course::find($data['course_id']);
            $student = Student::find($data['student_id']);

            $course->students()->attach($student->id);

            return response()->json(['msg' => 'Estuduando matriculado correctamente al curso'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error al matricular el estudiante al curso'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {             
            Log::info("Dado una curso devuelve los estudiantes matriculados");
            $data = $request->validate([
                'course_id' => 'required|numeric'
            ]);
            $students = Student::whereHas('courses', function ($query) use ($data){
                $query->where('course_id', $data['course_id']);
            })->with('courses')->get();
                return response()->json(['students' => $students],200, [], JSON_NUMERIC_CHECK); 
          
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error al mostrar las estudiantes del curso"], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CourseStudent $courseStudent)
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
                'student_id' => 'required|numeric'
            ]);
            $course = Course::find($data['course_id']);
            $student = Student::find($data['student_id']);
            $course->students()->detach($student->id);
            return response()->json(['msg' => 'Estudiante desmatriculado correctamente del curso'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error al sacar al estudiante de este curso'], 500);
        }
    }
}
