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

                            <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">
                                Kami telah mengirimkan link verifikasi ke email
                                <strong class="text-dark">{{ auth()->user()->email }}</strong>.
                                Silakan cek inbox (atau folder spam) Anda.
                            </p>

                            @if (session('message'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="ri-checkbox-circle-line me-1"></i>
                                {{ session('message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            @endif

                            <div class="d-flex flex-column gap-3 align-items-center">
                                <form method="POST" action="{{ route('verification.send') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-refresh-line me-1"></i>
                                        Kirim Ulang Email Verifikasi
                                    </button>
                                </form>

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
                                Tidak menerima email? Pastikan email <strong>{{ auth()->user()->email }}</strong> benar,
                                lalu klik "Kirim Ulang".
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
</body>

</html>
