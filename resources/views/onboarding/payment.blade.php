<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Tokoku - Pembayaran</title>
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
        .payment-header {
            background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
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
        .step.completed { color: #0acf97; }
        .step.active { color: #3e60d5; font-weight: 600; }
        .step-dot {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
        }
        .step.completed .step-dot { background: #0acf97; color: #fff; }
        .step.active .step-dot { background: #3e60d5; color: #fff; }
        .step .step-dot { background: #e2e8f0; color: #94a3b8; }
        .step-line { width: 40px; height: 2px; background: #e2e8f0; align-self: center; }
        .step-line.completed { background: #0acf97; }
        .bank-card {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            transition: all 0.2s;
            cursor: pointer;
        }
        .bank-card:hover { border-color: #3e60d5; background: #f8f9fe; }
        .bank-card.selected { border-color: #3e60d5; background: #f0f3ff; }
        .copy-btn {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 4px 12px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .copy-btn:hover { background: #e2e8f0; }
        .status-pending {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 12px;
            padding: 20px;
        }
        .amount-display {
            font-size: 2rem;
            font-weight: 800;
            color: #1e293b;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center py-4">
            <div class="col-12 text-center mb-3">
                <a href="{{ route('landing') }}" class="brand-logo">TOKO<span>KU</span></a>
            </div>

            <div class="col-12">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step completed">
                        <div class="step-dot"><i class="ri-check-line"></i></div>
                        <span class="d-none d-sm-inline">Daftar</span>
                    </div>
                    <div class="step-line completed"></div>
                    <div class="step completed">
                        <div class="step-dot"><i class="ri-check-line"></i></div>
                        <span class="d-none d-sm-inline">Verifikasi</span>
                    </div>
                    <div class="step-line completed"></div>
                    <div class="step completed">
                        <div class="step-dot"><i class="ri-check-line"></i></div>
                        <span class="d-none d-sm-inline">Pilih Paket</span>
                    </div>
                    <div class="step-line completed"></div>
                    <div class="step active">
                        <div class="step-dot">4</div>
                        <span class="d-none d-sm-inline">Pembayaran</span>
                    </div>
                    <div class="step-line"></div>
                    <div class="step">
                        <div class="step-dot">5</div>
                        <span class="d-none d-sm-inline">Buat Instansi</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-lg overflow-hidden" style="border-radius: 16px;">
                    <!-- Header -->
                    <div class="payment-header">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h3 class="mb-1 fw-bold">Pembayaran Paket</h3>
                                <p class="mb-0 opacity-75">Selesaikan pembayaran untuk mengaktifkan paket Anda</p>
                            </div>
                            <div class="plan-badge">
                                <i class="ri-vip-crown-line"></i>
                                {{ $subscription->plan->name }}
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <!-- Status Pending -->
                        <div class="status-pending mb-4">
                            <div class="d-flex align-items-center gap-3">
                                <div>
                                    <i class="ri-time-line" style="font-size: 2.5rem; color: #f59e0b;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1 fw-bold">Menunggu Pembayaran</h5>
                                    <p class="mb-0 text-muted">Lakukan pembayaran sesuai instruksi di bawah, lalu hubungi admin untuk konfirmasi.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Order Summary -->
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3"><i class="ri-file-list-3-line text-primary me-2"></i>Ringkasan Pesanan</h5>
                            <div class="table-responsive">
                                <table class="table table-borderless mb-0">
                                    <tr>
                                        <td class="text-muted">Paket</td>
                                        <td class="text-end fw-semibold">{{ $subscription->plan->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Durasi</td>
                                        <td class="text-end fw-semibold">{{ $subscription->plan->duration_days }} Hari</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Max User</td>
                                        <td class="text-end fw-semibold">{{ $subscription->plan->max_users ?: 'Unlimited' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Max Unit Usaha</td>
                                        <td class="text-end fw-semibold">{{ $subscription->plan->max_business_units ?: 'Unlimited' }}</td>
                                    </tr>
                                    <tr class="border-top">
                                        <td class="fw-bold fs-5">Total Pembayaran</td>
                                        <td class="text-end">
                                            <span class="amount-display text-primary">Rp {{ number_format($subscription->amount_paid, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Bank Transfer Info -->
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3"><i class="ri-bank-line text-primary me-2"></i>Transfer ke Rekening</h5>

                            <div class="row g-3">
                                <!-- BCA -->
                                <div class="col-md-6">
                                    <div class="bank-card">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2">BCA</span>
                                            <span class="text-muted small">Bank Central Asia</span>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <div class="fw-bold fs-5" id="bca-number">1234567890</div>
                                                <div class="text-muted small">a.n. PT Kuroragi Digital Indonesia</div>
                                            </div>
                                            <button type="button" class="copy-btn" onclick="copyToClipboard('bca-number', this)">
                                                <i class="ri-file-copy-line"></i> Salin
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mandiri -->
                                <div class="col-md-6">
                                    <div class="bank-card">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-warning-subtle text-warning fw-bold px-3 py-2">Mandiri</span>
                                            <span class="text-muted small">Bank Mandiri</span>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <div class="fw-bold fs-5" id="mandiri-number">0987654321</div>
                                                <div class="text-muted small">a.n. PT Kuroragi Digital Indonesia</div>
                                            </div>
                                            <button type="button" class="copy-btn" onclick="copyToClipboard('mandiri-number', this)">
                                                <i class="ri-file-copy-line"></i> Salin
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Amount to transfer -->
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3"><i class="ri-money-dollar-circle-line text-primary me-2"></i>Nominal Transfer</h5>
                            <div class="bg-light rounded-3 p-3 d-flex align-items-center justify-content-between">
                                <span class="fw-bold fs-4" id="transfer-amount">Rp {{ number_format($subscription->amount_paid, 0, ',', '.') }}</span>
                                <button type="button" class="copy-btn" onclick="copyToClipboard('transfer-amount-raw', this)">
                                    <i class="ri-file-copy-line"></i> Salin Nominal
                                </button>
                                <input type="hidden" id="transfer-amount-raw" value="{{ (int) $subscription->amount_paid }}">
                            </div>
                            <p class="text-muted small mt-2">
                                <i class="ri-information-line me-1"></i>
                                Transfer sesuai nominal di atas agar pembayaran dapat diverifikasi otomatis.
                            </p>
                        </div>

                        <!-- Confirmation Steps -->
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3"><i class="ri-list-check-2 text-primary me-2"></i>Langkah Konfirmasi</h5>
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3">
                                    <span class="badge bg-primary rounded-circle" style="width: 28px; height: 28px; line-height: 20px;">1</span>
                                    <div>
                                        <div class="fw-semibold">Transfer ke salah satu rekening di atas</div>
                                        <div class="text-muted small">Pastikan nominal sesuai dengan yang tertera</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3">
                                    <span class="badge bg-primary rounded-circle" style="width: 28px; height: 28px; line-height: 20px;">2</span>
                                    <div>
                                        <div class="fw-semibold">Hubungi admin via WhatsApp untuk konfirmasi</div>
                                        <div class="text-muted small">Kirim bukti transfer beserta email akun Anda</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3">
                                    <span class="badge bg-primary rounded-circle" style="width: 28px; height: 28px; line-height: 20px;">3</span>
                                    <div>
                                        <div class="fw-semibold">Paket akan diaktifkan oleh admin</div>
                                        <div class="text-muted small">Setelah dikonfirmasi, Anda bisa langsung membuat instansi bisnis</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- WhatsApp Button -->
                        <div class="d-grid gap-2 mb-3">
                            <a href="https://wa.me/6281234567890?text={{ urlencode('Halo Admin TOKOKU, saya ingin konfirmasi pembayaran paket ' . $subscription->plan->name . ' (Rp ' . number_format($subscription->amount_paid, 0, ',', '.') . '). Email: ' . auth()->user()->email) }}"
                               target="_blank"
                               class="btn btn-success btn-lg">
                                <i class="ri-whatsapp-line me-2"></i> Konfirmasi via WhatsApp
                            </a>
                        </div>

                        <div class="text-center">
                            <p class="text-muted small mb-2">Sudah punya voucher? Gunakan di halaman utama.</p>
                            <a href="{{ route('landing') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="ri-arrow-left-line me-1"></i> Kembali ke Halaman Utama
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card border-0 shadow-sm mt-3" style="border-radius: 12px;">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ri-shield-check-line text-success fs-4"></i>
                            <div>
                                <div class="fw-semibold small">Pembayaran Aman</div>
                                <div class="text-muted" style="font-size: 0.8rem;">Transfer langsung ke rekening resmi. Konfirmasi diproses dalam 1x24 jam.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/vendor.min.js"></script>
    <script src="/assets/js/app.min.js"></script>
    <script>
        function copyToClipboard(elementId, btn) {
            const el = document.getElementById(elementId);
            let text = el.value || el.textContent;
            text = text.replace(/[^0-9]/g, '') || el.textContent.trim();

            navigator.clipboard.writeText(text).then(() => {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="ri-check-line"></i> Tersalin!';
                btn.classList.add('text-success');
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('text-success');
                }, 2000);
            });
        }
    </script>
</body>
</html>
