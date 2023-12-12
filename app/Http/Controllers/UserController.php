<?php

namespace App\Http\Controllers;

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

class UserController extends Controller
{
 
    public function register_client(Request $request){
        try{
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
            return response()->json(['msg' => $validator->errors()->all()
            ],400);
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
        $client->save();

        return response()->json(['msg' => "Client registrado correctamente!!!",
            'user' => $user
        ],201);
    }catch(\Throwable $th){
        return response()->json(['msg' => $th->getMessage().'Error al registrarse'], 500);
    }
    }

    public function register_professional(Request $request){
        try{
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'user' => 'required',
            'password' => 'required|confirmed',
            'surname' => 'required|max:50',
            'second_surname' => 'required|max:50',
            'email' => 'required|max:50|email|unique:professionals',
            'phone' => 'required|max:15',
            'charge_id' => 'required|numeric',
            'image_url' => 'nullable'
        ]);
        if ($validator->fails()) {
            return response()->json(['msg' => $validator->errors()->all()
            ],400);
        }
        $user = User::create([
            'name' => $request->user,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);
        
        if ($request->hasFile('image_url')) {
            $filename = $request->file('image_url')->storeAs('professionals',$request->file('image_url')->getClientOriginalName(),'public');
        }
        $professional = new Professional();
        $professional->name = $request['name'];
        $professional->surname = $request['surname'];
        $professional->second_surname = $request['second_surname'];
        $professional->email = $request['email'];
        $professional->phone = $request['phone'];
        $professional->charge_id = $request['charge_id'];
        $professional->user_id = $user->id;
        $professional->image_url = $filename;
        $professional->state = 0;
        $professional->save();

        return response()->json(['msg' => "Professional registrado correctamente!!!",
            'user' => $user
        ],201);
    }catch(\Throwable $th){
        return response()->json(['msg' => $th->getMessage().'Error al registrarse'], 500);
    }
    }

    public function login(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'msg' => $validator->errors()->all()
                ],400);
            }
            $branch = [
                'branch_id' => null,
                'nameBranch' => null
            ];
            Log::info("obtener el usuario");
            $user = User::with('client', 'professional')->has('client')->orHas('professional')->where('email',$request->email)->orWhere('name', $request->email)->first();
            Log::info($user);
            if (isset($user->id) ) {
                if(Hash::check($request->password, $user->password)) {
                    Log::info("Pass correct");
                    if($user->professional){
                        Log::info("Es professional");
                    $branch = $user->professional->branches->map(function ($branch){
                        return[
                            'branch_id' => $branch->id,
                            'nameBranch' => $branch->name
                        ];
                    })->first();}
                    Log::info($branch);
                   return response()->json([
                        'id' => $user->id,
                        'userName' => $user->name,
                        'email' => $user->email,
                        'charge' => $user->professional ? $user->professional->charge->name : null,
                        'name' => $user->professional ? ($user->professional->name .' '. $user->professional->surname .' '. $user->professional->second_surname) : ($user->client->name .' '. $user->client->surname .' '. $user->client->second_surname), 
                        'charge_id' =>$user->professional ? ($user->professional->charge_id) : 0,
                        'professional_id' =>$user->professional ? ($user->professional->id) : 0,    
                        'client_id' =>$user->client ? ($user->client->id) : 0,    
                        'branch_id' =>$branch ? $branch['branch_id']: null,
                        'nameBranch' =>$branch ? $branch['nameBranch'] : null,           
                        'token' => $user->createToken('auth_token')->plainTextToken
                    ],200);
                }else{
                    return response()->json([
                      "msg" => "Contraseña incorrecta"
                   ], 404);
               }
            }else{
                return response()->json([
                    "msg" => "Usuario no registrado"
                ], 404);
            }
        }catch(\Throwable $th){
            return response()->json(['msg' => $th->getMessage().'Error al loguearse'], 500);
        }
    }

    public function userProfile(){
        try{
        return response()->json([
            "msg" => "Acerca del perfil de usuario",
            "data" => auth()->user()
        ]);
    }catch(\Throwable $th){
        return response()->json(['msg' => 'Error al ver los datos del usuario'], 500);
    }
    }

    public function qrCode(){
        try{
            $datos = [
                'id' => auth()->user()->id,
                'userName' => auth()->user()->name,
                'email' => auth()->user()->email,
                'hora' => Carbon::now()->format('H:i:s')
            ];
            $qrCode = QrCode::format('svg')->size(100)->generate(json_encode($datos));
            $qrCodeBase64 = base64_encode($qrCode);  
        return $qrCodeBase64;
    }catch(\Throwable $th){ 
        return response()->json(['msg' => 'Error al ver los datos del usuario'], 500);
    }
    }

    public function logout(){
        try{
        auth()->user()->tokens()->delete();
        return response()->json([
            "msg" => "Session cerrada correctamente"
        ],200);
    }catch(\Throwable $th){
        return response()->json(['msg' => 'Error al cerrar la session'], 500);
    }
    }
}
