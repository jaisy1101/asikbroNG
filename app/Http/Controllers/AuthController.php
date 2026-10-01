<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

            return back()->with(
                'error',
                'Username atau password salah'
            );

        }



        Auth::login($user);

        $request->session()->regenerate();



        return redirect('/');

    }

}