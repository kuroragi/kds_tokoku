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
    }

    public function logout(AuthService $authService)
    {
        $authService->logout();    

        return redirect()->route('login');
    }
}
