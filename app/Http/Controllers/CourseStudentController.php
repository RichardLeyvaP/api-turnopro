<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseStudent;
use App\Models\Finance;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;

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
                'student_id' => 'required|numeric',
                'reservation_payment' => 'nullable|numeric',
                'total_payment' => 'nullable|numeric',
                'enrollment_confirmed' => 'nullable',
                'image_url' => 'nullable',
            ]);
            
            $course = Course::find($data['course_id']);
            $student = Student::find($data['student_id']);

            $course->students()->attach($student->id);
            $course->available_slots = $course->available_slots - 1;
            $course->save();

            return response()->json(['msg' => 'Estudiante matriculado correctamente al curso'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al matricular el estudiante al curso'], 500);
        }
    }

    #[Group('Landing', 'Endpoints de la landing')]
    #[Endpoint('store_landing', 'Matricular estudiante al curso')]
    #[BodyParam('course_id', 'numeric', required: true, example: '5')]
    #[BodyParam('name', 'string', required: true, example: 'Pepe Rosales Mora')]
    #[BodyParam('phone', 'string', required: true, example: '+56912345678')]
    #[BodyParam('email', 'email', required: true, example: 'ejemplo@gmail.com')]
    #[BodyParam('course_image', 'file', required: false)]
    #[BodyParam('fie', 'file', required: false)]
    #[Response(['msg' => 'Estudiante matriculado correctamente al curso'], 200)]
    #[Response(['msg' => 'Error al matricular el estudiante al curso'], 500)]
    public function store_landing(Request $request)
    {
        Log::info("Matricular estudiante al curso");
        Log::info($request);
        try {
            $data = $request->validate([
                'course_id' => 'required|numeric',
                'name' => 'required|max:50',
                //'surname' => 'required|max:50',
                //'second_surname' => 'required|max:50',
                'phone' => 'required|max:50',
                'email' => 'required|max:50',
                'client_image' => 'nullable',
                'file' => 'nullable',
            ]);
            
            if ($request->hasFile('file')) {
                Log::info("tiene un archivo");
              }

            $course = Course::find($data['course_id']);
           
            $student = new Student;
            $student->name = $data['name'];
            $student->surname = $data['surname'];
            $student->second_surname = $data['second_surname'];
            $student->email = $data['email'];
            $student->phone = $data['phone'];
            $student->save();
           
            $filename = ""; 
            if ($request->hasFile('client_image')) {
                Log::info("tiene una imagen");
               $filename = $request->file('course_image')->storeAs('students',$student->id.'.'.$request->file('course_image')->extension(),'public');
            }
          
           
            $atributosParaActualizar = [
              
                'image_url' => $filename,
            ];

            $student->courses()->syncWithoutDetaching([
                $data['course_id'] => $atributosParaActualizar,
            ]);          
          


           // $course->students()->attach($student->id);
            $course->available_slots = $course->available_slots - 1;
            $course->save();

            return response()->json(['msg' => 'Estudiante matriculado correctamente al curso'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al matricular el estudiante al curso'], 500);
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
         /*   $students = Student::whereHas('courses', function ($query) use ($data) {
                $query->where('course_id', $data['course_id']);
            })->with('courses')->get();*/

              // Obtiene los estudiantes inscritos en el curso especificado, incluyendo los datos adicionales
         
              $course = Course::with(['students' => function($query) {
                $query->withPivot('reservation_payment', 'total_payment', 'enrollment_confirmed', 'image_url', 'enabled', 'payment_status', 'amount_pay');
            }])->find($data[ 'course_id']);
        
            if (!$course) {
                return response()->json(['message' => 'Curso no encontrado'], 404);
            }
        
            // Opcional: Transformar la estructura de los datos si es necesario
            $students = $course->students->map(function ($student) {
                return [
                    'id' => $student->id, 
                    'name' => $student->name, // Asume que tus estudiantes tienen un campo 'name'
                    'surname' => $student->surname,
                    'second_surname' => $student->second_surname,
                    'student_image' => $student->student_image,
                    'email' => $student->email, 
                    'phone' => $student->phone, 
                    'course_id' => $student->pivot->course_id,
                    'reservation_payment' => $student->pivot->reservation_payment,
                    'total_payment' => $student->pivot->total_payment,
                    'enrollment_confirmed' => $student->pivot->enrollment_confirmed,
                    'image_url' => $student->pivot->image_url,
                    'enabled' => $student->pivot->enabled,
                    'payment_status' => $student->pivot->payment_status,
                    'amount_pay' => $student->pivot->amount_pay,
                ];
            });
            
            return response()->json(['students' => $students], 200, [], JSON_NUMERIC_CHECK);

        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las estudiantes del curso"], 500);
        }
    }

    public function course_students_product_show(Request $request)
    {
        try {
            Log::info("Dado una curso devuelve los estudiantes matriculados");
            $data = $request->validate([
                'course_id' => 'required|numeric'
            ]);
              // Obtiene los estudiantes inscritos en el curso especificado, incluyendo los datos adicionales
         
              $course = Course::with(['students'])->find($data[ 'course_id']);
        
            if (!$course) {
                return response()->json(['message' => 'Curso no encontrado'], 404);
            }
        
            // Opcional: Transformar la estructura de los datos si es necesario
            $students = $course->students->map(function ($student) {
                return [
                    'id' => $student->id, 
                    'name' => $student->name.' '.$student->surname.' '.$student->second_surname, // Asume que tus estudiantes tienen un campo 'name'
                    'student_image' => $student->student_image,
                ];
            });
            
            return response()->json(['students' => $students], 200, [], JSON_NUMERIC_CHECK);

        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CourseStudent $courseStudent)
    {
        Log::info("Matricular estudiante al curso");
        Log::info($request);
        try {
            $data = $request->validate([
                'course_id' => 'required|numeric',
                'student_id' => 'required|numeric',
                'reservation_payment' => 'nullable|numeric',
                'total_payment' => 'nullable|numeric',
                'enrollment_confirmed' => 'required|numeric',
                'image_url' => 'nullable',
            ]);
            $student = Student::find($data['student_id']);
           $totalAnt = $student->courses()->wherePivot('course_id', $data['course_id'])->value('total_payment');
            $filename = ""; 
            if ($request->hasFile('image_url')) {
                Log::info("tiene una imagen");
               $filename = $request->file('image_url')->storeAs('students/pagos',$student->id.'-'.$data['course_id'].'.'.$request->file('image_url')->extension(),'public');
            }

            $atributosParaActualizar = [
                'reservation_payment' => $data['reservation_payment'],
                'total_payment' => $data['total_payment'],
                'enrollment_confirmed' => $data['enrollment_confirmed'],
                'image_url' => $filename,
            ];

            $student->courses()->syncWithoutDetaching([
                $data['course_id'] => $atributosParaActualizar,
            ]); 

            //agregar a Finanzas
           $course = Course::find($data['course_id']);  
           //$finance = Finance::where('enrollment_id', $course->enrollment_id)orderBy('control', 'desc')->first();
            //return $course->enrollment_id;       
            /*$finance = Finance::where('enrollment_id', $course->enrollment_id)->where('revenue_id', 3)->whereDate('data', Carbon::now())->first();
            if($finance){
                Log::info('existe');
                if($totalAnt != $data['total_payment']){
                    $finance->amount = $finance->amount - $totalAnt;
                    $finance->save();
                }
                $finance->operation = 'Ingreso';
                $finance->amount = $finance->amount + $data['total_payment'];
                $finance->comment = 'Ingreso por matrícula de estudiante en curso';
                $finance->enrollment_id = $course->enrollment_id;
                $finance->type = 'Academia';
                $finance->revenue_id = 3;
                $finance->data = Carbon::now();                
                $finance->file = '';
                $finance->save();
            }
            else{*/
                //Log::info('no existe');
                $finance = Finance::orderBy('control', 'desc')->first();
                if($finance)
                    {
                        $control = $finance->control+1;
                    }
                    else {
                        $control = 1;
                    }
                $finance = new Finance();
                $finance->control = $control;
                $finance->operation = 'Ingreso';
                $finance->amount = $data['total_payment'];
                $finance->comment = 'Ingreso por matrícula de estudiante en curso '.$course->name;
                $finance->enrollment_id = $course->enrollment_id;
                $finance->type = 'Academia';
                $finance->revenue_id = 3;
                $finance->data = Carbon::now();                
                $finance->file = '';
                $finance->save();
            //}

            return response()->json(['msg' => 'Estudiante actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al matricular el estudiante al curso'], 500);
        }
    }

    public function update2(Request $request)
    {
        Log::info("Editar estado en el curso");
        Log::info($request);
        try {
            $data = $request->validate([
                'course_id' => 'required|numeric',
                'student_id' => 'required|numeric',
                'enabled' => 'nullable|numeric',
                'payment_status' => 'nullable|numeric',
                'amount_pay' => 'required|numeric'
            ]);
            $student = Student::find($data['student_id']);
            $atributosParaActualizar = [
                'enabled' => $data['enabled'],
                'payment_status' => $data['payment_status'],
                'amount_pay' => $data['amount_pay']
            ];
            $student->courses()->updateExistingPivot($data['course_id'], $atributosParaActualizar);
            return response()->json(['msg' => 'Estado del Estudiante actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error interno del sistema'], 500);
        }
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
            $pagoAnt = $student->courses()->wherePivot('course_id', $data['course_id'])->value('image_url');
            if ($pagoAnt != "students/pagos/default.jpg") {
                $destination=public_path("storage\\".$pagoAnt);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }
                }
            $course->students()->detach($student->id);
            $course->available_slots += 1;
            $course->save();


            return response()->json(['msg' => 'Estudiante desmatriculado correctamente del curso'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al sacar al estudiante de este curso'], 500);
        }
    }
}
