<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function show()
    {
        return view('register');
    }

    public function store(Request $request, SubscriptionService $subscriptionService)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'plan' => ['nullable', 'string', 'exists:plans,slug'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        return DB::transaction(function () use ($validated, $subscriptionService) {
            // Generate unique username
            $baseUsername = Str::slug(Str::before($validated['email'], '@'), '_');
            $username = $baseUsername;
            $counter = 1;
            while (User::withTrashed()->where('username', $username)->exists()) {
                $username = $baseUsername . '_' . $counter++;
            }

            $user = User::create([
                'name' => $validated['name'],
                'username' => $username,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);

            // Determine plan
            $planSlug = $validated['plan'] ?? 'trial';
            $plan = Plan::where('slug', $planSlug)->first();

            if ($plan) {
                // For paid plans, still start with trial until payment
                $actualPlan = $plan->price > 0
                    ? Plan::where('slug', 'trial')->first() ?? $plan
                    : $plan;

                $subscriptionService->createSubscription($user, $actualPlan);
            }

            Auth::login($user, true);
            request()->session()->regenerate();

            return redirect()->route('dashboard')
                ->with('success', 'Selamat datang di Tokoku ERP! Akun Anda telah berhasil dibuat.');
        });
    }
}
