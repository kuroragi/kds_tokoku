<?php

namespace App\Http\Controllers;

use App\Mail\VerificationMail;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function show()
    {
        return view('register');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.regex' => 'Username hanya boleh huruf, angka, dan underscore.',
            'username.unique' => 'Username sudah digunakan.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        return DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);

            // Send verification email based on system setting
            self::sendVerificationEmail($user);

            Auth::login($user);
            request()->session()->regenerate();

            return redirect()->route('verification.notice')
                ->with('success', 'Akun berhasil dibuat! Silakan cek email Anda untuk verifikasi.');
        });
    }

    /**
     * Send verification email based on system setting (OTP or URL).
     */
    public static function sendVerificationEmail(User $user): void
    {
        $mode = SystemSetting::get('verification_method', 'otp');

        if ($mode === 'url') {
            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
            );

            $details = [
                'subject' => 'Verifikasi Email Anda - TOKOKU',
                'userName' => $user->name,
                'mode' => 'url',
                'verificationUrl' => $verificationUrl,
            ];
        } else {
            // Generate 6-digit OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiryMinutes = (int) SystemSetting::get('otp_expiry_minutes', '15');

            $user->update([
                'email_otp' => $otp,
                'email_otp_expires_at' => now()->addMinutes($expiryMinutes),
            ]);

            $details = [
                'subject' => 'Kode Verifikasi Anda - TOKOKU',
                'userName' => $user->name,
                'mode' => 'otp',
                'otpCode' => $otp,
            ];
        }

        Mail::to($user->email)->send(new VerificationMail($details));
    }
}
