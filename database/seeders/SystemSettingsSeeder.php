<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'verification_method',
                'value' => 'otp',
                'group' => 'auth',
                'description' => 'Metode verifikasi email: otp atau url',
            ],
            [
                'key' => 'app_name',
                'value' => 'TOKOKU',
                'group' => 'general',
                'description' => 'Nama aplikasi',
            ],
            [
                'key' => 'otp_expiry_minutes',
                'value' => '15',
                'group' => 'auth',
                'description' => 'Durasi kedaluwarsa OTP dalam menit',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
