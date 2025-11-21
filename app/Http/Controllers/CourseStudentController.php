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
 * Matricula un estudiante existente en un curso.
 *
 * Reduce en 1 los cupos disponibles del curso. No gestiona pagos ni archivos.
 *
 * @authenticated
 * @bodyParam course_id integer required ID del curso. Example: 5
 * @bodyParam student_id integer required ID del estudiante. Example: 12
 * @bodyParam reservation_payment number optional Monto del pago de reservación. Example: 2000
 * @bodyParam total_payment number optional Monto total pagado. Example: 12000
 * @bodyParam enrollment_confirmed boolean optional Confirmación de matrícula. Example: 1
 *
 * @response 200 {"msg": "Estudiante matriculado correctamente al curso"}
 * @response 500 {"msg": "[error]Error al matricular el estudiante al curso"}
 */
    public function store(Request $request)
    {
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
            return response()->json(['msg' => $th->getMessage() . 'Error al matricular el estudiante al curso'], 500);
        }
    }

    /**
 * Matricula un **nuevo estudiante** desde la landing page.
 *
 * Crea al estudiante, genera un código único y QR, sube archivos (foto y comprobante),
 * registra el pago en finanzas y reduce los cupos del curso.
 * Requiere un `token_id` válido para autenticación de la landing.
 *
 * @group Landing
 * @subgroup Endpoints de la landing
 *
 * @bodyParam token_id string required Token de seguridad de la landing. Example: "46s7ZFu650qBRGIdlNjpB8ZsbQqQYDxHliq7R0wZCrgHUIOZ88auQMIa8TSxOLUo"
 * @bodyParam course_id integer required ID del curso. Example: 5
 * @bodyParam name string required Nombre completo del estudiante. Example: "Pepe Rosales Mora"
 * @bodyParam phone string required Teléfono del estudiante. Example: "+56912345678"
 * @bodyParam email email required Correo electrónico. Example: "ejemplo@gmail.com"
 * @bodyParam client_image file optional Foto del estudiante.
 * @bodyParam file file optional Comprobante de pago.
 * @bodyParam reservation_payment number optional Pago de reservación. Example: 2000
 * @bodyParam total_payment number optional Pago total. Example: 12000
 * @bodyParam enrollment_confirmed integer required Confirmación de matrícula (1 = sí, 0 = no). Example: 1
 *
 * @response 200 {"msg": "Estudiante matriculado correctamente al curso"}
 * @response 403 {"msg": "Token inválido"}
 * @response 500 {"msg": "[error]Error al matricular el estudiante al curso"}
 */
    public function store_landing(Request $request)
    {
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
            return response()->json(['msg' => $th->getMessage() . 'Error al matricular el estudiante al curso'], 500);
        }
    }

    /**
 * Obtiene todos los estudiantes inscritos en un curso con detalles de pago.
 *
 * Incluye campos adicionales desde la tabla pivote: `reservation_payment`, `total_payment`,
 * `enrollment_confirmed`, `image_url`, `enabled`, `payment_status`, `amount_pay`.
 *
 * @authenticated
 * @queryParam course_id integer required ID del curso. Example: 5
 *
 * @response 200 {
 *   "students": [
 *     {
 *       "id": 12,
 *       "name": "Pepe Rosales",
 *       "email": "pepe@example.com",
 *       "phone": "+56912345678",
 *       "student_image": "students/12.jpg",
 *       "course_id": 5,
 *       "reservation_payment": 2000,
 *       "total_payment": 12000,
 *       "enrollment_confirmed": 1,
 *       "image_url": "students/pagos/12-5.pdf",
 *       "enabled": 1,
 *       "payment_status": 2,
 *       "amount_pay": 12000
 *     }
 *   ]
 * }
 * @response 500 {"msg": "[error]Error al mostrar las estudiantes del curso"}
 */
    public function show(Request $request)
    {
        try {
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
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las estudiantes del curso"], 500);
        }
    }

    /**
 * Lista estudiantes de un curso para selección en ventas de productos.
 *
 * Retorna solo `id`, `name` completo y `student_image`. Optimizado para interfaces de venta.
 *
 * @authenticated
 * @queryParam course_id integer required ID del curso. Example: 5
 *
 * @response 200 {
 *   "students": [
 *     {
 *       "id": 12,
 *       "name": "Pepe Rosales Mora",
 *       "student_image": "students/12.jpg"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "[error]Error interno del sistema"}
 */
    public function course_students_product_show(Request $request)
    {
        try {
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
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
        }
    }

    /**
 * Actualiza los datos de matrícula y pago de un estudiante en un curso.
 *
 * Permite subir nuevo comprobante de pago. Actualiza o crea registro en finanzas si el monto cambia.
 *
 * @authenticated
 * @bodyParam course_id integer required ID del curso. Example: 5
 * @bodyParam student_id integer required ID del estudiante. Example: 12
 * @bodyParam reservation_payment number optional Nuevo monto de reservación. Example: 2500
 * @bodyParam total_payment number required Nuevo monto total pagado. Example: 12500
 * @bodyParam enrollment_confirmed integer required Confirmación (1/0). Example: 1
 * @bodyParam image_url file optional Nuevo comprobante de pago.
 *
 * @response 200 {"msg": "Estudiante actualizado correctamente"}
 * @response 500 {"msg": "[error]Error al matricular el estudiante al curso"}
 */
    public function update(Request $request, CourseStudent $courseStudent)
    {
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
            return response()->json(['msg' => $th->getMessage() . 'Error al matricular el estudiante al curso'], 500);
        }
    }

    /**
 * Actualiza el estado de seguimiento de pagos del estudiante.
 *
 * Usado para gestionar estados como: pendiente, pagado, parcial, etc., y controlar accesos.
 *
 * @authenticated
 * @bodyParam course_id integer required ID del curso. Example: 5
 * @bodyParam student_id integer required ID del estudiante. Example: 12
 * @bodyParam enabled integer optional Activa/desactiva al estudiante (1/0). Example: 1
 * @bodyParam payment_status integer optional Estado del pago (1 = pendiente, 2 = pagado, etc.). Example: 2
 * @bodyParam amount_pay number required Monto actual pagado. Example: 12000
 *
 * @response 200 {"msg": "Estado del Estudiante actualizado correctamente"}
 * @response 500 {"msg": "[error]Error interno del sistema"}
 */
    public function update2(Request $request)
    {
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
            return response()->json(['msg' => $th->getMessage() . 'Error interno del sistema'], 500);
        }
    }

    /**
 * Elimina la matrícula de un estudiante en un curso.
 *
 * Incrementa en 1 los cupos disponibles del curso.
 * Si existe un comprobante de pago personalizado, se elimina del almacenamiento.
 *
 * @authenticated
 * @bodyParam course_id integer required ID del curso. Example: 5
 * @bodyParam student_id integer required ID del estudiante. Example: 12
 *
 * @response 200 {"msg": "Estudiante desmatriculado correctamente del curso"}
 * @response 500 {"msg": "[error]Error al sacar al estudiante de este curso"}
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
            return response()->json(['msg' => $th->getMessage() . 'Error al sacar al estudiante de este curso'], 500);
        }
    }
}
