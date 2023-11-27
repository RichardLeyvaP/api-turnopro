<?php

namespace App\Http\Controllers;

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
    public function register(Request $request){
        try{
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed'

        ]);

        if ($validator->fails()) {
            return response()->json(['msg' => $validator->errors()->all()
            ],400);
        }
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        return response()->json(['msg' => "Usuario registrado correctamente!!!",
            'user' => $user
        ],201);
    }catch(\Throwable $th){
        return response()->json(['msg' => 'Error al registrarse'], 500);
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
    
            $user = User::with('client', 'professional')->has('client')->orHas('professional')->where('email',$request->email)->orWhere('name', $request->email)->first();
            if (isset($user->id) ) {
                if(Hash::check($request->password, $user->password)) {
                    if($user->professional){
                    $branch = $user->professional->branchServices->map(function ($branchService){
                        return[
                            'branch_id' => $branchService->branch->id,
                            'nameBranch' => $branchService->branch->name
                        ];
                    })->first();
                    if(!$branch){
                        $branch['branch_id'] = null;
                        $branch['nameBranch'] = null;
                    }}
                   return response()->json([
                        'id' => $user->id,
                        'userName' => $user->name,
                        'email' => $user->email,
                        'charge' => $user->professional ? ($user->professional->charge->name .' '. $user->professional->surname .' '. $user->professional->second_surname) : null,
                        'name' => $user->professional ? ($user->professional->name .' '. $user->professional->surname .' '. $user->professional->second_surname) : ($user->client->name .' '. $user->client->surname .' '. $user->client->second_surname), 
                        'charge_id' =>$user->professional ? ($user->professional->charge_id) : 0,
                        'professional_id' =>$user->professional ? ($user->professional->id) : 0,    
                        'client_id' =>$user->client ? ($user->client->id) : 0,    
                        'branch_id' =>$branch['branch_id'],
                        'nameBranch' =>$branch['nameBranch'],           
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
