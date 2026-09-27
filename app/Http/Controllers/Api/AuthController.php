<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;


class AuthController extends Controller
{
    

    public function login(Request $request)
    {

        $request->validate([
            'username'=>'required',
            'password'=>'required'
        ]);


        $user = User::where(
            'username',
            $request->username
        )->first();



        if(
            !$user ||
            !Hash::check(
                $request->password,
                $user->password
            )
        ){

            return response()->json([
                'message'=>'Email atau password salah'
            ],401);

        }



        $token = $user
            ->createToken('asikbro-token')
            ->plainTextToken;



        return response()->json([

            'message'=>'Login berhasil',

            'token'=>$token,

            'user'=>[
                'id'=>$user->id,
                'username'=>$user->username,
                'name'=>$user->name,

                'role'=>[
                    'id'=>$user->role->id,
                    'name'=>$user->role->name
                ],

                'wilayah'=>[
                    'id'=>$user->wilayah->id,
                    'nama'=>$user->wilayah->nama
                ]

            ]

        ]);

    }


}