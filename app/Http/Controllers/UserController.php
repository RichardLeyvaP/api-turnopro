<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Client;
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

            Log::info("entra a buscar los usuarios");
            return response()->json(['users' => User::all()], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los usuarios"], 500);
        }
    }

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
            //$client->surname = $request->surname;
            //$client->second_surname = $request->second_surname;
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
            $userName = User::where('name', $request->user)->first();
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
            /*$filename = "image/default.png";
            if ($request->hasFile('image_url')) {
                $filename = $request->file('image_url')->storeAs('professionals', $request->file('image_url')->getClientOriginalName(), 'public');
            }*/
            $professional = new Professional();
            $professional->name = $request['name'];
            //$professional->surname = $request['surname'];
            //$professional->second_surname = $request['second_surname'];
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
            //if(Hash::check($request->old_password, $user->password))
            //{
            $user->password = Hash::make($request->password);
            $user->save();
            return response()->json(['msg' => "Password modificada correctamente!!!"], 201);
            //}
            // else{
            //return response()->json(['msg' => "Password anterior incorrect0!!!"],400);
            //}
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al modificar la password'], 500);
        }
    }

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
            //if(Hash::check($request->old_password, $user->password))
            //{
                if(!$user){
                    return response()->json(['msg' => "Correo incorrecto!!!"], 404);
                }
            Log::info($user);
            $pass = Str::random(8);
            $user->password = Hash::make($pass);
            $user->save();
            $nombre = $user->professional->name.' '.$user->professional->surname.' '.$user->professional->second_surname;
            $usuario = $user->name;
            Log::info($nombre);
            Log::info($usuario);
            //AQUI SE LE ENVIA UN CORREO CON LA NUEVA CONTRASEÑA

            //$this->sendEmailService->emailRecuperarPass($client_email, $type,$client_name, $usser, $pass);
            $this->sendEmailService->emailRecuperarPass($request->email,$nombre, $usuario, $pass);
           // $this->sendEmailService->emailBoxClosure($mergedEmails, $reporte, $branch->business['name'], $branch['name'], $box['data'], $box['cashFound'], $box['existence'], $box['extraction'], $data['totalTip'], $data['totalProduct'], $data['totalService'], $data['totalCash'], $data['totalCreditCard'], $data['totalDebit'], $data['totalTransfer'], $data['totalOther'], $data['totalMount']);
            //SendEmailJob::dispatch()->emailRecuperarPass($request->email,$nombre, $usuario, $pass);
            /*$data = [
                'recover_password' => true, // Indica que es un correo de recuperación de contraseña
                'client_email' => $request->email,
                'client_name' => $nombre,
                'usser' => $usuario,
                'pass' => $pass,
            ];
            
            SendEmailJob::dispatch($data);*/


            return response()->json(['msg' => "Password modificada correctamente!!!"], 201);
            //}
            // else{
            //return response()->json(['msg' => "Password anterior incorrect0!!!"],400);
            //}
        } catch (TransportException $e) {
    
            return response()->json(['msg' => 'Password modificada correctamente.Error al enviar el correo electrónico '], 200);
        }
          catch (\Throwable $th) {
              Log::error($th);
            
              DB::rollback();
              return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }

    public function login_phone_get_branch(Request $request){
        try {
            Log::info("entra a buscar los usuarios");

            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 400);
            }
            $user = User::where('email', $request->email)->orWhere('name', $request->email)->first();
            if($user != null){
                if (Hash::check($request->password, $user->password)){
                    $branches = $user->professional->branches->map(function ($branch){
                        return [
                            'branch_id' => $branch->id,
                            'nameBranch' => $branch->name
                        ];
    
                    });
                }
                else{
                    $branches = [];
                }
            }else{
                $branches = [];  
            }
            
            return response()->json(['branches' => $branches], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

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
            Log::info("obtener el usuario");
            $user = User::where('email', $request->email)->orWhere('name', $request->email)->first();
            Log::info($user);
            if (isset($user->id)) {
                if (Hash::check($request->password, $user->password)) {
                    Log::info("Pass correct");
                    //return $user->professional->id;
                    $business = Business::where('id', $user->professional->business_id)->get();
                    if ($user->professional->branches->isNotEmpty()) { // Check if branches exist
                        Log::info("Es professional");
                        $branch = $user->professional->branches->where('id', $request->branch_id)->map(function ($branch) use ($request){
                            //if ($request->branch_id !== null  && strtolower($request->branch_id !== 'null')){
                                //if($request->branch_id == $branch->id){
                            return [
                                'branch_id' => $branch->id,
                                'nameBranch' => $branch->name,
                                'useTechnical' => $branch->useTechnical,
                                'business_id' => $branch->business->id,
                                'nameBusiness' => $branch->business->name
                            ];
                            //}
                        //}
                        })->values()->first();

                        Log::info($user->professional->branchRules);
                        $charge = $user->professional->charge->name;
                        if($charge == 'Barbero' || $charge == 'Tecnico' || $charge == 'Barbero y Encargado'){
                            //return $user->professional->branchRules->where('branch_id', $request->branch_id);
                        if ($user->professional->branchRules->where('branch_id', $request->branch_id)) {
                            $branchRules = Branch::find($request->branch_id);
                            $professional = Professional::find($user->professional->id);
                            $professionalRules = $professional->branchRules()
                            ->where('branch_id', $request->branch_id)
                            ->get()->map->pivot->where('data', Carbon::now()->toDateString());
                            Log::info($professionalRules);
                            if (count($professionalRules) == 0) {
                                $branchRulesId = $branchRules->rules()->withPivot('id')->get()->map->pivot->pluck('id');
                                Log::info($branchRulesId);
                                $professional->branchRules()->attach($branchRulesId, ['data' => Carbon::now()->toDateString(), 'estado' => 3]);
                            }
                        }
                        }
                    }
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
                        'permissions' => $user->professional ? $user->professional->charge->permissions->map(function ($query){
                            return $query->name . ', ' . $query->module;
                        })->values()->all() : [],
                    ], 200, [], JSON_NUMERIC_CHECK);
                } else {
                    return response()->json([
                        "msg" => "Contraseña incorrecta"
                    ], 404);
                }
            } else {
                return response()->json([
                    "msg" => "Usuario no registrado"
                ], 404);
            }
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al loguearse'], 500);
        }
    }

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
                'nameBusiness' => ''
            ];
            Log::info("obtener el usuario");
            $user = User::where('email', $request->email)->orWhere('name', $request->email)->first();
            Log::info($user);
            if (isset($user->id)) {
                if (Hash::check($request->password, $user->password)) {
                    Log::info("Pass correct");
                    $business = Business::where('professional_id', $user->professional->id)->first();
                    Log::info($business);

                    if ($user->professional->branches->where('id', $request->branch_id)->isNotEmpty()) { // Check if branches exist
                        Log::info("Es professional");
                        if ($request->branch_id !== null  && strtolower($request->branch_id !== 'null')){
                        $branch = $user->professional->branches->where('id', $request->branch_id)->map(function ($branch) use ($request){

                            return [
                                'branch_id' => $branch->id,
                                'nameBranch' => $branch->name,
                                'useTechnical' => $branch->useTechnical,
                                'business_id' => $branch->business->id,
                                'nameBusiness' => $branch->business->name
                            ];
                        })->values()->first();}
                    }
                    
                
                    $token = $user->createToken('auth_token')->plainTextToken;
                    Auth::user();                  
            
                    //return $branch;
                    return response()->json([
                        'id' => $user->id,
                        'userName' => $user->name,
                        'email' => $user->email,
                        'business_id' => $business ? $business->id : $branch['business_id'],
                        'nameBusiness' => $business ? $business->name : $branch['nameBusiness'],
                        'charge' => $user->professional ? $user->professional->charge->name : null,
                        'name' => $user->professional ? ($user->professional->name . ' ' . $user->professional->surname) : ($user->client->name . ' ' . $user->client->surname),
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
                        "msg" => "Contraseña incorrecta"
                    ], 401);
                }
            } else {
                return response()->json([
                    "msg" => "Usuario no registrado"
                ], 401);
            }
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al loguearse'], 500);
        }
    }

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

    public function qrCode(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'email' => 'required',
                'professional' => 'nullable'
            ]);
            //return $request->professionals;
            /*this.getProfesional = {
                professional_id: this.professionalId,
                workplace_id: newArrayPlaces[0],
                places:0,
      }*/
      Log::info('$request');
      Log::info($request);
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
    public function qrCodeOtros(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'email' => 'required',
                'professional' => 'nullable'
            ]);
            //return $request->professionals;
            /*this.getProfesional = {
                professional_id: this.professionalId,
                workplace_id: newArrayPlaces[0],
                places:0,
      }*/
      Log::info('$request');
      Log::info($request);
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

    public function logout(Request $request)
    {
        try {
            auth()->user()->tokens()->delete();
            return response()->json([
                "msg" => "Session cerrada correctamente"
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al cerrar la session'], 500);
        }
    }
}
