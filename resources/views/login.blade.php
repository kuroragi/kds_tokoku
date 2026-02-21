<!DOCTYPE html>
<html lang="en">


<!-- Mirrored from techzaa.in/velonic/layouts/auth-login.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 09 Sep 2025 17:06:03 GMT -->

<head>
    <meta charset="utf-8" />
    <title>Tokoku - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Login ke Tokoku ERP — Sistem ERP untuk UMKM Indonesia." name="description" />
    <meta content="Tokoku" name="author" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="/assets/images/favicon.ico">

    <!-- Theme Config Js -->
    <script src="/assets/js/config.js"></script>

    <!-- App css -->
    <link href="/assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-style" />

    <!-- Icons css -->
    <link href="/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
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
                                        <div class="text-center mb-2">
                                            <a href="{{ route('landing') }}" style="font-weight: 800; font-size: 1.5rem; color: #3e60d5; text-decoration: none;">TOKO<span style="color: #0acf97;">KU</span></a>
                                        </div>
                                        <h4 class="fs-20 text-primary">Sign In</h4>

                                        @if (session()->has('error'))
                                        <div class="alert alert-danger" role="alert">
                                            {{ session('error') }}
                                        </div>
                                        @endif

                                        <!-- form -->
                                        <form action="{{ route('authenticate') }}" method="post">
                                            @csrf
                                            <div class="mb-3">
                                                <label for="login" class="form-label">Username / Alamat Email</label>
                                                @if ($errors->has('login'))
                                                <div class="alert alert-danger">
                                                    {{ $errors->first('login') }}
                                                </div>
                                                @endif
                                                <input class="form-control" type="text" name="login" id="login" required
                                                    placeholder="Masukkan Username / Email" value="{{ old('login') }}">
                                            </div>
                                            <div class="mb-3">
                                                <a href="auth-forgotpw.html" class="text-muted float-end"><small>Lupa
                                                        password?</small></a>
                                                <label for="password" class="form-label">Password</label>
                                                <input class="form-control" type="password" name="password" required
                                                    id="password" placeholder="Masukkan password anda">
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input type="checkbox" name="remember" value="1" class="form-check-input"
                                                        id="checkbox-signin">
                                                    <label class="form-check-label" for="checkbox-signin">Ingat
                                                        Saya</label>
                                                </div>
                                            </div>
                                            <div class="mb-0 text-start">
                                                <button class="btn btn-soft-primary w-100" type="submit"><i
                                                        class="ri-login-circle-fill me-1"></i> <span class="fw-bold">Log
                                                        In</span> </button>
                                            </div>

                                            {{-- Google Login --}}
                                            <div class="text-center mt-4">
                                                <p class="text-muted fs-14 mb-2">Atau masuk dengan</p>
                                                <a href="{{ route('auth.google') }}" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2" style="padding: 10px;">
                                                    <svg width="20" height="20" viewBox="0 0 24 24">
                                                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                                                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                                    </svg>
                                                    Google
                                                </a>
                                            </div>
                                        </form>
                                        <!-- end form-->
                                    </div>
                                </div>
                            </div> <!-- end col -->
                        </div>
                    </div>
                </div>
                <!-- end row -->
            </div>
            <div class="row">
                <div class="col-12 text-center">
                    <p class="text-dark-emphasis">Belum punya akun? <a href="{{ route('register') }}"
                            class="text-dark fw-bold ms-1 link-offset-3 text-decoration-underline"><b>Daftar Gratis</b></a>
                    </p>
                </div>
            </div>
            <!-- end row -->
        </div>
        <!-- end container -->
    </div>
    <!-- end page -->

    <footer class="footer footer-alt fw-medium">
        <span class="text-dark">
            <script>
                document.write(new Date().getFullYear())
            </script> © Tokoku ERP
        </span>
    </footer>
    <!-- Vendor js -->
    <script src="/assets/js/vendor.min.js"></script>

    <!-- App js -->
    <script src="/assets/js/app.min.js"></script>

</body>


</html>