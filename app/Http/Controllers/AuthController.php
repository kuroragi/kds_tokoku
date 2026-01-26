<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function authenticate(LoginRequest $request, AuthService $authService)
    {
        $authService->login(
            $request->credentials()['login'],
            $request->credentials()['password'],
            $request->remember()
        );

        return redirect()->intended('dashboard');

        // $request->validate([
        //     'login' => 'required',
        //     'password' => 'required'
        // ]);

        // $credentials = $request->only('login', 'password');
        // $remember = $request->filled('remember');

        // // cek apakah input berupa email atau username
        // $fieldType = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        // if (Auth::attempt([$fieldType => $credentials['login'], 'password' => $credentials['password']], $remember)) {
        //     $request->session()->regenerate();
        //     return redirect()->intended('dashboard'); // sesuaikan route
        // }

        // return back()->with('error', 'Credential yang anda masukkan tidak cocok!');
    }

    public function logout(AuthService $authService)
    {
        $authService->logout();    

        return redirect()->route('login');
    }
}
