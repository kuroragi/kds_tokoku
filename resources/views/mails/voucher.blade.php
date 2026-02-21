<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher TOKOKU</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f2f5;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f0f2f5; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0"
                    style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">

                    <!-- Header Gradient -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 50px 40px 30px; text-align: center;">
                            <div style="font-size: 14px; color: rgba(255,255,255,0.8); letter-spacing: 3px; text-transform: uppercase; margin-bottom: 8px;">
                                🎁 Hadiah Spesial untuk Anda
                            </div>
                            <div style="font-size: 32px; font-weight: bold; color: #ffffff; margin-bottom: 8px;">
                                TOKOKU
                            </div>
                            <div style="font-size: 14px; color: rgba(255,255,255,0.7);">
                                Sistem ERP & Akuntansi Modern
                            </div>
                        </td>
                    </tr>

                    <!-- Greeting -->
                    <tr>
                        <td style="padding: 40px 40px 20px;">
                            <p style="font-size: 18px; color: #333; margin: 0 0 8px;">
                                Halo <strong>{{ $recipientName }}</strong>! 👋
                            </p>
                            <p style="font-size: 14px; color: #666; line-height: 1.6; margin: 0;">
                                Anda mendapatkan voucher eksklusif untuk mengaktifkan paket <strong>{{ $plan->name }}</strong>
                                di TOKOKU. Gunakan kode di bawah ini untuk mengaktifkan langganan Anda.
                            </p>
                        </td>
                    </tr>

                    <!-- Personal Message -->
                    @if(!empty($personalMessage))
                    <tr>
                        <td style="padding: 0 40px 20px;">
                            <div style="background: #f8f9fe; border-left: 4px solid #764ba2; border-radius: 0 8px 8px 0; padding: 16px 20px;">
                                <p style="font-size: 13px; color: #888; margin: 0 0 6px; font-style: italic;">Pesan dari pengirim:</p>
                                <p style="font-size: 14px; color: #444; margin: 0; line-height: 1.5;">
                                    "{{ $personalMessage }}"
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endif

                    <!-- Voucher Card -->
                    <tr>
                        <td style="padding: 10px 40px 30px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 35px 30px; text-align: center;">
                                        <!-- Decorative dots -->
                                        <div style="position: relative;">
                                            <div style="font-size: 11px; color: rgba(255,255,255,0.6); letter-spacing: 4px; text-transform: uppercase; margin-bottom: 16px;">
                                                KODE VOUCHER
                                            </div>

                                            <!-- The Code -->
                                            <div style="background: rgba(255,255,255,0.15); border: 2px dashed rgba(255,255,255,0.4); border-radius: 12px; padding: 20px 24px; display: inline-block; margin-bottom: 20px;">
                                                <div style="font-size: 28px; font-weight: bold; color: #ffffff; letter-spacing: 6px; font-family: 'Courier New', Courier, monospace;">
                                                    {{ $voucher->code }}
                                                </div>
                                            </div>

                                            <!-- Plan Badge -->
                                            <div style="margin-bottom: 8px;">
                                                <span style="background: rgba(255,255,255,0.2); color: #fff; padding: 6px 20px; border-radius: 50px; font-size: 14px; font-weight: 600;">
                                                    ✨ Paket {{ $plan->name }}
                                                </span>
                                            </div>

                                            <!-- Duration -->
                                            <div style="color: rgba(255,255,255,0.8); font-size: 13px;">
                                                Berlaku selama <strong style="color: #fff;">{{ number_format($voucher->duration_days, 0) }} hari</strong>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Plan Features -->
                    <tr>
                        <td style="padding: 0 40px 30px;">
                            <div style="font-size: 15px; font-weight: 600; color: #333; margin-bottom: 16px;">
                                🚀 Yang Anda Dapatkan:
                            </div>
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                @php
                                    $featureList = [
                                        ['icon' => '📊', 'text' => 'Chart of Accounts & Jurnal'],
                                        ['icon' => '📈', 'text' => 'Neraca Saldo & Laba Rugi'],
                                        ['icon' => '🏪', 'text' => 'Manajemen Stok & Penjualan'],
                                        ['icon' => '🏦', 'text' => 'Bank & Rekonsiliasi'],
                                    ];
                                    if (in_array($plan->slug, ['medium', 'premium'])) {
                                        $featureList[] = ['icon' => '💰', 'text' => 'Payroll & Pinjaman Karyawan'];
                                        $featureList[] = ['icon' => '🏗️', 'text' => 'Manajemen Aset & Proyek'];
                                    }
                                    if ($plan->slug === 'premium') {
                                        $featureList[] = ['icon' => '📑', 'text' => 'Laporan Pajak & Koreksi Fiskal'];
                                        $featureList[] = ['icon' => '👥', 'text' => 'Multi-Role & User Unlimited'];
                                    }
                                @endphp

                                @foreach($featureList as $feature)
                                <tr>
                                    <td style="padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                                        <table cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="width: 32px; font-size: 16px;">{{ $feature['icon'] }}</td>
                                                <td style="font-size: 14px; color: #555;">{{ $feature['text'] }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    <!-- How to Use -->
                    <tr>
                        <td style="padding: 0 40px 30px;">
                            <div style="background: #f8f9fe; border-radius: 12px; padding: 24px;">
                                <div style="font-size: 15px; font-weight: 600; color: #333; margin-bottom: 16px;">
                                    📋 Cara Menggunakan:
                                </div>
                                <table cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td style="padding: 6px 0;">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td style="width: 28px; height: 28px; background: #667eea; border-radius: 50%; text-align: center; color: #fff; font-size: 12px; line-height: 28px; font-weight: bold;">1</td>
                                                    <td style="padding-left: 12px; font-size: 13px; color: #555;">Daftar atau login di <strong>TOKOKU</strong></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0;">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td style="width: 28px; height: 28px; background: #667eea; border-radius: 50%; text-align: center; color: #fff; font-size: 12px; line-height: 28px; font-weight: bold;">2</td>
                                                    <td style="padding-left: 12px; font-size: 13px; color: #555;">Masukkan kode voucher di halaman <strong>Redeem Voucher</strong></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0;">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td style="width: 28px; height: 28px; background: #667eea; border-radius: 50%; text-align: center; color: #fff; font-size: 12px; line-height: 28px; font-weight: bold;">3</td>
                                                    <td style="padding-left: 12px; font-size: 13px; color: #555;">Nikmati fitur <strong>{{ $plan->name }}</strong> selama {{ number_format($voucher->duration_days, 0) }} hari!</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>

                    <!-- Expiry Notice -->
                    <tr>
                        <td style="padding: 0 40px 30px; text-align: center;">
                            <div style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 12px 20px;">
                                <span style="font-size: 13px; color: #c2410c;">
                                    ⏰ Voucher berlaku hingga <strong>{{ $voucher->valid_until->format('d F Y') }}</strong>
                                </span>
                            </div>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 40px;">
                            <div style="border-top: 1px solid #e5e7eb;"></div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; text-align: center;">
                            <p style="font-size: 13px; color: #999; margin: 0 0 8px;">
                                Email ini dikirim oleh <strong>TOKOKU — Kuroragi Digital Studio</strong>
                            </p>
                            <p style="font-size: 12px; color: #bbb; margin: 0;">
                                Jika Anda merasa menerima email ini karena kesalahan, abaikan saja.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
