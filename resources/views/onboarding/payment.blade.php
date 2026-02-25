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
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,0.2); padding: 6px 14px;
            border-radius: 50px; font-size: 0.85rem; font-weight: 600;
        }
        .step-indicator { display: flex; justify-content: center; gap: 12px; margin-bottom: 30px; }
        .step { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #94a3b8; }
        .step.completed { color: #0acf97; }
        .step.active { color: #3e60d5; font-weight: 600; }
        .step-dot {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.75rem;
        }
        .step.completed .step-dot { background: #0acf97; color: #fff; }
        .step.active .step-dot { background: #3e60d5; color: #fff; }
        .step .step-dot { background: #e2e8f0; color: #94a3b8; }
        .step-line { width: 40px; height: 2px; background: #e2e8f0; align-self: center; }
        .step-line.completed { background: #0acf97; }
        .bank-card {
            border: 2px solid #e2e8f0; border-radius: 12px; padding: 16px;
            transition: all 0.2s; cursor: pointer;
        }
        .bank-card:hover { border-color: #3e60d5; background: #f8f9fe; }
        .copy-btn {
            background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px;
            padding: 4px 12px; font-size: 0.8rem; cursor: pointer; transition: all 0.2s;
        }
        .copy-btn:hover { background: #e2e8f0; }
        .status-pending {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 12px; padding: 20px;
        }
        .invoice-box {
            border: 2px solid #e2e8f0; border-radius: 12px; overflow: hidden;
        }
        .invoice-header {
            background: #f8fafc; padding: 16px 20px; border-bottom: 2px solid #e2e8f0;
        }
        .invoice-body { padding: 20px; }
        .invoice-footer {
            background: #f1f5f9; padding: 16px 20px; border-top: 2px dashed #e2e8f0;
        }
        @media print {
            .no-print { display: none !important; }
            .invoice-box { border: 1px solid #333; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center py-4">
            <div class="col-12 text-center mb-3">
                <a href="{{ route('landing') }}" class="brand-logo">TOKO<span>KU</span></a>
            </div>

            <div class="col-12 no-print">
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
                                <i class="ri-time-line" style="font-size: 2.5rem; color: #f59e0b;"></i>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1 fw-bold">Menunggu Pembayaran</h5>
                                    <p class="mb-0 text-muted">Lakukan pembayaran sesuai instruksi di bawah, lalu hubungi admin untuk konfirmasi.</p>
                                </div>
                            </div>
                        </div>

                        {{-- ═══ INVOICE ═══ --}}
                        @if($invoice)
                        <div class="invoice-box mb-4" id="invoice-area">
                            <div class="invoice-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0 fw-bold"><i class="ri-bill-line text-primary me-2"></i>Invoice</h5>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-primary">{{ $invoice->invoice_number }}</div>
                                    <div class="text-muted small">
                                        @if($invoice->isOverdue())
                                            <span class="badge bg-danger">Jatuh Tempo</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Belum Dibayar</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="invoice-body">
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <div class="text-muted small">Tanggal Terbit</div>
                                        <div class="fw-semibold">{{ $invoice->issued_at->format('d F Y') }}</div>
                                    </div>
                                    <div class="col-6 text-end">
                                        <div class="text-muted small">Jatuh Tempo</div>
                                        <div class="fw-semibold {{ $invoice->isOverdue() ? 'text-danger' : '' }}">
                                            {{ $invoice->due_at->format('d F Y') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <div class="text-muted small">Ditagihkan Kepada</div>
                                        <div class="fw-semibold">{{ auth()->user()->name }}</div>
                                        <div class="text-muted small">{{ auth()->user()->email }}</div>
                                    </div>
                                    <div class="col-6 text-end">
                                        <div class="text-muted small">Dari</div>
                                        <div class="fw-semibold">PT Kuroragi Digital Indonesia</div>
                                        <div class="text-muted small">TOKOKU ERP</div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Deskripsi</th>
                                                <th class="text-center">Durasi</th>
                                                <th class="text-end">Harga</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">Paket {{ $invoice->plan_name }}</div>
                                                    <div class="text-muted small">
                                                        Max {{ $subscription->plan->max_users ?: '∞' }} User,
                                                        Max {{ $subscription->plan->max_business_units ?: '∞' }} Unit Usaha
                                                    </div>
                                                </td>
                                                <td class="text-center">{{ $invoice->duration_days }} Hari</td>
                                                <td class="text-end">Rp {{ number_format($invoice->plan_price, 0, ',', '.') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="invoice-footer">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted">Subtotal</span>
                                    <span>Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                                </div>
                                @if($invoice->discount > 0)
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted">Diskon</span>
                                    <span class="text-success">- Rp {{ number_format($invoice->discount, 0, ',', '.') }}</span>
                                </div>
                                @endif
                                @if($invoice->tax > 0)
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted">Pajak</span>
                                    <span>Rp {{ number_format($invoice->tax, 0, ',', '.') }}</span>
                                </div>
                                @endif
                                <hr class="my-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold fs-5">Total</span>
                                    <span class="fw-bold fs-5 text-primary">Rp {{ number_format($invoice->total, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mb-4 no-print">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
                                <i class="ri-printer-line me-1"></i> Cetak Invoice
                            </button>
                        </div>
                        @else
                        {{-- Fallback if no invoice --}}
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
                                    <tr class="border-top">
                                        <td class="fw-bold fs-5">Total</td>
                                        <td class="text-end fw-bold fs-4 text-primary">Rp {{ number_format($subscription->amount_paid, 0, ',', '.') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        @endif

                        <!-- Bank Transfer Info -->
                        <div class="mb-4 no-print">
                            <h5 class="fw-bold mb-3"><i class="ri-bank-line text-primary me-2"></i>Transfer ke Rekening</h5>

                            <div class="row g-3">
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
                                            <button type="button" class="copy-btn" onclick="copyText('1234567890', this)">
                                                <i class="ri-file-copy-line"></i> Salin
                                            </button>
                                        </div>
                                    </div>
                                </div>
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
                                            <button type="button" class="copy-btn" onclick="copyText('0987654321', this)">
                                                <i class="ri-file-copy-line"></i> Salin
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Amount to transfer -->
                        @php $payTotal = $invoice ? $invoice->total : $subscription->amount_paid; @endphp
                        <div class="mb-4 no-print">
                            <h5 class="fw-bold mb-3"><i class="ri-money-dollar-circle-line text-primary me-2"></i>Nominal Transfer</h5>
                            <div class="bg-light rounded-3 p-3 d-flex align-items-center justify-content-between">
                                <span class="fw-bold fs-4">Rp {{ number_format($payTotal, 0, ',', '.') }}</span>
                                <button type="button" class="copy-btn" onclick="copyText('{{ (int) $payTotal }}', this)">
                                    <i class="ri-file-copy-line"></i> Salin Nominal
                                </button>
                            </div>
                            <p class="text-muted small mt-2">
                                <i class="ri-information-line me-1"></i>
                                Transfer sesuai nominal di atas agar pembayaran dapat diverifikasi.
                            </p>
                        </div>

                        <!-- Confirmation Steps -->
                        <div class="mb-4 no-print">
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
                                        <div class="text-muted small">Kirim bukti transfer beserta nomor invoice <strong>{{ $invoice?->invoice_number ?? '-' }}</strong></div>
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

                        <!-- Cek Status Pembayaran -->
                        <div class="d-grid gap-2 mb-3 no-print">
                            <a href="{{ route('onboarding.payment', ['subscription' => $subscription->id]) }}"
                               class="btn btn-outline-primary btn-lg">
                                <i class="ri-refresh-line me-2"></i> Cek Status Pembayaran
                            </a>
                        </div>

                        <!-- WhatsApp Button -->
                        <div class="d-grid gap-2 mb-3 no-print">
                            @php
                                $adminWa = \App\Models\SystemSetting::get('admin_whatsapp', '6281234567890');
                                $waMsg = "Halo Admin TOKOKU, saya ingin konfirmasi pembayaran:\n"
                                    . "Invoice: " . ($invoice?->invoice_number ?? '-') . "\n"
                                    . "Paket: " . $subscription->plan->name . "\n"
                                    . "Total: Rp " . number_format($payTotal, 0, ',', '.') . "\n"
                                    . "Email: " . auth()->user()->email;
                            @endphp
                            <a href="https://wa.me/{{ $adminWa }}?text={{ urlencode($waMsg) }}"
                               target="_blank" class="btn btn-success btn-lg">
                                <i class="ri-whatsapp-line me-2"></i> Konfirmasi via WhatsApp
                            </a>
                        </div>

                        <div class="text-center no-print">
                            <p class="text-muted small mb-2">Sudah punya voucher? Gunakan di halaman utama.</p>
                            <a href="{{ route('landing') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="ri-arrow-left-line me-1"></i> Kembali ke Halaman Utama
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3 no-print" style="border-radius: 12px;">
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
        function copyText(text, btn) {
            navigator.clipboard.writeText(text).then(() => {
                const orig = btn.innerHTML;
                btn.innerHTML = '<i class="ri-check-line"></i> Tersalin!';
                btn.classList.add('text-success');
                setTimeout(() => { btn.innerHTML = orig; btn.classList.remove('text-success'); }, 2000);
            });
        }
    </script>
</body>
</html>
