<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Tokoku - Verifikasi Email</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/assets/images/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="/assets/js/config.js"></script>
    <link href="/assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-style" />
    <link href="/assets/css/icons.min.css" rel="stylesheet" type="text/css" />

    <style>
        body { font-family: 'Inter', sans-serif; }
        .brand-logo { font-weight: 800; font-size: 1.5rem; color: #3e60d5; text-decoration: none; }
        .brand-logo span { color: #0acf97; }
        .verify-icon { font-size: 4rem; color: #3e60d5; }
        .mail-animation {
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-12px); }
            60% { transform: translateY(-6px); }
        }
        .otp-inputs {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        .otp-inputs input {
            width: 52px;
            height: 58px;
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            border: 2px solid #e2e5e8;
            border-radius: 10px;
            outline: none;
            transition: all 0.2s;
            color: #3e60d5;
        }
        .otp-inputs input:focus {
            border-color: #3e60d5;
            box-shadow: 0 0 0 3px rgba(62, 96, 213, 0.15);
        }
    </style>
</head>

<body class="authentication-bg position-relative">
    <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5 position-relative">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xxl-6 col-lg-8">
                    <div class="card overflow-hidden">
                        <div class="p-4 p-sm-5 text-center">
                            <a href="{{ route('landing') }}" class="brand-logo d-block mb-4">TOKO<span>KU</span></a>

                            <div class="verify-icon mb-3 mail-animation">
                                <i class="ri-mail-send-line"></i>
                            </div>

                            <h4 class="fs-20 text-primary mb-2">Verifikasi Email Anda</h4>

                            @php
                                $verifyMode = \App\Models\SystemSetting::get('verification_method', 'otp');
                            @endphp

                            @if ($verifyMode === 'otp')
                                <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">
                                    Kami telah mengirimkan kode verifikasi <strong>6 digit</strong> ke email
                                    <strong class="text-dark">{{ auth()->user()->email }}</strong>.
                                    Masukkan kode tersebut di bawah ini.
                                </p>
                            @else
                                <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">
                                    Kami telah mengirimkan link verifikasi ke email
                                    <strong class="text-dark">{{ auth()->user()->email }}</strong>.
                                    Silakan cek inbox (atau folder spam) Anda.
                                </p>
                            @endif

                            @if (session('message'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="ri-checkbox-circle-line me-1"></i>
                                {{ session('message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            @endif

                            @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="ri-checkbox-circle-line me-1"></i>
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            @endif

                            @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="ri-error-warning-line me-1"></i>
                                {{ $errors->first() }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            @endif

                            @if ($verifyMode === 'otp')
                                <!-- OTP Input Form -->
                                <form method="POST" action="{{ route('verification.verify-otp') }}" class="mb-3">
                                    @csrf
                                    <div class="otp-inputs mb-3">
                                        <input type="text" name="otp[]" maxlength="1" inputmode="numeric" pattern="[0-9]" autofocus>
                                        <input type="text" name="otp[]" maxlength="1" inputmode="numeric" pattern="[0-9]">
                                        <input type="text" name="otp[]" maxlength="1" inputmode="numeric" pattern="[0-9]">
                                        <input type="text" name="otp[]" maxlength="1" inputmode="numeric" pattern="[0-9]">
                                        <input type="text" name="otp[]" maxlength="1" inputmode="numeric" pattern="[0-9]">
                                        <input type="text" name="otp[]" maxlength="1" inputmode="numeric" pattern="[0-9]">
                                    </div>
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="ri-checkbox-circle-line me-1"></i>
                                        Verifikasi
                                    </button>
                                </form>

                                <div class="mb-3">
                                    <form method="POST" action="{{ route('verification.send') }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary btn-sm">
                                            <i class="ri-refresh-line me-1"></i>
                                            Kirim Ulang Kode
                                        </button>
                                    </form>
                                </div>
                            @else
                                <!-- URL Verification Mode -->
                                <div class="d-flex flex-column gap-3 align-items-center">
                                    <form method="POST" action="{{ route('verification.send') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ri-refresh-line me-1"></i>
                                            Kirim Ulang Email Verifikasi
                                        </button>
                                    </form>
                                </div>
                            @endif

                            <div class="mt-3">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-link text-muted text-decoration-none">
                                        <i class="ri-logout-box-r-line me-1"></i>
                                        Logout
                                    </button>
                                </form>
                            </div>

                            <hr class="my-4">

                            <div class="text-muted small">
                                <i class="ri-information-line me-1"></i>
                                @if ($verifyMode === 'otp')
                                    Tidak menerima kode? Pastikan email <strong>{{ auth()->user()->email }}</strong> benar,
                                    lalu klik "Kirim Ulang Kode".
                                @else
                                    Tidak menerima email? Pastikan email <strong>{{ auth()->user()->email }}</strong> benar,
                                    lalu klik "Kirim Ulang".
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer footer-alt fw-medium">
        <span class="text-dark">
            <script>document.write(new Date().getFullYear())</script> © Tokoku ERP
        </span>
    </footer>

    <script src="/assets/js/vendor.min.js"></script>
    <script src="/assets/js/app.min.js"></script>

    @if ($verifyMode === 'otp')
    <script>
        // OTP auto-focus logic
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.otp-inputs input');
            inputs.forEach((input, index) => {
                input.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    if (this.value && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                });
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !this.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });
                // Handle paste
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
                    for (let i = 0; i < paste.length && (index + i) < inputs.length; i++) {
                        inputs[index + i].value = paste[i];
                    }
                    const nextIdx = Math.min(index + paste.length, inputs.length - 1);
                    inputs[nextIdx].focus();
                });
            });
        });
    </script>
    @endif
</body>

</html>
