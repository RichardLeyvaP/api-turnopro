<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try { 
            
            Log::info( "entra a cliente");

            return response()->json(['clients' => Student::all()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            Log::error($th);

            return response()->json(['msg' => "Error al mostrar los estuduantes"], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|max:50',
                'surname' => 'required|max:50',
                'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email|unique:clients',
                'phone' => 'required|max:15'
            ]);
           
            $student = new Student;
            $student->name = $data['name'];
            $student->surname = $data['surname'];
            $student->second_surname = $data['second_surname'];
            $student->email = $data['email'];
            $student->phone = $data['phone'];
            $student->save();
            Log::info($student);
            $filename = "students/default.jpg"; 
            if ($request->hasFile('student_image')) {
               $filename = $request->file('student_image')->storeAs('students',$student->id.'.'.$request->file('student_image')->extension(),'public');
            }
            $student->student_image = $filename;
            $student->save();

            return response()->json(['msg' => 'Estudiante insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al insertar el Estudiante'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        try {
            $students = Student::all()->map(function ($student){
                return [
                    'id' => $student->id,
                    'name' => $student->name.' '.$student->surname.' '.$student->second_surname,
                    'client_image' => $student->student_image

                ];
            });
            return response()->json(['students' => $students], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el estudiante"], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        try {

            Log::info("entra a actualizar");


            $data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'surname' => 'required|max:50',
                'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email',
                'phone' => 'required|max:15'
            ]);
            Log::info($request['student_image']);
            $student = Student::find($data['id']);
            if ($request->hasFile('student_image')) {
                if($student->student_image != 'students/default.jpg'){
                $destination = public_path("storage\\" . $student->student_image);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
                }                      
                    $student->student_image = $request->file('student_image')->storeAs('students',$student->id.'.'.$request->file('student_image')->extension(),'public');
                }
            $student->name = $data['name'];
            $student->surname = $data['surname'];
            $student->second_surname = $data['second_surname'];
            $student->email = $data['email'];
            $student->phone = $data['phone'];
            //$client->client_image = $filename;
            $student->save();

            return response()->json(['msg' => 'Estudiante actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al actualizar el Estudiante'], 500);
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
            $student = Student::find($data['id']);
            if ($student->student_image != "students/default.jpg") {
                $destination=public_path("storage\\".$student->student_image);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }
                }
                Student::destroy($data['id']);

            return response()->json(['msg' => 'Estudiante eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el estudiante'], 500);
        }
    }
}
