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
        try{
        $validator = Validator::make($request->all(), [
            'name' => 'required|name|unique:users',
            'email' => 'required|email|unique:professionals',
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

        $user = User::with(['professional' => function ($query){
            $query->with(['branchServices' => function ($query){
                $query->with('branch')->get();
            }])->get();
        }])->where('email',$request->email)->orWhere('name', $request->email)->first();
        if (isset($user->id) ) {
            if(Hash::check($request->password, $user->password)) {
                $branch = $user->professional->branchServices->map(function ($branchService){
                    return[
                        'branch_id' => $branchService->branch->id,
                        'nameBranch' => $branchService->branch->name
                    ];
                })->first();
                return response()->json([
                    'id' => $user->id,
                    'userName' => $user->name,
                    'email' => $user->email,
                    'charge' => $user->professional->charge->name,
                    'nameProfessional' =>$user->professional->name .' '. $user->professional->surname .' '. $user->professional->second_surname, 
                    'charge_id' =>$user->professional->charge->id,
                    'professional_id' =>$user->professional->id,    
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
