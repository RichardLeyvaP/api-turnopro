<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;

class StudentController extends Controller
{
    
    /**
 * Lista todos los estudiantes del sistema.
 *
 * @authenticated
 *
 * @response 200 {
 *   "clients": [
 *     {
 *       "id": 12,
 *       "name": "Pepe Rosales",
 *       "email": "pepe@example.com",
 *       "phone": "+56912345678",
 *       "code": "Ab3x9Kz1",
 *       "qr_url": "students/qr_codes/Ab3x9Kz1.svg"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los estuduantes"}
 */
    public function index()
    {
        try { 
            return response()->json(['clients' => Student::all()], 200);
        } catch (\Throwable $th) {  
            return response()->json(['msg' => "Error al mostrar los estuduantes"], 500);
        }
    }

   /**
 * Crea un nuevo estudiante.
 *
 * Genera automáticamente un **código único** y un **código QR SVG** vinculado a una URL de landing.
 * La imagen es opcional; si no se adjunta, se usa `students/default.jpg`.
 *
 * @authenticated
 * @bodyParam name string required Nombre completo del estudiante. Max: 50 caracteres. Example: "Pepe Rosales"
 * @bodyParam email string required Correo electrónico único. Max: 50. Example: "pepe@example.com"
 * @bodyParam phone string required Teléfono. Max: 15. Example: "+56912345678"
 * @bodyParam student_image file optional Foto del estudiante
 *
 * @response 200 {"msg": "Estudiante insertado correctamente"}
 * @response 400 {"msg": ["El correo ya ha sido tomado."]}
 * @response 500 {"msg": "[error]Error al insertar el Estudiante"}
 */
    public function store(Request $request)
    {
        try {
            $data = Validator::make($request->all(), [
                'name' => 'required|max:50',
                //'surname' => 'required|max:50',
                //'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email|unique:students',
                'phone' => 'required|max:15'
            ]);
            if ($data->fails()) {
                return response()->json([
                    'msg' => $data->errors()->all()
                ], 400);
            }
            
            $code = Str::random(8);
            $url = 'https://landingbh.simplifies.cl/student/?code=' . rawurlencode($code);
            //$url = 'https://landingbh.simplifies.cl/?codigo='.$code;
            /*$datos = [
                'code' => $code,
                'url' => $url
            ];*/
            //return $qrCode = QrCode::format('png')->size(100)->generate(json_encode($code));
            $qrCode = QrCode::format('svg')->size(250)->generate($url);
            // Guardar el código QR en storage/students/qr_codes
            $fileName = $code . uniqid() . '.svg';
            Storage::disk('public')->put('students/qr_codes/' . $fileName, $qrCode);

            // La ruta del archivo guardado
            $qrCodeFilePath = 'students/qr_codes/' . $fileName;
           
            $student = new Student;
            $student->code = $code;
            $student->qr_url = $qrCodeFilePath;
            $student->name = $request->name;
            //$student->surname = $data['surname'];
            //$student->second_surname = $data['second_surname'];
            $student->email = $request->email;
            $student->phone = $request->phone;
            $student->save();

            $filename = "students/default.jpg"; 
            if ($request->hasFile('student_image')) {
               $filename = $request->file('student_image')->storeAs('students',$student->id.'.'.$request->file('student_image')->extension(),'public');
            }
            $student->student_image = $filename;
            $student->save();

            return response()->json(['msg' => 'Estudiante insertado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error al insertar el Estudiante'], 500);
        }
    }

