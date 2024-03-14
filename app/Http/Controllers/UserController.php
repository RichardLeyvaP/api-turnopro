<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Professional;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use GuzzleHttp;

class UserController extends Controller
{
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
                'surname' => 'required|max:50',
                'second_surname' => 'required|max:50',
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
            $client->name = $validator['name'];
            $client->surname = $validator['surname'];
            $client->second_surname = $validator['second_surname'];
            $client->email = $validator['email'];
            $client->phone = $validator['phone'];
            $client->user_id = $user->id;
            $client->client_image = 'comments/default_profile.jpg';
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
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'user' => 'required',
                'password' => 'required',
                'surname' => 'required|max:50',
                'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email|unique:professionals',
                'phone' => 'required|max:15',
                'charge_id' => 'required|numeric',
                'image_url' => 'nullable'
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
            /*$filename = "image/default.png";
            if ($request->hasFile('image_url')) {
                $filename = $request->file('image_url')->storeAs('professionals', $request->file('image_url')->getClientOriginalName(), 'public');
            }*/
            $professional = new Professional();
            $professional->name = $request['name'];
            $professional->surname = $request['surname'];
            $professional->second_surname = $request['second_surname'];
            $professional->email = $request['email'];
            $professional->phone = $request['phone'];
            $professional->charge_id = $request['charge_id'];
            $professional->user_id = $user->id;
            $professional->state = 0;
            $professional->save();

            $filename = "professionals/default_profile.jpg";
            if ($request->hasFile('image_url')) {
                $filename = $request->file('image_url')->storeAs('professionals',$professional->id.'.'.$request->file('image_url')->extension(),'public');
            }
            $professional->image_url = $filename;
            $professional->save();

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
                'branch_id' => null,
                'nameBranch' => null,
                'useTechnical' => 0
            ];
            Log::info("obtener el usuario");
            $user = User::where('email', $request->email)->orWhere('name', $request->email)->first();
            Log::info($user);
            if (isset($user->id)) {
                if (Hash::check($request->password, $user->password)) {
                    Log::info("Pass correct");
                    if ($user->professional->branches->isNotEmpty()) { // Check if branches exist
                        Log::info("Es professional");
                        $branch = $user->professional->branches->map(function ($branch) {
                            return [
                                'branch_id' => $branch->id,
                                'nameBranch' => $branch->name,
                                'useTechnical' => $branch->useTechnical
                            ];
                        })->first();
                    }
                    Log::info($user->professional->branchRules);
                    if ($user->professional->branchRules) {
                        $branchRules = Branch::find($branch['branch_id']);
                        $professional = Professional::find($user->professional->id);
                        $professionalRules = $professional->branchRules()->wherePivot('data', Carbon::now())->get();
                        Log::info($professionalRules);
                        if (count($professionalRules)) {
                            $branchRulesId = $branchRules->rules()->withPivot('id')->get()->map->pivot->pluck('id');
                            Log::info($branchRulesId);
                            $professional->branchRules()->attach($branchRulesId, ['data' => Carbon::now()->toDateString(), 'estado' => 3]);
                        }
                    }
                    return response()->json([
                        'id' => $user->id,
                        'userName' => $user->name,
                        'email' => $user->email,
                        'business_id' => $user->professional->business ? $user->professional->business->value('id') : 0,
                        'nameBusiness' => $user->professional->business ? $user->professional->business->value('name') : "",
                        'charge' => $user->professional ? $user->professional->charge->name : null,
                        'name' => $user->professional ? ($user->professional->name . ' ' . $user->professional->surname) : ($user->client->name . ' ' . $user->client->surname),
                        'charge_id' => $user->professional ? ($user->professional->charge_id) : 0,
                        'professional_id' => $user->professional ? ($user->professional->id) : 0,
                        'image' => $user->professional ? ($user->professional->image_url) : $user->client->client_image,
                        'client_id' => $user->client ? ($user->client->id) : 0,
                        'branch_id' => $user->professional->branches ? $branch['branch_id'] : 0,
                        'nameBranch' => $branch ? $branch['nameBranch'] : "",
                        'useTechnical' => $branch ? $branch['useTechnical'] : 0,
                        'token' => $user->createToken('auth_token')->plainTextToken,
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
                'email' => 'required|email',
            ]);
            $professional = Professional::whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->with(['user', 'charge', 'branches'])->where('email', $data['email'])->first();
            if ($professional) {
                $datos = [
                    'id' => $professional->user->id,
                    'userName' => $professional->user->name,
                    'name' => $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname,
                    'email' => $professional->email,
                    'branch_id' => $professional->branches->first()->value('id'),
                    'hora' => Carbon::now()->format('H:i:s')
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

    public function logout()
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
