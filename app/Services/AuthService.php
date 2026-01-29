<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function login(string $login, string $password, bool $remember = false): void
    {
        try{
            $field = $this->detectLoginField($login);

            if(!Auth::attempt([$field => $login, 'password' => $password], $remember)){
                throw ValidationException::withMessages([
                    'login' => 'Credential yang anda masukkan tidak cocok!',
                ]);
            }

            request()->session()->regenerate();

        } catch (QueryException $e) {
            // database error (connection refused, timeout, dll)
            Log::error('Login failed: database error', [
                'exception' => $e,
            ]);

            throw ValidationException::withMessages([
                'login' => 'Sistem sedang bermasalah. Silakan coba beberapa saat lagi.',
            ]);

        } catch (Throwable $e) {
            // error tak terduga
            Log::critical('Unexpected login error', [
                'exception' => $e,
            ]);

            throw ValidationException::withMessages([
                'login' => 'Terjadi kesalahan sistem. Silakan hubungi administrator.',
            ]);
        }
    }

    public function detectLoginField(string $login): string
    {
        return filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    }

    public function logout(): void
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
