<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseStudent;
use App\Models\Finance;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CourseStudentController extends Controller
{
    protected $token_id = '46s7ZFu650qBRGIdlNjpB8ZsbQqQYDxHliq7R0wZCrgHUIOZ88auQMIa8TSxOLUo';
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
    #[BodyParam('course_id', 'numeric', required: true, example: '5', description:'id del curso')]
    #[BodyParam('name', 'string', required: true, example: 'Pepe Rosales Mora', description:'nombre y apellido del estudiante')]
    #[BodyParam('phone', 'string', required: true, example: '+56912345678', description:'teléfono del estudiante')]
    #[BodyParam('email', 'email', required: true, example: 'ejemplo@gmail.com', description:'correo del estudiante')]
    #[BodyParam('client_image', 'file', required: false, description:'imagen del estudiante')]
    #[BodyParam('fie', 'file', required: false, description:'comprobante de pago')]
    #[Response(['msg' => 'Estudiante matriculado correctamente al curso'], 200)]
    #[Response(['msg' => 'Error al matricular el estudiante al curso'], 500)]
    public function store_landing(Request $request)
    {
        Log::info("Matricular estudiante al curso landing");
        Log::info($request);
        $token_id = $request->input('token_id');
        if ($token_id != $this->token_id) {
            return response()->json(['msg' => 'Token inválido'], 403);
        }
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'course_id' => 'required|numeric',
                'name' => 'required|max:255',
                'phone' => 'required|max:50',
                'email' => 'required|max:50',
                'client_image' => 'nullable',
                'file' => 'nullable',
                'reservation_payment' => 'nullable|numeric',
                'total_payment' => 'nullable|numeric',
                'enrollment_confirmed' => 'required|numeric',
            ]);
            
            if ($request->hasFile('file')) {
                Log::info("tiene un archivo");
              }

            $course = Course::find($data['course_id']);
            $code = Str::random(8);
            $url = 'https://landingbh.simplifies.cl/student/?code=' . rawurlencode($code);
            $qrCode = QrCode::format('svg')->size(250)->generate($url);
            // Guardar el código QR en storage/students/qr_codes
            $fileName = $code . uniqid() . '.svg';
            Storage::disk('public')->put('students/qr_codes/' . $fileName, $qrCode);

            // La ruta del archivo guardado
            $qrCodeFilePath = 'students/qr_codes/' . $fileName;
           
            $student = new Student;
            $student->code = $code;
            $student->qr_url = $qrCodeFilePath;
            $student->name = $data['name'];
            $student->email = $data['email'];
            $student->phone = $data['phone'];
            $student->save();
           
            $filename = ""; 
            if ($request->hasFile('client_image')) {
               $filename = $request->file('client_image')->storeAs('students',$student->id.'.'.$request->file('client_image')->extension(),'public');
                
            $student->student_image = $filename;
            $student->save();
            }
            if ($request->hasFile('file')) {
               $file = $request->file('file')->storeAs('students/pagos',$student->id.'-'.$data['course_id'].'.'.$request->file('file')->extension(),'public');
               
            }
            // Determinar el valor para total_payment
            $totalPayment = isset($data['total_payment']) 
                ? $data['total_payment'] 
                : (isset($data['reservation_payment']) ? $data['reservation_payment'] : null);

                // Asociar estudiante al curso usando Eloquent
                $courseStudent = new CourseStudent();
                $courseStudent->course_id = $data['course_id'];
                $courseStudent->student_id = $student->id;
                $courseStudent->reservation_payment = $data['reservation_payment'] ?? null;
                $courseStudent->total_payment = $totalPayment;
                $courseStudent->enrollment_confirmed = $data['enrollment_confirmed'] ?? false;
                $courseStudent->image_url = $file ?? '';
                $courseStudent->save();

                // Crear registro financiero
                $finance = new Finance();
                $finance->control = Finance::max('control') + 1;
                $finance->operation = 'Ingreso';
                $finance->amount = $totalPayment;
                $finance->comment = 'Ingreso por matrícula de estudiante en curso '.$course->name;
                $finance->enrollment_id = $course->enrollment_id;
                $finance->type = 'Academia';
                $finance->revenue_id = 3;
                $finance->data = Carbon::now();                
                $finance->file = '';
                $finance->course_student_id = $courseStudent->id; // Usamos el ID del modelo recién creado
                $finance->save();
            $course->available_slots = $course->available_slots - 1;
            $course->save();
            DB::commit();
            return response()->json(['msg' => 'Estudiante matriculado correctamente al curso'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
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
                    //'surname' => $student->surname,
                    //'second_surname' => $student->second_surname,
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
        Log::info("Editar estudiante mariculado en un curso");
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

            // Obtener la relación course_student actualizada
        $courseStudent = DB::table('course_student')
                         ->where('course_id', $data['course_id'])
                         ->where('student_id', $data['student_id'])
                         ->first();

            //agregar a Finanzas
           $course = Course::find($data['course_id']);  
           $financeCourse = Finance::where('course_student_id', $courseStudent->id)->first();

            if ($financeCourse) {
                // Si existe y el monto es diferente, actualizar
                if ($financeCourse->amount != $data['total_payment']) {
                    $financeCourse->amount = $data['total_payment'];
                    $financeCourse->comment = 'Actualización de matrícula de estudiante en curso '.$course->name;
                    //$financeCourse->data = Carbon::now();
                    $financeCourse->save();
                } 
            } else {
                $finance = new Finance();
                $finance->control = Finance::max('control') + 1;
                $finance->operation = 'Ingreso';
                $finance->amount = $data['total_payment'];
                $finance->comment = 'Ingreso por matrícula de estudiante en curso '.$course->name;
                $finance->enrollment_id = $course->enrollment_id;
                $finance->type = 'Academia';
                $finance->revenue_id = 3;
                $finance->data = Carbon::now();
                $finance->file = '';
                $finance->course_student_id = $courseStudent->id;
                $finance->save();
            }
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