   /**
 * Obtiene estudiantes **no matriculados** en un curso específico.
 *
 * Útil para interfaces de matrícula donde se muestra la lista de estudiantes disponibles.
 *
 * @authenticated
 * @queryParam course_id integer required ID del curso. Example: 5
 *
 * @response 200 {
 *   "students": [
 *     {
 *       "id": 12,
 *       "name": "Pepe Rosales Mora",
 *       "client_image": "students/12.jpg"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "[error]Error al mostrar el estudiante"}
 */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'course_id' => 'required|numeric'
            ]);
            $students = Student::whereDoesntHave('courses', function ($query) use ($data){
                $query->where('course_id', $data['course_id']);
            })->get()->map(function ($student){
                return [
                    'id' => $student->id,
                    'name' => $student->name.' '.$student->surname.' '.$student->second_surname,
                    'client_image' => $student->student_image

                ];
            });
            return response()->json(['students' => $students], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()."Error al mostrar el estudiante"], 500);
        }
    }

   /**
 * Muestra los datos de un estudiante y su historial académico usando un código QR.
 *
 * Retorna información del estudiante, cursos matriculados, pagos, productos comprados y estado general.
 *
 * @bodyParam code string required Código único del estudiante (del QR). Example: "Ab3x9Kz1"
 *
 * @response 200 {
 *   "student": { "id": 12, "name": "Pepe", "email": "pepe@example.com", ... },
 *   "courses": [ { "id": 5, "name": "Curso Básico", "price": 12000, ... } ],
 *   "pagos": [ { "reservation_payment": 2000, "total_payment": 12000, "enabled": 1, ... } ],
 *   "products": [ { "name": "Kit de inicio", "price": 5000, "cant": 1, ... } ],
 *   "habilitado": "Habilitado",
 *   "status": "Ok",
 *   "payMount": 12000
 * }
 * @response 500 {"msg": "[error]Error interno del sistema"}
 */
    public function student_code(Request $request)
    {
        try {
            
            $data = $request->validate([
                'code' => 'required'
            ]);
            
            $student = Student::where('code', $data['code'])->select('id', 'name', 'surname', 'second_surname', 'email', 'phone', 'student_image')->with('courses', 'productsales.productStore.product')->first();
            /*if ($student !== null) {
                $curseStudent = $student->courses;
            }*/
            $coursesArray = [];
            $pagosArray = [];
            $productsArray = [];

            $coursesData = $student['courses'];
            //$productSales = $student['productsales'];
            $studentData = [
                'id' => $student['id'],
                'name' => $student['name'],
                'surname' => $student['surname'],
                'second_surname' => $student['second_surname'],
                'email' => $student['email'],
                'phone' => $student['phone'],
                'student_image' => $student['student_image'] . '?$' . Carbon::now()
            ];
            foreach ($coursesData as $course) {
                // Extraer datos del curso
                $courseData = [
                    'id' => $course['id'],
                    'name' => $course['name'],
                    'course_image' => $course['course_image'] . '?$' . Carbon::now(),
                    'price' => $course['price'],
                    'reservation_price' => $course['reservation_price'],
                    'startDate' => $course['startDate'],
                    'endDate' => $course['endDate'],
                    'description' => $course['description'],
                    'duration' => $course['duration'],
                    // Agrega aquí los otros campos que desees extraer del curso
                ];
                $coursesArray[] = $courseData;
            
                // Extraer datos de la tabla pivot
                $pivotData = [
                    'data' => Carbon::parse($course['pivot']['updated_at'])->format('Y-m-d'),
                    'name' => $course['name'],
                    'details' => $course['pivot']['image_url'],
                    'reservation_payment' => $course['pivot']['reservation_payment'],
                    'total_payment' => $course['pivot']['total_payment'],
                    'enabled' => $course['pivot']['enabled'],
                    'payment_status' => $course['pivot']['payment_status'],
                    'amount_pay' => $course['pivot']['amount_pay'],
                    // Agrega aquí los otros campos que desees extraer de la tabla pivot
                ];
                $pagosArray[] = $pivotData;
            }
            $contadorEnabledCero =0;
            $contadorPaymentStatusCero =0;
            $sumaAmountPay = 0;
            foreach ($pagosArray as $pago) {
                // Verifica si 'enabled' es 0 y aumenta el contador
                if ($pago['enabled'] == 0) {
                    $contadorEnabledCero++;
                }
            
                // Verifica si 'payment_status' es 0 y aumenta el contador
                if ($pago['payment_status'] == 0) {
                    $contadorPaymentStatusCero++;            
                // Suma 'amount_pay'
                $sumaAmountPay += $pago['amount_pay'];
                }
            }
            $productSales = $student->productsales;

            foreach ($productSales as $productSale) {
                $product = $productSale->productStore->product;
                $course = Course::find($productSale->course_id);

                $productsArray[] = [
                    'price' => $productSale->price,
                    'cant' => $productSale->cant,
                    'course' => $course->name,
                    'name' => $product->name,
                    'image_product' => $product->image_product . '?$' . Carbon::now()
                ];
            } 
        return response()->json(['student' => $studentData , 'courses' => $coursesArray, 'pagos' => $pagosArray, 'products' => $productsArray, 'habilitado' => $contadorEnabledCero ? 'No Habilitado' : 'Habilitado',  'status' => $contadorEnabledCero ? 'Retrasado' : 'Ok',  'payMount' => $sumaAmountPay], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

/**
 * Actualiza un estudiante existente.
 *
 * Si el estudiante no tiene `code`, se le genera uno nuevo con QR.
 * Permite actualizar la imagen y valida unicidad del correo.
 *
 * @authenticated
 * @bodyParam id integer required ID del estudiante. Example: 12
 * @bodyParam name string required Nombre. Max: 50. Example: "Pepe Rosales Mora"
 * @bodyParam email string required Correo único. Example: "pepe.nuevo@example.com"
 * @bodyParam phone string required Teléfono. Max: 15. Example: "+56987654321"
 * @bodyParam student_image file optional Nueva foto.
 *
 * @response 200 {"msg": "Estudiante actualizado correctamente"}
 * @response 400 {"msg": ["El correo ya ha sido tomado."]}
 * @response 500 {"msg": "Error al actualizar el Estudiante"}
 */
    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                //'surname' => 'required|max:50',
                //'second_surname' => 'required|max:50',
                'email' => 'required|email|unique:associates,email,' . $request->id,
                'phone' => 'required|max:15'
            ]);
            
            $student = Student::find($data['id']);
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:students,email,' . $student->id,
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 400);
            }
            if(!$student->code){
                $code = Str::random(8);
                $url = 'https://landingbh.simplifies.cl/student/?code='.$code . rawurlencode($code);
                //$url = 'https://landingbh.simplifies.cl/?codigo='.$code;
            /*$datos = [
                'code' => $code,
                'url' => $url
            ];*/
            //return $qrCode = QrCode::format('png')->size(100)->generate(json_encode($code));
            $qrCode = QrCode::format('svg')->size(250)->generate(json_encode($url));
            // Guardar el código QR en storage/students/qr_codes
            $fileName = $code . uniqid() . '.svg';
            Storage::disk('public')->put('students/qr_codes/' . $fileName, $qrCode);

            // La ruta del archivo guardado
            $qrCodeFilePath = 'students/qr_codes/' . $fileName;
            $student->code = $code;
            $student->qr_url = $qrCodeFilePath;
            }
            if ($request->hasFile('student_image')) {
                if($student->student_image != 'students/default.png'){
                $destination = public_path("storage\\" . $student->student_image);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
                } 
                    $student->student_image = $request->file('student_image')->storeAs('students',$student->id.'.'.$request->file('student_image')->extension(),'public');
                }
            $student->name = $data['name'];
            //$student->surname = $data['surname'];
            //$student->second_surname = $data['second_surname'];
            $student->email = $data['email'];
            $student->phone = $data['phone'];
            //$client->client_image = $filename;
            $student->save();

            return response()->json(['msg' => 'Estudiante actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar el Estudiante'], 500);
        }
    }

    /**
 * Elimina un estudiante del sistema.
 *
 * También elimina su imagen personalizada del almacenamiento (si no es la predeterminada).
 *
 * @authenticated
 * @bodyParam id integer required ID del estudiante a eliminar. Example: 12
 *
 * @response 200 {"msg": "Estudiante eliminado correctamente"}
 * @response 500 {"msg": "Error al eliminar el estudiante"}
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
