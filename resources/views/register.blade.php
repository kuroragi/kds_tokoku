<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Tokoku - Daftar Akun</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Daftar akun Tokoku ERP — gratis 14 hari." name="description" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="/assets/images/favicon.ico">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Theme Config Js -->
    <script src="/assets/js/config.js"></script>

    <!-- App css -->
    <link href="/assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-style" />
    <link href="/assets/css/icons.min.css" rel="stylesheet" type="text/css" />

    <style>
        body { font-family: 'Inter', sans-serif; }
        .brand-logo { font-weight: 800; font-size: 1.5rem; color: #3e60d5; text-decoration: none; }
        .brand-logo span { color: #0acf97; }
        .google-icon { width: 20px; height: 20px; }
        .btn-google {
            background: #fff; color: #333; border: 1px solid #dadce0;
            font-weight: 500; display: inline-flex; align-items: center;
            gap: 10px; padding: 10px 24px; border-radius: 8px; transition: all 0.2s;
        }
        .btn-google:hover { background: #f8f9fa; box-shadow: 0 4px 12px rgba(0,0,0,0.1); color: #333; }
        .divider-text { display: flex; align-items: center; gap: 16px; margin: 20px 0; color: #94a3b8; font-size: 0.85rem; }
        .divider-text::before, .divider-text::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }
        .selected-plan-badge { background: #f0f4ff; border: 1px solid #3e60d5; border-radius: 8px; padding: 10px 16px; }
    </style>
</head>

<body class="authentication-bg position-relative">
    <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5 position-relative">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xxl-8 col-lg-10">
                    <div class="card overflow-hidden">
                        <div class="row g-0">
                            <div class="col-lg-6 d-none d-lg-block p-2">
                                <img src="/assets/images/auth-img.jpg" alt="" class="img-fluid rounded h-100">
                            </div>
                            <div class="col-lg-6">
                                <div class="d-flex flex-column h-100">
                                    <div class="p-4 my-auto">
                                        <div class="text-center mb-3">
                                            <a href="{{ route('landing') }}" class="brand-logo">TOKO<span>KU</span></a>
                                        </div>

                                        <h4 class="fs-20 text-primary">Buat Akun Baru</h4>
                                        <p class="text-muted mb-3">Daftar gratis dan mulai kelola bisnis Anda.</p>

                                        @if (session()->has('error'))
                                        <div class="alert alert-danger" role="alert">
                                            {{ session('error') }}
                                        </div>
                                        @endif

                                        @if (session()->has('success'))
                                        <div class="alert alert-success" role="alert">
                                            {{ session('success') }}
                                        </div>
                                        @endif

                                        <!-- Google Login -->
                                        <a href="{{ route('auth.google') }}" class="btn-google w-100 text-center d-flex justify-content-center">
                                            <svg class="google-icon" viewBox="0 0 24 24">
                                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                            </svg>
                                            Daftar dengan Google
                                        </a>

                                        <div class="divider-text">atau daftar dengan email</div>

                                        <!-- Register form -->
                                        <form action="{{ route('register.store') }}" method="POST">
                                            @csrf

                                            <div class="mb-3">
                                                <label for="name" class="form-label">Nama Lengkap</label>
                                                <input class="form-control @error('name') is-invalid @enderror"
                                                    type="text" name="name" id="name" required
                                                    placeholder="Masukkan nama lengkap" value="{{ old('name') }}">
                                                @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="username" class="form-label">Username</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="ri-at-line"></i></span>
                                                    <input class="form-control @error('username') is-invalid @enderror"
                                                        type="text" name="username" id="username" required
                                                        placeholder="username_anda" value="{{ old('username') }}"
                                                        pattern="[a-zA-Z0-9_]+" title="Hanya huruf, angka, dan underscore">
                                                    @error('username')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <small class="text-muted">Hanya huruf, angka, dan underscore (_)</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="email" class="form-label">Alamat Email</label>
                                                <input class="form-control @error('email') is-invalid @enderror"
                                                    type="email" name="email" id="email" required
                                                    placeholder="contoh@email.com" value="{{ old('email') }}">
                                                @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="password" class="form-label">Password</label>
                                                <input class="form-control @error('password') is-invalid @enderror"
                                                    type="password" name="password" id="password" required
                                                    placeholder="Minimal 8 karakter">
                                                @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                                                <input class="form-control" type="password" name="password_confirmation"
                                                    id="password_confirmation" required placeholder="Ulangi password">
                                            </div>

                                            <div class="mb-0 text-start">
                                                <button class="btn btn-primary w-100" type="submit">
                                                    <i class="ri-user-add-line me-1"></i>
                                                    <span class="fw-bold">Daftar Sekarang</span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 text-center">
                    <p class="text-dark-emphasis">
                        Sudah punya akun?
                        <a href="{{ route('login') }}" class="text-dark fw-bold ms-1 link-offset-3 text-decoration-underline">
                            <b>Login</b>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer footer-alt fw-medium">
        <span class="text-dark">
            <script>document.write(new Date().getFullYear())</script> © Tokoku ERP
        </span>
    </footer>

    <!-- Vendor js -->
    <script src="/assets/js/vendor.min.js"></script>
    <!-- App js -->
    <script src="/assets/js/app.min.js"></script>
</body>

</html>
