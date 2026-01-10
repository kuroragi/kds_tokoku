<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function authenticate(Request $request)
    {
        $request->validate([
            'login' => 'required',
            'password' => 'required'
        ]);

        $credentials = $request->only('login', 'password');
        $remember = $request->filled('remember');

        // cek apakah input berupa email atau username
        $fieldType = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        if (Auth::attempt([$fieldType => $credentials['login'], 'password' => $credentials['password']], $remember)) {
            $request->session()->regenerate();
            return redirect()->intended('dashboard'); // sesuaikan route
        }

        return back()->with('error', 'Credential yang anda masukkan tidak cocok!');
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        Request()->session()->regenerateToken();
        return redirect()->route('login');
    }
}
