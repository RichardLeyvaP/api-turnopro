<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try { 
            
            Log::info( "entra a cliente");

            return response()->json(['courses' => Course::with('enrollment')->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            Log::error($th);

            return response()->json(['msg' => "Error al mostrar los cursos"], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                
                'name' => 'required|max:100',
                'description' => 'required',
                'price' => 'required|numeric',
                'startDate' => 'required|date',
                'endDate' => 'required|date',
                'enrollment_id' => 'nullable|numeric',
                'total_enrollment' => 'nullable|numeric',
                'available_slots' => 'nullable|numeric',
                'reservation_price' => 'nullable|numeric',
                'duration' => 'nullable|numeric',
                'practical_percentage' => 'nullable|numeric',
                'theoretical_percentage' => 'nullable|numeric',   
            ]);
           
            $course = new Course();
            $course->name = $data['name'];
            $course->description = $data['description'];
            $course->price = $data['price'];
            $course->startDate = $data['startDate'];
            $course->endDate = $data['endDate'];
            $course->enrollment_id = $data['enrollment_id'];
            $course->total_enrollment = $data['total_enrollment'];
            $course->available_slots = $data['available_slots'];
            $course->reservation_price = $data['reservation_price'];
            $course->duration = $data['duration'];
            $course->practical_percentage = $data['practical_percentage'];
            $course->theoretical_percentage = $data['theoretical_percentage'];
            $course->save();
            Log::info($course);
            $filename = "courses/default.jpg"; 
            if ($request->hasFile('course_image')) {
               $filename = $request->file('course_image')->storeAs('courses',$course->id.'.'.$request->file('course_image')->extension(),'public');
            }
            $course->course_image = $filename;
            $course->save();

            return response()->json(['msg' => 'Curso creado correctamente'], 200);
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
            $data = $request->validate([
                'business_id' => 'required|numeric'
            ]);
            return response()->json(['courses' => Course::whereHas('enrollment', function ($query) use ($data){
                $query->where('business_id', $data['business_id']);
            })->with('enrollment')->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()."Error al mostrar los Cursos"], 500);
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
                'name' => 'required|max:100',
                'description' => 'required',
                'price' => 'required|numeric',
                'startDate' => 'required|date',
                'endDate' => 'required|date',
                'enrollment_id' => 'nullable|numeric',
                'total_enrollment' => 'nullable|numeric',
                'available_slots' => 'nullable|numeric',
                'reservation_price' => 'nullable|numeric',
                'duration' => 'nullable|numeric',
                'practical_percentage' => 'nullable|numeric',
                'theoretical_percentage' => 'nullable|numeric',   
            ]);
           
            Log::info($request['course_image']);
            $course = Course::find($data['id']);
            if ($request->hasFile('course_image')) {
                if($course->course_image != 'courses/default.jpg'){
                $destination = public_path("storage\\" . $course->course_image);
                if (File::exists($destination)) {
                    File::delete($destination);
                }            
            }                      
                    $course->course_image = $request->file('course_image')->storeAs('courses',$course->id.'.'.$request->file('course_image')->extension(),'public');
                }
            $course->name = $data['name'];
            $course->description = $data['description'];
            $course->price = $data['price'];
            $course->startDate = $data['startDate'];
            $course->endDate = $data['endDate'];
            $course->enrollment_id = $data['enrollment_id'];
            $course->total_enrollment = $data['total_enrollment'];
            $course->available_slots = $data['available_slots'];
            $course->reservation_price = $data['reservation_price'];
            $course->duration = $data['duration'];
            $course->practical_percentage = $data['practical_percentage'];
            $course->theoretical_percentage = $data['theoretical_percentage'];
            $course->save();
            Log::info($course);

            return response()->json(['msg' => 'Curso creado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al crear al Curso'], 500);
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
            $course = Course::find($data['id']);
            if ($course->course_image != "courses/default.jpg") {
                $destination=public_path("storage\\".$course->course_image);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }
                }
                Course::destroy($data['id']);

            return response()->json(['msg' => 'Curso eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el Curso'], 500);
        }
    }

    public function calculateCourseEarnings(Request $request)  {

        try { 
            $cursos = Course::with('students.productSales')->get()->map(function ($curso){
                $sumaProduct = 0;
                $sumpayment = 0;
                // Iterar a través de los estudiantes del curso
                foreach ($curso->students as $student) {
                    // Iterar a través de las ventas de productos de este estudiante
                    foreach ($student->productSales as $productSale) {
                        // Sumar el precio del producto vendido
                        $sumaProduct += $productSale->price;
                    }
                }
                // Sumar el total_payment de la tabla pivot course_student
                $sumpayment = $curso->students()->sum('total_payment');
    
                return [
                    'curso' => $curso->name, 
                    'total_payment' => $sumpayment,
                    'price' => $sumaProduct, // Ganancia sin contar el total_payment
                    'total' => $sumpayment + $sumaProduct, // Ganancia total incluyendo total_payment
                ];
            });
            return response()->json($cursos, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            Log::error($th);

            return response()->json(['msg' => "Error al mostrar los cursos"], 500);
        }
        // Obtener todos los cursos con sus estudiantes y ventas de productos cargados
        
    }

    public function calculateCourseEarningsEnrollment(Request $request)  {

        try {             
            $data = $request->validate([
                'enrollment_id' => 'required|numeric'
            ]);
            $cursos = Course::where('enrollment_id', $data['enrollment_id'])->with('students.productSales')->get()->map(function ($curso){
                $sumaProduct = 0;
                $sumpayment = 0;
                // Iterar a través de los estudiantes del curso
                foreach ($curso->students as $student) {
                    // Iterar a través de las ventas de productos de este estudiante
                    foreach ($student->productSales as $productSale) {
                        // Sumar el precio del producto vendido
                        $sumaProduct += $productSale->price;
                    }
                }
                // Sumar el total_payment de la tabla pivot course_student
                $sumpayment = $curso->students()->sum('total_payment');
    
                return [
                    'curso' => $curso->name, 
                    'total_payment' => $sumpayment,
                    'price' => $sumaProduct, // Ganancia sin contar el total_payment
                    'total' => $sumpayment + $sumaProduct, // Ganancia total incluyendo total_payment
                ];
            });
            return response()->json($cursos, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            Log::error($th);

            return response()->json(['msg' => "Error al mostrar los cursos"], 500);
        }
        // Obtener todos los cursos con sus estudiantes y ventas de productos cargados
        
    }
}
