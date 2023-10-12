<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function register(Request $request){
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
    }

    public function login(Request $request){
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
            "msg" => "Usuario logueado correctamente!!!",
            'token' => $user->createToken('auth_token')->plainTextToken
        ],200);
    }

    public function userProfile(){
        return response()->json([
            "msg" => "Acerca del perfil de usuario",
            "data" => auth()->user()
        ]);
    }

    public function logout(){
        auth()->user()->tokens()->delete();
        return response()->json([
            "msg" => "Session cerrada correctamente"
        ],200);
    }
}
