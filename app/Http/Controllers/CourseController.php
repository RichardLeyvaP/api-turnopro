<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;

class CourseController extends Controller
{
    /**
 * Muestra la lista de todos los cursos con sus academias asociadas.
 *
 * Incluye metadatos del curso y datos completos de la academia (`enrollment`).
 * La imagen del curso incluye un timestamp para evitar caché del navegador.
 *
 * @group Landing
 * @subgroup Endpoints de la landing
 *
 * @response 200 {
 *   "courses": [
 *     {
 *       "id": 6,
 *       "enrollment_id": 4,
 *       "name": "Nuevo Prueba",
 *       "description": "kslfksf",
 *       "price": 12000,
 *       "startDate": "2024-05-21",
 *       "endDate": "2024-05-31",
 *       "course_image": "courses/6.jpg?$2025-11-21 15:00:00",
 *       "total_enrollment": 10,
 *       "available_slots": 8,
 *       "reservation_price": 12000,
 *       "duration": 10,
 *       "practical_percentage": 10,
 *       "theoretical_percentage": 90,
 *       "enrollment": {
 *         "id": 4,
 *         "business_id": 1,
 *         "name": "Academia Hernandez",
 *         "description": "Cursos de barberia Básicos y avanzados",
 *         "location": "dwqdqwdwqqwqd",
 *         "image_data": "enrollments/4.jpg",
 *         "address": "qwwqwqdqwd",
 *         "phone": 56949879923
 *       }
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los cursos"}
 */
    public function index()
    {
        try { 
            $courses = Course::with('enrollment')->get()->map(function ($query){
                return [
                    "id" => $query->id,
                    "enrollment_id" => $query->enrollment_id,
                    "name" => $query->name,
                    "description" => $query->description,
                    "price" => $query->price,
                    "startDate" => $query->startDate,
                    "endDate" => $query->endDate,
                    "course_image" => $query->course_image . '?$' . Carbon::now(),
                    "total_enrollment" => $query->total_enrollment,
                    "available_slots" => $query->available_slots,
                    "reservation_price" => $query->reservation_price,
                    "duration" => $query->duration,
                    "practical_percentage" => $query->practical_percentage,
                    "theoretical_percentage" => $query->theoretical_percentage,
                    "enrollment" => $query->enrollment
                ];
            });
            return response()->json(['courses' => $courses], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            return response()->json(['msg' => "Error al mostrar los cursos"], 500);
        }
    }

    /**
 * Crea un nuevo curso.
 *
 * Permite adjuntar una imagen opcional. Si no se adjunta, se usa una imagen por defecto.
 *
 * @authenticated
 * @bodyParam name string required Nombre del curso. Max: 100 caracteres. Example: "Barbería Avanzada"
 * @bodyParam description string required Descripción del curso. Example: "Técnicas profesionales de corte y diseño."
 * @bodyParam price number required Precio total del curso. Example: 12000
 * @bodyParam startDate date required Fecha de inicio (formato Y-m-d). Example: "2025-12-01"
 * @bodyParam endDate date required Fecha de finalización (formato Y-m-d). Example: "2026-02-01"
 * @bodyParam enrollment_id integer optional ID de la academia asociada. Example: 4
 * @bodyParam total_enrollment integer optional Capacidad total del curso. Example: 20
 * @bodyParam available_slots integer optional Cupos disponibles. Example: 15
 * @bodyParam reservation_price number optional Precio de reservación. Example: 2000
 * @bodyParam duration integer optional Duración en días o semanas. Example: 60
 * @bodyParam practical_percentage number optional Porcentaje práctico. Example: 70
 * @bodyParam theoretical_percentage number optional Porcentaje teórico. Example: 30
 * @bodyParam course_image file optional Imagen del curso
 *
 * @response 200 {"msg": "Curso creado correctamente"}
 * @response 500 {"msg": "[mensaje de error]Error interno del sistema"}
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
            $filename = "courses/default.jpg"; 
            if ($request->hasFile('course_image')) {
               $filename = $request->file('course_image')->storeAs('courses',$course->id.'.'.$request->file('course_image')->extension(),'public');
            }
            $course->course_image = $filename;
            $course->save();

            return response()->json(['msg' => 'Curso creado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    /**
 * Lista los cursos asociados a un negocio específico.
 *
 * Filtra los cursos cuya academia (`enrollment`) pertenece al `business_id` proporcionado.
 *
 * @authenticated
 * @queryParam business_id integer required ID del negocio. Example: 1
 *
 * @response 200 {
 *   "courses": [
 *     {
 *       "id": 6,
 *       "name": "Nuevo Prueba",
 *       "enrollment": { "name": "Academia Hernandez", ... }
 *     }
 *   ]
 * }
 * @response 500 {"msg": "[mensaje de error]Error al mostrar los Cursos"}
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
 * Actualiza un curso existente.
 *
 * Permite reemplazar la imagen del curso. Si se sube una nueva, la anterior (si no es la predeterminada) se elimina del almacenamiento.
 *
 * @authenticated
 * @bodyParam id integer required ID del curso a actualizar. Example: 6
 * @bodyParam name string required Nombre del curso. Max: 100 caracteres. Example: "Barbería Profesional"
 * @bodyParam description string required Descripción del curso. Example: "Formación integral para barberos."
 * @bodyParam price number required Precio total del curso. Example: 15000
 * @bodyParam startDate date required Fecha de inicio. Example: "2025-12-10"
 * @bodyParam endDate date required Fecha de finalización. Example: "2026-03-10"
 * @bodyParam enrollment_id integer optional ID de la academia. Example: 4
 * @bodyParam total_enrollment integer optional Capacidad total. Example: 25
 * @bodyParam available_slots integer optional Cupos disponibles. Example: 10
 * @bodyParam reservation_price number optional Precio de reservación. Example: 3000
 * @bodyParam duration integer optional Duración. Example: 90
 * @bodyParam practical_percentage number optional Porcentaje práctico. Example: 80
 * @bodyParam theoretical_percentage number optional Porcentaje teórico. Example: 20
 * @bodyParam course_image file optional Nueva imagen del curso.
 *
 * @response 200 {"msg": "Curso creado correctamente"}
 * @response 500 {"msg": "Error al crear al Curso"}
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

            return response()->json(['msg' => 'Curso creado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al crear al Curso'], 500);
        }
    }

    /**
 * Elimina un curso.
 *
 * Si el curso tiene una imagen personalizada (distinta de la predeterminada), se elimina del almacenamiento.
 *
 * @authenticated
 * @bodyParam id integer required ID del curso a eliminar. Example: 6
 *
 * @response 200 {"msg": "Curso eliminado correctamente"}
 * @response 500 {"msg": "Error al eliminar el Curso"}
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

    /**
 * Calcula las ganancias totales de todos los cursos.
 *
 * Incluye:
 * - Suma de `total_payment` de la relación pivote `course_student`
 * - Suma de precios de ventas de productos (`productSales`) realizadas por estudiantes del curso
 *
 * @authenticated
 *
 * @response 200 [
 *   {
 *     "curso": "Barbería Avanzada",
 *     "total_payment": 30000,
 *     "price": 5000,
 *     "total": 35000
 *   }
 * ]
 * @response 500 {"msg": "Error al mostrar los cursos"}
 */
    public function calculateCourseEarnings(Request $request)  
    {
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
            return response()->json(['msg' => "Error al mostrar los cursos"], 500);
        }
        // Obtener todos los cursos con sus estudiantes y ventas de productos cargados
        
    }

    /**
 * Calcula las ganancias de los cursos pertenecientes a una academia específica.
 *
 * Filtra por `enrollment_id` y aplica la misma lógica de ganancias que `calculateCourseEarnings`.
 *
 * @authenticated
 * @queryParam enrollment_id integer required ID de la academia. Example: 4
 *
 * @response 200 [
 *   {
 *     "curso": "Corte Básico",
 *     "total_payment": 12000,
 *     "price": 2000,
 *     "total": 14000
 *   }
 * ]
 * @response 500 {"msg": "Error al mostrar los cursos"}
 */
    public function calculateCourseEarningsEnrollment(Request $request)  
    {
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
            return response()->json(['msg' => "Error al mostrar los cursos"], 500);
        }
        // Obtener todos los cursos con sus estudiantes y ventas de productos cargados
        
    }
}
