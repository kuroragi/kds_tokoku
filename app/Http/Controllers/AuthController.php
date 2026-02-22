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

        $user = Auth::user();

        // Superadmin bypasses all onboarding checks
        if ($user->hasRole('superadmin')) {
            return redirect()->intended('dashboard');
        }

        // Redirect based on onboarding state
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if (!$user->activeSubscription) {
            return redirect()->route('landing');
        }

        if (!$user->business_unit_id) {
            return redirect()->route('onboarding.setup-instance');
        }

        return redirect()->intended('dashboard');
    }

    public function logout(AuthService $authService)
    {
        $authService->logout();    

        return redirect()->route('login');
    }
}
