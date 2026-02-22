<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            Log::error('Google OAuth callback error', ['exception' => $e]);
            return redirect()->route('login')->with('error', 'Gagal login dengan Google. Silakan coba lagi.');
        }

        return DB::transaction(function () use ($googleUser) {
            // Cari user by google_id atau email
            $user = User::withTrashed()
                ->where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if ($user) {
                // User sudah ada — update google_id & avatar jika belum
                if ($user->trashed()) {
                    return redirect()->route('login')
                        ->with('error', 'Akun Anda telah dinonaktifkan. Hubungi administrator.');
                }

                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                ]);
            } else {
                // Buat user baru — no subscription yet
                $username = $this->generateUniqueUsername($googleUser->getEmail());

                $user = User::create([
                    'name' => $googleUser->getName(),
                    'username' => $username,
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => now(), // Google email is already verified
                    'password' => null,
                    'is_active' => true,
                ]);
            }

            Auth::login($user, true);
            request()->session()->regenerate();

            // Redirect based on onboarding state
            return $this->redirectBasedOnState($user);
        });
    }

    /**
     * Redirect user based on their onboarding state.
     */
    private function redirectBasedOnState(User $user)
    {
        // No subscription → landing page to select plan
        if (!$user->activeSubscription) {
            return redirect()->route('landing');
        }

        // Has subscription but no instance → setup instance
        if (!$user->business_unit_id) {
            return redirect()->route('onboarding.setup-instance');
        }

        // Fully onboarded → dashboard
        return redirect()->route('dashboard');
    }

    /**
     * Generate unique username from email.
     */
    private function generateUniqueUsername(string $email): string
    {
        $base = Str::before($email, '@');
        $base = Str::slug($base, '_');
        $username = $base;
        $counter = 1;

        while (User::withTrashed()->where('username', $username)->exists()) {
            $username = $base . '_' . $counter;
            $counter++;
        }

        return $username;
    }
}
