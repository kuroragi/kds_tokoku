<div>
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-double-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form wire:submit="save">
        {{-- General Settings --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0"><i class="ri-settings-3-line text-primary me-2"></i> Pengaturan Umum</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Aplikasi</label>
                            <input type="text" wire:model="appName" class="form-control @error('appName') is-invalid @enderror">
                            @error('appName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Contact / WhatsApp Settings --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0"><i class="ri-whatsapp-line text-success me-2"></i> Pengaturan WhatsApp Admin</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nomor WhatsApp Admin</label>
                            <div class="input-group">
                                <span class="input-group-text">+</span>
                                <input type="text" wire:model="adminWhatsapp"
                                    class="form-control @error('adminWhatsapp') is-invalid @enderror"
                                    placeholder="628xxxxxxxxxx">
                            </div>
                            @error('adminWhatsapp') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            <div class="text-muted small mt-1">
                                <i class="ri-information-line me-1"></i>
                                Format: kode negara + nomor tanpa + (contoh: 6281234567890). Digunakan untuk konfirmasi pembayaran langganan.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Auth Settings --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0"><i class="ri-shield-check-line text-primary me-2"></i> Pengaturan Autentikasi</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Metode Verifikasi Email</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" wire:model="verificationMethod" value="otp" id="methodOtp">
                                    <label class="form-check-label" for="methodOtp">
                                        <i class="ri-key-line me-1"></i> Kode OTP (6 Digit)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" wire:model="verificationMethod" value="url" id="methodUrl">
                                    <label class="form-check-label" for="methodUrl">
                                        <i class="ri-link me-1"></i> Link URL Verifikasi
                                    </label>
                                </div>
                            </div>
                            @error('verificationMethod') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            <div class="text-muted small mt-2">
                                @if($verificationMethod === 'otp')
                                    <i class="ri-information-line me-1"></i> Pengguna akan menerima kode 6 digit via email untuk verifikasi. Cocok untuk semua environment.
                                @else
                                    <i class="ri-alert-line text-warning me-1"></i> Pengguna akan menerima link verifikasi via email. Pastikan <code>APP_URL</code> menggunakan domain valid (bukan .test/.local).
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3" x-data x-show="$wire.verificationMethod === 'otp'" x-transition>
                            <label class="form-label fw-semibold">Durasi Kedaluwarsa OTP (menit)</label>
                            <input type="number" wire:model="otpExpiryMinutes" class="form-control @error('otpExpiryMinutes') is-invalid @enderror" min="5" max="60">
                            @error('otpExpiryMinutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="text-muted small mt-1">
                                <i class="ri-time-line me-1"></i> Kode OTP akan kedaluwarsa setelah {{ $otpExpiryMinutes }} menit.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Save Button --}}
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary px-4">
                <span wire:loading.remove wire:target="save">
                    <i class="ri-save-line me-1"></i> Simpan Pengaturan
                </span>
                <span wire:loading wire:target="save">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyimpan...
                </span>
            </button>
        </div>
    </form>
</div>
