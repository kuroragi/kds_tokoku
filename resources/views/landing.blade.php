<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tokoku ERP — Sistem ERP Modern untuk UMKM Indonesia</title>
    <meta name="description" content="Tokoku ERP — Solusi ERP lengkap dan terjangkau untuk UMKM Indonesia. Akuntansi, Pembelian, Penjualan, Payroll, Aset, Pajak dalam satu platform." />

    <!-- Favicon -->
    <link rel="shortcut icon" href="/assets/images/favicon.ico">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="/assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-style" />
    <link href="/assets/css/icons.min.css" rel="stylesheet" type="text/css" />

    <style>
        :root {
            --primary: #3e60d5;
            --primary-dark: #2c4ab8;
            --secondary: #6c757d;
            --success: #0acf97;
            --gradient-start: #3e60d5;
            --gradient-end: #6c5dd3;
        }

        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        /* ── Navbar ── */
        .landing-navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.06);
            padding: 12px 0;
            transition: all 0.3s ease;
        }

        .landing-navbar .nav-link {
            font-weight: 500;
            color: #495057;
            padding: 8px 16px;
            transition: color 0.2s;
        }

        .landing-navbar .nav-link:hover,
        .landing-navbar .nav-link.active {
            color: var(--primary);
        }

        .brand-logo {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary);
            text-decoration: none;
        }

        .brand-logo span {
            color: var(--success);
        }

        /* ── Hero ── */
        .hero-section {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8ecff 50%, #f5f0ff 100%);
            min-height: 90vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 60%;
            height: 200%;
            background: radial-gradient(circle, rgba(62, 96, 213, 0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-title {
            font-size: 3.2rem;
            font-weight: 800;
            line-height: 1.2;
            color: #1e293b;
        }

        .hero-title .highlight {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.15rem;
            color: #64748b;
            line-height: 1.7;
            max-width: 560px;
        }

        .hero-image {
            max-width: 100%;
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(62, 96, 213, 0.15);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(62, 96, 213, 0.1);
            color: var(--primary);
            font-weight: 600;
            font-size: 0.85rem;
            padding: 6px 16px;
            border-radius: 50px;
            margin-bottom: 20px;
        }

        /* ── Section Common ── */
        .section-padding {
            padding: 80px 0;
        }

        .section-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
        }

        .section-subtitle {
            font-size: 1.05rem;
            color: #64748b;
            max-width: 640px;
            margin: 0 auto;
        }

        /* ── Features ── */
        .feature-card {
            background: #fff;
            border-radius: 16px;
            padding: 32px 24px;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            border-color: var(--primary);
        }

        .feature-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
        }

        .feature-card h5 {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .feature-card p {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        /* ── Stats ── */
        .stats-section {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: #fff;
        }

        .stat-item h3 {
            font-size: 2.5rem;
            font-weight: 800;
        }

        .stat-item p {
            font-size: 1rem;
            opacity: 0.85;
        }

        /* ── Pricing ── */
        .pricing-section {
            background: #f8fafc;
        }

        .pricing-card {
            background: #fff;
            border-radius: 20px;
            padding: 36px 28px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .pricing-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
        }

        .pricing-card.popular {
            border-color: var(--primary);
            box-shadow: 0 15px 40px rgba(62, 96, 213, 0.15);
        }

        .pricing-card.popular::before {
            content: 'Populer';
            position: absolute;
            top: -14px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: #fff;
            font-weight: 700;
            font-size: 0.8rem;
            padding: 4px 20px;
            border-radius: 50px;
        }

        .pricing-card .plan-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e293b;
        }

        .pricing-card .plan-price {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--primary);
            margin: 16px 0 4px;
        }

        .pricing-card .plan-price small {
            font-size: 0.9rem;
            font-weight: 500;
            color: #94a3b8;
        }

        .pricing-card .plan-desc {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 24px;
        }

        .pricing-features {
            list-style: none;
            padding: 0;
            margin: 0 0 auto;
        }

        .pricing-features li {
            padding: 8px 0;
            font-size: 0.9rem;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pricing-features li i {
            font-size: 16px;
            flex-shrink: 0;
        }

        .pricing-features li i.ri-check-line {
            color: var(--success);
        }

        .pricing-features li i.ri-close-line {
            color: #e2e8f0;
        }

        .pricing-card .btn-pricing {
            margin-top: 28px;
        }

        /* ── Voucher ── */
        .voucher-section {
            background: linear-gradient(135deg, #1e293b, #334155);
            color: #fff;
        }

        .voucher-input-group {
            max-width: 500px;
            margin: 0 auto;
        }

        .voucher-input-group .form-control {
            border-radius: 12px 0 0 12px;
            padding: 14px 20px;
            font-size: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .voucher-input-group .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
            letter-spacing: 1px;
            text-transform: none;
        }

        .voucher-input-group .form-control:focus {
            border-color: var(--primary);
            box-shadow: none;
            background: rgba(255, 255, 255, 0.15);
        }

        .voucher-input-group .btn {
            border-radius: 0 12px 12px 0;
            padding: 14px 28px;
            font-weight: 600;
        }

        /* ── Footer ── */
        .landing-footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 48px 0 24px;
        }

        .landing-footer h6 {
            color: #f1f5f9;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .landing-footer a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.2s;
        }

        .landing-footer a:hover {
            color: #fff;
        }

        .footer-bottom {
            border-top: 1px solid #1e293b;
            padding-top: 20px;
            margin-top: 40px;
        }

        /* ── CTA ── */
        .cta-section {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: #fff;
            padding: 70px 0;
        }

        .btn-google {
            background: #fff;
            color: #333;
            border: 1px solid #dadce0;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 24px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-google:hover {
            background: #f8f9fa;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            color: #333;
        }

        .google-icon {
            width: 20px;
            height: 20px;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2rem;
            }

            .section-title {
                font-size: 1.6rem;
            }

            .pricing-card .plan-price {
                font-size: 1.8rem;
            }
        }

        /* ── Smooth Scroll ── */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>

<body>
    <!-- ═══ Navbar ═══ -->
    <nav class="navbar navbar-expand-lg landing-navbar fixed-top">
        <div class="container">
            <a class="brand-logo" href="#">TOKO<span>KU</span></a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="#features">Fitur</a></li>
                    <li class="nav-item"><a class="nav-link" href="#pricing">Harga</a></li>
                    <li class="nav-item"><a class="nav-link" href="#voucher">Voucher</a></li>
                    <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
                </ul>
                <div class="d-flex gap-2">
                    <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm px-3">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm px-3">Daftar Gratis</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- ═══ Hero Section ═══ -->
    <section class="hero-section" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <div class="hero-badge">
                        <i class="ri-rocket-2-line"></i>
                        ERP Modern untuk UMKM Indonesia
                    </div>
                    <h1 class="hero-title">
                        Kelola Bisnis Anda<br>
                        dengan <span class="highlight">Tokoku ERP</span>
                    </h1>
                    <p class="hero-subtitle mt-3">
                        Sistem ERP all-in-one yang lengkap dan terjangkau. Akuntansi, pembelian, penjualan,
                        payroll, aset, hingga pajak — semua dalam satu platform cloud.
                    </p>
                    <div class="d-flex flex-wrap gap-3 mt-4">
                        <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-4">
                            <i class="ri-rocket-line me-1"></i> Mulai Gratis 14 Hari
                        </a>
                        <a href="{{ route('auth.google') }}" class="btn btn-google btn-lg">
                            <svg class="google-icon" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            Masuk dengan Google
                        </a>
                    </div>
                    <p class="mt-3 text-muted small">
                        <i class="ri-shield-check-line text-success me-1"></i>
                        Tanpa kartu kredit · Tanpa komitmen · Setup 2 menit
                    </p>
                </div>
                <div class="col-lg-6">
                    <div class="position-relative text-center">
                        <img src="/assets/images/auth-img.jpg" alt="Tokoku ERP Dashboard" class="hero-image">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ Stats ═══ -->
    <section class="stats-section section-padding">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 col-6 mb-4 mb-md-0">
                    <div class="stat-item">
                        <h3>58+</h3>
                        <p>Modul Lengkap</p>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4 mb-md-0">
                    <div class="stat-item">
                        <h3>12</h3>
                        <p>Kategori Fitur</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <h3>7+</h3>
                        <p>Laporan PDF</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <h3>99rb</h3>
                        <p>Mulai Dari / Bulan</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ Features ═══ -->
    <section class="section-padding" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Semua yang Bisnis Anda Butuhkan</h2>
                <p class="section-subtitle">Dari pembukuan sederhana hingga manajemen aset kompleks — Tokoku ERP punya semuanya.</p>
            </div>
            <div class="row g-4">
                <!-- Feature 1 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon bg-soft-primary text-primary">
                            <i class="ri-book-2-line"></i>
                        </div>
                        <h5>Akuntansi</h5>
                        <p>COA, Jurnal, Buku Besar, Neraca Saldo, Laba Rugi, Neraca Keuangan</p>
                    </div>
                </div>
                <!-- Feature 2 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon bg-soft-success text-success">
                            <i class="ri-shopping-cart-2-line"></i>
                        </div>
                        <h5>Pembelian</h5>
                        <p>Purchase Order, pembelian langsung, pembayaran parsial, auto stok</p>
                    </div>
                </div>
                <!-- Feature 3 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon bg-soft-warning text-warning">
                            <i class="ri-store-2-line"></i>
                        </div>
                        <h5>Penjualan</h5>
                        <p>Penjualan barang, saldo, & jasa. Pembayaran cash/credit/partial</p>
                    </div>
                </div>
                <!-- Feature 4 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon bg-soft-info text-info">
                            <i class="ri-bank-line"></i>
                        </div>
                        <h5>Perbankan</h5>
                        <p>Rekening, transfer dana, fee matrix, rekonsiliasi bank otomatis</p>
                    </div>
                </div>
                <!-- Feature 5 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon bg-soft-danger text-danger">
                            <i class="ri-money-dollar-circle-line"></i>
                        </div>
                        <h5>Hutang/Piutang</h5>
                        <p>Tracking AP/AR, pembayaran parsial, aging report, dan riwayat</p>
                    </div>
                </div>
                <!-- Feature 6 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon bg-soft-secondary text-secondary">
                            <i class="ri-team-line"></i>
                        </div>
                        <h5>Payroll</h5>
                        <p>Penggajian otomatis, PPh 21 TER, BPJS, tunjangan & potongan</p>
                    </div>
                </div>
                <!-- Feature 7 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon bg-soft-dark text-dark">
                            <i class="ri-building-4-line"></i>
                        </div>
                        <h5>Aset</h5>
                        <p>Penyusutan otomatis, mutasi, disposal, perbaikan, 4 laporan</p>
                    </div>
                </div>
                <!-- Feature 8 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon bg-soft-primary text-primary">
                            <i class="ri-file-list-3-line"></i>
                        </div>
                        <h5>Perpajakan</h5>
                        <p>Faktur pajak, SPT Masa PPN, SPT Tahunan, PPh Badan, koreksi fiskal</p>
                    </div>
                </div>
            </div>

            <!-- More features row -->
            <div class="row g-4 mt-2">
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon bg-soft-success text-success">
                            <i class="ri-wallet-3-line"></i>
                        </div>
                        <h5>Saldo Management</h5>
                        <p>Provider saldo, topup, transaksi penjualan saldo digital</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon bg-soft-warning text-warning">
                            <i class="ri-box-3-line"></i>
                        </div>
                        <h5>Inventory</h5>
                        <p>Stok, kategori, satuan, stock opname, warehouse monitor</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon bg-soft-info text-info">
                            <i class="ri-folder-chart-line"></i>
                        </div>
                        <h5>Project</h5>
                        <p>Job order, tracking biaya & pendapatan, budget vs actual</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon bg-soft-danger text-danger">
                            <i class="ri-pie-chart-2-line"></i>
                        </div>
                        <h5>Dashboard</h5>
                        <p>Grafik penjualan, pembelian, cash flow, low stock alerts</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ Pricing ═══ -->
    <section class="pricing-section section-padding" id="pricing">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Harga Terjangkau untuk UMKM</h2>
                <p class="section-subtitle">Pilih paket yang sesuai dengan skala bisnis Anda. Semua paket sudah termasuk update gratis.</p>
            </div>
            <div class="row g-4 justify-content-center">
                @foreach($plans as $plan)
                <div class="col-lg-3 col-md-6">
                    <div class="pricing-card {{ $plan->slug === 'medium' ? 'popular' : '' }}">
                        <div class="text-center">
                            <div class="plan-name">{{ $plan->name }}</div>
                            <div class="plan-price">
                                {{ $plan->formatted_price }}
                                @if($plan->price > 0)
                                <small>/bulan</small>
                                @else
                                <small>{{ $plan->duration_days }} hari</small>
                                @endif
                            </div>
                            <p class="plan-desc">{{ $plan->description }}</p>
                        </div>

                        <ul class="pricing-features">
                            <li>
                                <i class="ri-user-line text-primary"></i>
                                <strong>{{ $plan->max_users === 0 ? 'Unlimited' : $plan->max_users }}</strong> User
                            </li>
                            <li>
                                <i class="ri-building-2-line text-primary"></i>
                                <strong>{{ $plan->max_business_units === 0 ? 'Unlimited' : $plan->max_business_units }}</strong> Unit Usaha
                            </li>
                            @php
                                $features = $plan->features->where('is_enabled', true)->pluck('feature_key')->toArray();
                            @endphp

                            @foreach([
                                'coa' => 'COA & Jurnal',
                                'trial_balance' => 'Neraca & Laba Rugi',
                                'purchase' => 'Pembelian',
                                'sales' => 'Penjualan',
                                'ap_ar' => 'Hutang/Piutang',
                                'bank' => 'Bank & Transfer',
                                'payroll' => 'Payroll',
                                'asset' => 'Asset Management',
                                'tax' => 'Perpajakan',
                                'bank_reconciliation' => 'Bank Reconciliation',
                                'project' => 'Project / Job Order',
                            ] as $key => $label)
                            <li>
                                @if(in_array($key, $features))
                                <i class="ri-check-line"></i>
                                @else
                                <i class="ri-close-line"></i>
                                @endif
                                {{ $label }}
                            </li>
                            @endforeach
                        </ul>

                        <div class="text-center btn-pricing">
                            @if($plan->slug === 'trial')
                            <a href="{{ route('register') }}" class="btn btn-outline-primary w-100">
                                Coba Gratis
                            </a>
                            @else
                            <a href="{{ route('register', ['plan' => $plan->slug]) }}" class="btn {{ $plan->slug === 'medium' ? 'btn-primary' : 'btn-outline-primary' }} w-100">
                                Pilih {{ $plan->name }}
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ═══ Voucher ═══ -->
    <section class="voucher-section section-padding" id="voucher">
        <div class="container text-center">
            <h2 class="mb-3" style="font-weight: 700;">Punya Kode Voucher?</h2>
            <p class="mb-4 opacity-75">Masukkan kode voucher Anda untuk mendapatkan akses premium secara gratis.</p>

            @auth
            <form action="{{ route('voucher.redeem') }}" method="POST">
                @csrf
                <div class="input-group voucher-input-group">
                    <input type="text" name="code" class="form-control" placeholder="Masukkan kode voucher..." required>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-coupon-3-line me-1"></i> Gunakan
                    </button>
                </div>
            </form>
            @else
            <p class="opacity-75 mb-3">Silakan login atau daftar terlebih dahulu untuk menggunakan voucher.</p>
            <div class="d-flex gap-2 justify-content-center">
                <a href="{{ route('login') }}" class="btn btn-outline-light">Login</a>
                <a href="{{ route('register') }}" class="btn btn-primary">Daftar Gratis</a>
            </div>
            @endauth

            @if(session('voucher_success'))
            <div class="alert alert-success mt-3 d-inline-block">
                <i class="ri-checkbox-circle-line me-1"></i> {{ session('voucher_success') }}
            </div>
            @endif

            @if(session('voucher_error'))
            <div class="alert alert-danger mt-3 d-inline-block">
                <i class="ri-close-circle-line me-1"></i> {{ session('voucher_error') }}
            </div>
            @endif
        </div>
    </section>

    <!-- ═══ FAQ ═══ -->
    <section class="section-padding" id="faq">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Pertanyaan Umum</h2>
                <p class="section-subtitle">Jawaban untuk pertanyaan yang sering ditanyakan.</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item border rounded-3 mb-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Apa itu Tokoku ERP?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted">
                                    Tokoku ERP adalah sistem Enterprise Resource Planning (ERP) berbasis cloud yang dirancang khusus untuk UMKM Indonesia.
                                    Fiturnya mencakup akuntansi, pembelian, penjualan, payroll, aset, perpajakan, dan banyak lagi — semua dalam satu platform yang mudah digunakan.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border rounded-3 mb-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Apakah bisa dicoba gratis?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted">
                                    Ya! Anda bisa mencoba paket Trial selama 14 hari tanpa biaya apapun dan tanpa kartu kredit.
                                    Paket trial sudah termasuk fitur dasar akuntansi (COA, jurnal, neraca saldo, laba rugi).
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border rounded-3 mb-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Bagaimana cara mendaftar?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted">
                                    Anda bisa mendaftar dengan email atau langsung menggunakan akun Google. Proses pendaftaran hanya membutuhkan waktu 2 menit.
                                    Setelah mendaftar, Anda langsung mendapatkan akses paket Trial 14 hari.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border rounded-3 mb-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    Apa itu voucher dan bagaimana cara menggunakannya?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted">
                                    Voucher adalah kode khusus yang memberikan akses gratis ke paket Medium atau Premium.
                                    Setelah login, masukkan kode voucher Anda di halaman Voucher. Akses akan langsung aktif sesuai paket dan durasi voucher.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border rounded-3 mb-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    Berapa banyak user dan unit usaha yang bisa saya buat?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted">
                                    Tergantung paket Anda: Trial (1 user, 1 unit), Basic (3 user, 1 unit), Medium (10 user, 3 unit),
                                    Premium (unlimited user & unit). Anda bisa upgrade kapan saja.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ CTA ═══ -->
    <section class="cta-section">
        <div class="container text-center">
            <h2 style="font-weight: 700; font-size: 2rem;">Siap Mengelola Bisnis Anda Lebih Baik?</h2>
            <p class="mt-3 mb-4 opacity-85" style="font-size: 1.1rem;">
                Mulai gratis hari ini — tanpa komitmen, tanpa kartu kredit.
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="{{ route('register') }}" class="btn btn-light btn-lg px-4 fw-semibold">
                    <i class="ri-rocket-line me-1"></i> Daftar Gratis
                </a>
                <a href="{{ route('auth.google') }}" class="btn btn-outline-light btn-lg px-4">
                    <i class="ri-google-fill me-1"></i> Masuk dengan Google
                </a>
            </div>
        </div>
    </section>

    <!-- ═══ Footer ═══ -->
    <footer class="landing-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <a class="brand-logo d-block mb-3" href="#">TOKO<span>KU</span></a>
                    <p class="mb-0">Sistem ERP modern, lengkap, dan terjangkau untuk UMKM Indonesia.</p>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-4 mb-md-0">
                    <h6>Produk</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#features">Fitur</a></li>
                        <li class="mb-2"><a href="#pricing">Harga</a></li>
                        <li class="mb-2"><a href="#voucher">Voucher</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-4 mb-md-0">
                    <h6>Bantuan</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#faq">FAQ</a></li>
                        <li class="mb-2"><a href="#">Kontak</a></li>
                        <li class="mb-2"><a href="#">Panduan</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-4">
                    <h6>Login</h6>
                    <div class="d-flex gap-2 mt-2">
                        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-light">
                            <i class="ri-login-circle-line me-1"></i> Login
                        </a>
                        <a href="{{ route('auth.google') }}" class="btn btn-sm btn-outline-light">
                            <i class="ri-google-fill me-1"></i> Google
                        </a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom text-center">
                <p class="mb-0 small">&copy; {{ date('Y') }} Tokoku ERP. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="/assets/js/vendor.min.js"></script>
    <script src="/assets/js/app.min.js"></script>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function () {
            const navbar = document.querySelector('.landing-navbar');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 4px 30px rgba(0,0,0,0.1)';
            } else {
                navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.06)';
            }
        });
    </script>
</body>

</html>
