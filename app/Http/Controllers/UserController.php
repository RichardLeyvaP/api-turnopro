<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Client;
use App\Models\Notification;
use App\Models\Professional;
use App\Models\ProfessionalWorkPlace;
use App\Models\Record;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use App\Services\SendEmailService;
use Symfony\Component\Mailer\Exception\TransportException;
use GuzzleHttp;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    private SendEmailService $sendEmailService;
    public function __construct(SendEmailService $sendEmailService )
    {
       
        $this->sendEmailService = $sendEmailService;
    }
    public function index()
    {
        try {
            return response()->json(['users' => User::all()], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los usuarios"], 500);
        }
    }

    /**
 * Registra un nuevo cliente en el sistema.
 *
 * @bodyParam name string required Nombre del cliente. Example: Yasmany Sánchez
 * @bodyParam user string required Nombre de usuario único. Example: yasmany89
 * @bodyParam password string required Contraseña del usuario. Example: secreto123
 * @bodyParam email string required Email válido y único. Example: yasmany891230@gmail.com
 * @bodyParam phone string required Número de teléfono. Example: +5359380373
 *
 * @response 201 {"msg": "Client registrado correctamente!!!", "user": {"id": 123, "name": "yasmany89", "email": "yasmany891230@gmail.com"}}
 * @response 400 {"msg": ["El campo email es obligatorio."]}
 */
    public function register_client(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'user' => 'required',
                'password' => 'required|confirmed',
                //'surname' => 'required|max:50',
                //'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email|unique:clients',
                'phone' => 'required|max:15'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 400);
            }

            $user = User::create([
                'name' => $request->user,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            $client = new Client();
            $client->name = $request->name;
            $client->email = $request->email;
            $client->phone = $request->phone;
            $client->user_id = $user->id;
            $client->client_image = 'comments/default.jpg';
            $client->save();

            return response()->json([
                'msg' => "Client registrado correctamente!!!",
                'user' => $user
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al registrarse'], 500);
        }
    }

    /**
 * Registra un nuevo profesional (trabajador).
 *
 * @bodyParam name string required Nombre completo del profesional. Example: Juan Pérez
 * @bodyParam user string required Nombre de usuario único. Example: juan_barbero
 * @bodyParam password string required Contraseña. Example: pass123
 * @bodyParam email string required Email único. Example: juan@barberia.com
 * @bodyParam phone string required Teléfono. Example: +5351234567
 * @bodyParam charge_id integer required ID del cargo (ej. barbero, técnico). Example: 2
 * @bodyParam retention number nullable Porcentaje de retención. Example: 10.5
 * @bodyParam image_url file nullable Imagen del profesional.
 * @bodyParam user_id integer nullable ID de usuario existente (si se está editando).
 *
 * @response 201 {"msg": "Professional registrado correctamente!!!", "user": {"id": 456, "name": "juan_barbero"}}
 * @response 400 {"msg": ["El email ya está en uso."]}
 * @response 401 {"msg": ["El email ya está asociado a otro profesional."]}
 */
    public function register_professional(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'user' => 'required',
                'password' => 'required',
                //'surname' => 'required|max:50',
                //'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email|unique:professionals',
                'phone' => 'required|max:15',
                'charge_id' => 'required|numeric',
                'image_url' => 'nullable',
                'retention' => 'nullable'
            ]);
            if ($validator->fails()) {
                $errors = $validator->errors();
    
            // Verifica si el error es por el campo 'email'
            if ($errors->has('email')){
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 401);
            }else{
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 400);
            }
                
            }
            $userName = User::where('name', $request->user)->whereHas('professional')->first();
            if($userName){
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 400);
            }
            if($request->user_id){
                $user = User::find($request->user_id);
                $user->name = $request->user;
                $user->password = Hash::make($request->password);
                $user->save();
            }else{
            $user = User::create([
                'name' => $request->user,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);
            }
            $professional = new Professional();
            $professional->name = $request['name'];
            $professional->email = $request['email'];
            $professional->phone = $request['phone'];
            $professional->charge_id = $request['charge_id'];
            $professional->user_id = $user->id;
            $professional->state = 0;
            $professional->retention = $request['retention'];
            $professional->save();

            $filename = "professionals/default.jpg";
            if ($request->hasFile('image_url')) {
                $filename = $request->file('image_url')->storeAs('professionals',$professional->id.'.'.$request->file('image_url')->extension(),'public');
            }
            $professional->image_url = $filename;
            $professional->save();
            DB::commit();
            return response()->json([
                'msg' => "Professional registrado correctamente!!!",
                'user' => $user
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al registrarse'], 500);
        }
    }

    /**
     * Reestablece la contraseña de un usuario y la envía por correo.
     *
     * @bodyParam email string required Email del usuario. Example: yasmany891230@gmail.com
     *
     * @response 201 {"msg": "Password modificada correctamente!!!"}
     * @response 400 {"msg": ["El campo email es obligatorio."]}
     * @response 404 {"msg": "Correo incorrecto!!!"}
     */
    public function change_password(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|numeric',
                //'old_password' => 'required',
                'password' => 'required' //|confirmed'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 400);
            }
            $user = User::find($request['id']);
    
            $user->password = Hash::make($request->password);
            $user->save();
            return response()->json(['msg' => "Password modificada correctamente!!!"], 201);

        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al modificar la password'], 500);
        }
    }

    /**
     * Reestablece la contraseña de un usuario y la envía por correo.
     *
     * @bodyParam email string required Email del usuario. Example: yasmany891230@gmail.com
     *
     * @response 201 {"msg": "Password modificada correctamente!!!"}
     * @response 400 {"msg": ["El campo email es obligatorio."]}
     * @response 404 {"msg": "Correo incorrecto!!!"}
     */
    public function reactive_password(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                //'id' => 'required|numeric',
                //'old_password' => 'required',
                'email' => 'required' //|confirmed'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 400);
            }
            $user = User::where('email',$request->email)->first();

                if(!$user){
                    return response()->json(['msg' => "Correo incorrecto!!!"], 404);
                }
              $pass = Str::random(8);
            $user->password = Hash::make($pass);
            $user->save();
            $nombre = $user->professional->name.' '.$user->professional->surname.' '.$user->professional->second_surname;
            $usuario = $user->name;
           
            $this->sendEmailService->emailRecuperarPass($request->email,$nombre, $usuario, $pass);
          
            return response()->json(['msg' => "Password modificada correctamente!!!"], 201);

        } catch (TransportException $e) {
    
            return response()->json(['msg' => 'Password modificada correctamente.Error al enviar el correo electrónico '], 200);
        }
          catch (\Throwable $th) {
              DB::rollback();
              return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }

    /**
     * Obtiene las sucursales asociadas a un profesional usando credenciales.
     *
     * @bodyParam email string required Email o nombre de usuario. Example: yasmany891230@gmail.com
     * @bodyParam password string required Contraseña. Example: mypass123
     *
     * @response 200 {"branches": [{"branch_id": 5, "nameBranch": "Centro"}]}
     * @response 400 {"msg": ["El campo password es obligatorio."]}
     */
    public function login_phone_get_branch(Request $request){
        try {

            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 400);
            }
            $userData = [];
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $userData = Auth::user();
            }    
            // Intentar la autenticación con el nombre de usuario
            elseif (Auth::attempt(['name' => $request->email, 'password' => $request->password])) {
                $userData = Auth::user();
            }
            if(!$userData){
                $branches = [];
            }else {
                //return $user = User::find($userData['id']);
                $branches = $userData->professional->branches->map(function ($branch){
                    return [
                        'branch_id' => $branch->id,
                        'nameBranch' => $branch->name
                    ];
                });
            }
            
            return response()->json(['branches' => $branches], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    /**
     * Autentica a un usuario desde la aplicación móvil (sin branch_id).
     *
     * @bodyParam email string required Email o nombre de usuario. Example: yasmany891230@gmail.com
     * @bodyParam password string required Contraseña. Example: mypass123
     *
     * @response 200 {
     *   "id": 123,
     *   "userName": "yasmany89",
     *   "token": "1|abcdefghijklmnopqrstuvwxyz",
     *   "branch_id": 5,
     *   "nameBranch": "Centro"
     * }
     * @response 404 {"msg": "Usuario no logueado"}
     */
    public function login_phone(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 400);
            }
            $branch = [
                'branch_id' => null,
                'nameBranch' => null,
                'useTechnical' => 0,
                'business_id' => 0,
                'nameBusiness' => ''
            ];
            $user = [];
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();
            }    
            // Intentar la autenticación con el nombre de usuario
            elseif (Auth::attempt(['name' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();
            }
            if ($user) {
                    $professional = $user->professional;
                    $business = Business::where('id', $professional->business_id)->get();
                    if ($professional->branches->isNotEmpty()) { // Check if branches exist
                        $branch = $professional->branches->where('id', $request->branch_id)->map(function ($branch) use ($request){
                            return [
                                'branch_id' => $branch->id,
                                'nameBranch' => $branch->name,
                                'useTechnical' => $branch->useTechnical,
                                'business_id' => $branch->business->id,
                                'nameBusiness' => $branch->business->name
                            ];
                        })->values()->first();
                    }//if de la sucursal

                    $token = $user->createToken('auth_token')->plainTextToken;
                    Auth::user();     
                    return response()->json([
                        'id' => $user->id,
                        'userName' => $user->name,
                        'email' => $user->email,
                        'business_id' => $business->value('id'),
                        'nameBusiness' => $business->value('name'),
                        'charge' => $user->professional ? $user->professional->charge->name : null,
                        'name' => $user->professional ? ($user->professional->name . ' ' . $user->professional->surname) : ($user->client->name . ' ' . $user->client->surname),
                        'charge_id' => $user->professional ? ($user->professional->charge_id) : 0,
                        'professional_id' => $user->professional ? ($user->professional->id) : 0,
                        'image' => $user->professional ? ($user->professional->image_url) : $user->client->client_image,
                        'client_id' => $user->client ? ($user->client->id) : 0,
                        'branch_id' => $user->professional->branches ? $branch['branch_id'] : 0,
                        'nameBranch' => $branch ? $branch['nameBranch'] : "",
                        'useTechnical' => $branch ? $branch['useTechnical'] : 0,
                        'token' => $token,
                    ], 200, [], JSON_NUMERIC_CHECK);
            } else {
                return response()->json([
                    "msg" => "Usuario no logueado"
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al loguearse'], 500);
        }
    }

    /**
     * Autentica a un usuario desde la app móvil con soporte para versión.
     *
     * @bodyParam email string required Email o nombre de usuario. Example: yasmany891230@gmail.com
     * @bodyParam password string required Contraseña. Example: mypass123
     * @bodyParam version string optional Versión de la app. Example: 2.1.0
     *
     * @response 200 {
     *   "id": 123,
     *   "userName": "yasmany89",
     *   "token": "1|abcdefghijklmnopqrstuvwxyz",
     *   "branch_id": 5,
     *   "nameBranch": "Centro"
     * }
     * @response 404 {"msg": "Usuario no registrado"}
     */
    public function login_phone_version(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required',
                'version' => 'sometimes'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 400);
            }
            $branch = [
                'branch_id' => null,
                'nameBranch' => null,
                'useTechnical' => 0,
                'business_id' => 0,
                'nameBusiness' => ''
            ];
            $user = [];
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();
            }    
            // Intentar la autenticación con el nombre de usuario
            elseif (Auth::attempt(['name' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();
            }
            if ($user) {
                $professional = $user->professional;
                   $business = Business::where('id', $professional->business_id)->get();
                    if ($professional->branches->isNotEmpty()) { // Check if branches exist
                             $branch = $professional->branches->where('id', $request->branch_id)->map(function ($branch) use ($request){
                            return [
                                'branch_id' => $branch->id,
                                'nameBranch' => $branch->name,
                                'useTechnical' => $branch->useTechnical,
                                'business_id' => $branch->business->id,
                                'nameBusiness' => $branch->business->name
                            ];
                        })->values()->first();

                    }//if de la sucursal

                    $token = $user->createToken('auth_token')->plainTextToken;
                    Auth::user();     
                    return response()->json([
                        'id' => $user->id,
                        'userName' => $user->name,
                        'email' => $user->email,
                        'business_id' => $business->value('id'),
                        'nameBusiness' => $business->value('name'),
                        'charge' => $user->professional ? $user->professional->charge->name : null,
                        'name' => $user->professional ? ($user->professional->name . ' ' . $user->professional->surname) : ($user->client->name . ' ' . $user->client->surname),
                        'charge_id' => $user->professional ? ($user->professional->charge_id) : 0,
                        'professional_id' => $user->professional ? ($user->professional->id) : 0,
                        'image' => $user->professional ? ($user->professional->image_url) : $user->client->client_image,
                        'client_id' => $user->client ? ($user->client->id) : 0,
                        'branch_id' => $user->professional->branches ? $branch['branch_id'] : 0,
                        'nameBranch' => $branch ? $branch['nameBranch'] : "",
                        'useTechnical' => $branch ? $branch['useTechnical'] : 0,
                        'token' => $token,
                    ], 200, [], JSON_NUMERIC_CHECK);
            } else {
                return response()->json([
                    "msg" => "Usuario no registrado"
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al loguearse'], 500);
        }
    }

    /**
     * Autentica a un usuario desde la web.
     *
     * @bodyParam email string required Email o nombre de usuario. Example: yasmany891230@gmail.com
     * @bodyParam password string required Contraseña. Example: mypass123
     * @bodyParam branch_id integer required ID de la sucursal seleccionada. Example: 5
     *
     * @response 200 {
     *   "id": 123,
     *   "userName": "yasmany89",
     *   "token": "1|abcdefghijklmnopqrstuvwxyz",
     *   "branch_id": 5,
     *   "nameBranch": "Centro",
     *   "permissions": ["manage_reservations, reservations"]
     * }
     * @response 401 {"msg": "Usuario no registrado"}
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 400);
            }
            $branch = [
                'branch_id' => 0,
                'nameBranch' => null,
                'useTechnical' => 0,
                'business_id' => 0,
                'nameBusiness' => '',
                'imageBusiness' => ''
            ];
            $user = [];
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();
            }    
            // Intentar la autenticación con el nombre de usuario
            elseif (Auth::attempt(['name' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();
            }
            if ($user) {
                    $business = Business::where('professional_id', $user->professional->id)->first();
                    if ($business == null && $user->professional->charge->name == 'Administrador') {
                        $business = Business::where('id', $user->professional->business_id)->first();
                    }

                    if ($user->professional->branches->where('id', $request->branch_id)->isNotEmpty()) { // Check if branches exist
                         if ($request->branch_id !== null  && strtolower($request->branch_id !== 'null')){
                        $branch = $user->professional->branches->where('id', $request->branch_id)->map(function ($branch) use ($request){

                            return [
                                'branch_id' => $branch->id,
                                'nameBranch' => $branch->name,
                                'useTechnical' => $branch->useTechnical,
                                'business_id' => $branch->business->id,
                                'nameBusiness' => $branch->business->name,
                                'imageBusiness' => $branch->business->image_url,
                                //'imageBusiness' => $branch ? $branch->image_data : $branch->business->image_url
                            ];
                        })->values()->first();}
                    }
                         
                    $professional = $user->professional;
                    $professional->state = 1;
                    $professional->save();
                
                    $token = $user->createToken('auth_token')->plainTextToken;
                    Auth::user();             
                    //return $branch;
                    return response()->json([
                        'id' => $user->id,
                        'userName' => $user->name,
                        'email' => $user->email,
                        'business_id' => $business ? $business->id : $branch['business_id'],
                        'nameBusiness' => $business ? $business->name : $branch['nameBusiness'],
                        'imageBusiness' => $business ? $business->image_url : $branch['imageBusiness'],
                        'charge' => $user->professional ? $user->professional->charge->name : null,
                        'name' => $user->professional ? ($user->professional->name) : ($user->client->name),
                        'charge_id' => $user->professional ? ($user->professional->charge_id) : 0,
                        'professional_id' => $user->professional ? ($user->professional->id) : 0,
                        'image' => $user->professional ? ($user->professional->image_url) : $user->client->client_image,
                        'client_id' => $user->client ? ($user->client->id) : 0,
                        'branch_id' => $branch ? $branch['branch_id'] : 0,
                        'nameBranch' => $branch ? $branch['nameBranch'] : "",
                        'useTechnical' => $branch ? $branch['useTechnical'] : 0,
                        'token' => $token,
                        'permissions' => $user->professional ? $user->professional->charge->permissions->map(function ($query){
                            return $query->name . ', ' . $query->module;
                        })->values()->all() : [],
                    ], 200, [], JSON_NUMERIC_CHECK);
            } else {
                return response()->json([
                    "msg" => "Usuario no registrado"
                ], 401);
            }
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al loguearse'], 500);
        }
    }

    /**
     * Obtiene el perfil del usuario autenticado.
     *
     * @authenticated
     *
     * @response 200 {"msg": "Acerca del perfil de usuario", "data": {"id": 123, "name": "yasmany89", "email": "yasmany891230@gmail.com"}}
     */
    public function userProfile()
    {
        try {
            return response()->json([
                "msg" => "Acerca del perfil de usuario",
                "data" => auth()->user()
            ]);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al ver los datos del usuario'], 500);
        }
    }

    /**
     * Genera un código QR con datos del profesional en una sucursal.
     *
     * @queryParam branch_id integer required ID de la sucursal. Example: 5
     * @queryParam email string required Nombre de usuario del profesional. Example: juan_barbero
     * @queryParam professional object optional Datos adicionales del profesional.
     *
     * @response 200 "<svg>...</svg>" (codificado en base64)
     * @response 400 {"msg": "Correo incorrecto o no es trabajador de esta sucursal"}
     */
    public function qrCode(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'email' => 'required',
                'professional' => 'nullable'
            ]);
      $professional_workplace = $data['professional'];
            $professional = Professional::whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->with(['user', 'charge', 'branches'])->whereHas('user', function ($query) use ($data){
            $query->where('name', $data['email']);
        })->first();
            if ($professional) {
                //$workplace_id = ProfessionalWorkPlace::where('professional_id', $professional->id)->whereDate('data', Carbon::now())->pluck('workplace_id');
                $datos = [
                    'id' => $professional->user->id,
                    'userName' => $professional->user->name,
                    'name' => $professional->name,
                    'email' => $professional->email,
                    'branch_id' => $data['branch_id'],
                    'professional_id' => $professional_workplace['professional_id'],
                    'workplace_id' => $professional_workplace['workplace_id'],
                    'places' => $professional_workplace['places']==0?[]:$professional_workplace['places'],
                    //'workplace_id' => $workplace_id,
                    'hora' => Carbon::now()->format('H:i')
                ];

                $qrCode = QrCode::format('svg')->size(100)->generate(json_encode($datos));
                $qrCodeBase64 = base64_encode($qrCode);
                return $qrCodeBase64;
            } else {
                return response()->json(['msg' => 'Correo incorrecto o no es trabajador de esta sucursal'], 400);
            }
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al ver los datos del usuario'], 500);
        }
    }

    /**
     * Genera un código QR para otros casos (sin datos de puesto).
     *
     * @queryParam branch_id integer required ID de la sucursal. Example: 5
     * @queryParam email string required Nombre de usuario del profesional. Example: juan_barbero
     * @queryParam professional integer optional ID del profesional. Example: 456
     *
     * @response 200 "<svg>...</svg>" (codificado en base64)
     * @response 400 {"msg": "Correo incorrecto o no es trabajador de esta sucursal"}
     */
    public function qrCodeOtros(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'email' => 'required',
                'professional' => 'nullable'
            ]);
      $professional = Professional::whereHas('branches', function ($query) use ($data) {
        $query->where('branch_id', $data['branch_id']);
        })->with(['user', 'charge', 'branches'])->whereHas('user', function ($query) use ($data){
        $query->where('name', $data['email']);
        })->first();
            if ($professional) {
                $datos = [
                    'id' => $professional->user->id,
                    'userName' => $professional->user->name,
                    'name' => $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname,
                    'email' => $professional->email,
                    'branch_id' => $data['branch_id'],
                    'professional_id' => $data['professional'],
                    'workplace_id' => 0,
                    'places' => [],
                    //'workplace_id' => $workplace_id,
                    'hora' => Carbon::now()->format('H:i')
                ];
                $qrCode = QrCode::format('svg')->size(100)->generate(json_encode($datos));
                $qrCodeBase64 = base64_encode($qrCode);
                return $qrCodeBase64;
            } else {
                return response()->json(['msg' => 'Correo incorrecto o no es trabajador de esta sucursal'], 400);
            }
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al ver los datos del usuario'], 500);
        }
    }

    /**
     * Cierra la sesión del usuario autenticado (web).
     *
     * @authenticated
     *
     * @response 200 {"msg": "Session cerrada correctamente"}
     */
    public function logout(Request $request)
    {
        try {
            $user = auth()->user();
            $professional = $user->professional;
                    $professional->state = 0;
                    $professional->save();
            auth()->user()->tokens()->delete();
            return response()->json([
                "msg" => "Session cerrada correctamente"
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al cerrar la session'], 500);
        }
    }

    /**
     * Cierra la sesión desde la app móvil y marca notificaciones como vistas.
     *
     * @queryParam branch_id integer required ID de la sucursal. Example: 5
     * @queryParam professional_id integer required ID del profesional. Example: 456
     *
     * @response 200 {"msg": "Session cerrada correctamente"}
     * @response 500 {"msg": "Error al cerrar la session"}
     */
    public function logout_phone(Request $request){   
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);            
        Notification::where('professional_id', $data['professional_id'])->where('branch_id', $data['branch_id'])->where('state', '!=', 1)->update(['state' => 1]);
            auth()->user()->tokens()->delete();
            return response()->json([
                "msg" => "Session cerrada correctamente"
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al cerrar la session'], 500);
        }     
    }
}
