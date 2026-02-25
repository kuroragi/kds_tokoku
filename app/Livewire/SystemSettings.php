<?php

namespace App\Livewire;

use App\Models\SystemSetting;
use Livewire\Component;

class SystemSettings extends Component
{
    // Auth settings
    public string $verificationMethod = 'otp';
    public string $otpExpiryMinutes = '15';

    // General settings
    public string $appName = 'TOKOKU';

    // Contact / WhatsApp settings
    public string $adminWhatsapp = '';

    public bool $saved = false;

    public function mount(): void
    {
        $this->verificationMethod = SystemSetting::get('verification_method', 'otp');
        $this->otpExpiryMinutes = SystemSetting::get('otp_expiry_minutes', '15');
        $this->appName = SystemSetting::get('app_name', 'TOKOKU');
        $this->adminWhatsapp = SystemSetting::get('admin_whatsapp', '');
    }

    public function save(): void
    {
        $this->validate([
            'verificationMethod' => 'required|in:otp,url',
            'otpExpiryMinutes' => 'required|integer|min:5|max:60',
            'appName' => 'required|string|max:100',
            'adminWhatsapp' => 'nullable|string|max:20',
        ]);

        SystemSetting::set('verification_method', $this->verificationMethod);
        SystemSetting::set('otp_expiry_minutes', $this->otpExpiryMinutes);
        SystemSetting::set('app_name', $this->appName);
        SystemSetting::set('admin_whatsapp', $this->adminWhatsapp, 'contact', 'Nomor WhatsApp admin untuk konfirmasi pembayaran');

        $this->saved = true;

        session()->flash('success', 'Pengaturan berhasil disimpan.');
    }

    public function render()
    {
        return view('livewire.system-settings');
    }
}
