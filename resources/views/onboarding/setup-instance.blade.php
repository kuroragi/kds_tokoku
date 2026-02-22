<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Tokoku - Buat Instansi Bisnis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/assets/images/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="/assets/js/config.js"></script>
    <link href="/assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-style" />
    <link href="/assets/css/icons.min.css" rel="stylesheet" type="text/css" />

    <style>
        body { font-family: 'Inter', sans-serif; }
        .brand-logo { font-weight: 800; font-size: 1.5rem; color: #3e60d5; text-decoration: none; }
        .brand-logo span { color: #0acf97; }
        .setup-header {
            background: linear-gradient(135deg, #3e60d5 0%, #6c5dd3 100%);
            color: #fff;
            border-radius: 16px 16px 0 0;
            padding: 30px;
        }
        .plan-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 30px;
        }
        .step {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #94a3b8;
        }
        .step.completed {
            color: #0acf97;
        }
        .step.active {
            color: #3e60d5;
            font-weight: 600;
        }
        .step-number {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            border: 2px solid #e2e8f0;
            color: #94a3b8;
        }
        .step.completed .step-number {
            background: #0acf97;
            border-color: #0acf97;
            color: #fff;
        }
        .step.active .step-number {
            background: #3e60d5;
            border-color: #3e60d5;
            color: #fff;
        }
        .step-line {
            width: 40px;
            height: 2px;
            background: #e2e8f0;
            align-self: center;
        }
        .step-line.completed {
            background: #0acf97;
        }
    </style>
</head>

<body class="authentication-bg position-relative">
    <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5 position-relative">
        <div class="container">
            <div class="text-center mb-4">
                <a href="{{ route('landing') }}" class="brand-logo">TOKO<span>KU</span></a>
            </div>

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step completed">
                    <span class="step-number"><i class="ri-check-line"></i></span>
                    <span>Daftar</span>
                </div>
                <div class="step-line completed"></div>
                <div class="step completed">
                    <span class="step-number"><i class="ri-check-line"></i></span>
                    <span>Verifikasi</span>
                </div>
                <div class="step-line completed"></div>
                <div class="step completed">
                    <span class="step-number"><i class="ri-check-line"></i></span>
                    <span>Pilih Paket</span>
                </div>
                <div class="step-line"></div>
                <div class="step active">
                    <span class="step-number">4</span>
                    <span>Buat Instansi</span>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="card overflow-hidden shadow-sm border-0">
                        <div class="setup-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-1 text-white">Buat Instansi Bisnis</h4>
                                    <p class="mb-0 opacity-75 small">Langkah terakhir sebelum memulai!</p>
                                </div>
                                <div class="plan-badge">
                                    <i class="ri-medal-line"></i>
                                    Paket {{ $plan->name }}
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="ri-checkbox-circle-line me-1"></i> {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            @endif

                            @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="ri-close-circle-line me-1"></i> {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            @endif

                            <div class="alert alert-soft-info mb-4">
                                <div class="d-flex gap-2">
                                    <i class="ri-information-line fs-5 mt-1"></i>
                                    <div>
                                        <strong>Info Paket {{ $plan->name }}:</strong>
                                        Maksimal <strong>{{ $plan->max_users === 0 ? 'Unlimited' : $plan->max_users }}</strong> user
                                        dan <strong>{{ $plan->max_business_units === 0 ? 'Unlimited' : $plan->max_business_units }}</strong> unit usaha.
                                    </div>
                                </div>
                            </div>

                            <form action="{{ route('onboarding.store-instance') }}" method="POST">
                                @csrf

                                <div class="mb-3">
                                    <label for="name" class="form-label fw-semibold">
                                        Nama Bisnis / Instansi <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" required value="{{ old('name') }}"
                                        placeholder="Contoh: Toko Sejahtera, CV Maju Bersama">
                                    @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="business_type" class="form-label fw-semibold">
                                        Jenis Bisnis <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('business_type') is-invalid @enderror"
                                        id="business_type" name="business_type" required>
                                        <option value="">— Pilih Jenis Bisnis —</option>
                                        <option value="Retail / Toko" {{ old('business_type') === 'Retail / Toko' ? 'selected' : '' }}>Retail / Toko</option>
                                        <option value="Grosir / Distributor" {{ old('business_type') === 'Grosir / Distributor' ? 'selected' : '' }}>Grosir / Distributor</option>
                                        <option value="Jasa / Service" {{ old('business_type') === 'Jasa / Service' ? 'selected' : '' }}>Jasa / Service</option>
                                        <option value="Manufaktur / Produksi" {{ old('business_type') === 'Manufaktur / Produksi' ? 'selected' : '' }}>Manufaktur / Produksi</option>
                                        <option value="F&B / Kuliner" {{ old('business_type') === 'F&B / Kuliner' ? 'selected' : '' }}>F&B / Kuliner</option>
                                        <option value="Kontraktor" {{ old('business_type') === 'Kontraktor' ? 'selected' : '' }}>Kontraktor</option>
                                        <option value="Pertanian / Peternakan" {{ old('business_type') === 'Pertanian / Peternakan' ? 'selected' : '' }}>Pertanian / Peternakan</option>
                                        <option value="Lainnya" {{ old('business_type') === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                                    </select>
                                    @error('business_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label fw-semibold">No. Telepon</label>
                                        <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                            id="phone" name="phone" value="{{ old('phone') }}"
                                            placeholder="08xx-xxxx-xxxx">
                                        @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="city" class="form-label fw-semibold">Kota</label>
                                        <input type="text" class="form-control @error('city') is-invalid @enderror"
                                            id="city" name="city" value="{{ old('city') }}"
                                            placeholder="Contoh: Bandung">
                                        @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="province" class="form-label fw-semibold">Provinsi</label>
                                        <input type="text" class="form-control @error('province') is-invalid @enderror"
                                            id="province" name="province" value="{{ old('province') }}"
                                            placeholder="Contoh: Jawa Barat">
                                        @error('province')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="address" class="form-label fw-semibold">Alamat</label>
                                        <input type="text" class="form-control @error('address') is-invalid @enderror"
                                            id="address" name="address" value="{{ old('address') }}"
                                            placeholder="Alamat lengkap (opsional)">
                                        @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <hr class="my-3">

                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="ri-building-2-line me-1"></i>
                                    Buat Instansi & Mulai Gunakan Tokoku
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-link text-muted text-decoration-none small">
                                <i class="ri-logout-box-r-line me-1"></i> Logout
                            </button>
                        </form>
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
