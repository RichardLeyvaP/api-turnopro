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
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'msg' => $validator->errors()->all()
            ],400);
        }
        if (!Auth::attempt($request->only('email','password'))) {
            return response()->json([
                "msg" => "Usuario no registrado"
            ], 401 );
        }

        $user = User::where('email',$request->email)->first();
        /*if (!$user->email_verified_at) {
            return response()->json([
                "msg" => "El correo no ha sido verificado"
            ], 401 );
        }*/
        return response()->json([
            'msg' => "Usuario logueado correctamente!!!",
            'token' => $user->createToken('auth_token')->plainTextToken,
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email
        ],200);
    }catch(\Throwable $th){
        return response()->json(['msg' => 'Error al loguearse'], 500);
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
