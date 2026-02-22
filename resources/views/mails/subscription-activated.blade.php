<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paket Aktif - TOKOKU</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f7fa;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f7fa;padding:40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#0acf97,#3e60d5);padding:30px 40px;text-align:center;">
                            <h1 style="color:#fff;margin:0;font-size:24px;">🎉 Paket Anda Telah Aktif!</h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:40px;">
                            <p style="color:#333;font-size:16px;margin:0 0 20px;">
                                Halo <strong>{{ $userName }}</strong>,
                            </p>
                            <p style="color:#555;font-size:15px;line-height:1.6;margin:0 0 20px;">
                                Pembayaran Anda telah dikonfirmasi dan paket <strong style="color:#3e60d5;">{{ $planName }}</strong> Anda sekarang sudah aktif.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:8px;padding:20px;margin:0 0 24px;">
                                <tr>
                                    <td style="padding:12px 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="color:#888;font-size:13px;padding:4px 0;">Paket</td>
                                                <td style="color:#333;font-size:14px;font-weight:600;text-align:right;padding:4px 0;">{{ $planName }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color:#888;font-size:13px;padding:4px 0;">Berlaku Hingga</td>
                                                <td style="color:#333;font-size:14px;font-weight:600;text-align:right;padding:4px 0;">{{ \Carbon\Carbon::parse($endsAt)->format('d F Y') }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color:#888;font-size:13px;padding:4px 0;">Status</td>
                                                <td style="text-align:right;padding:4px 0;">
                                                    <span style="background:#d1fae5;color:#065f46;padding:3px 12px;border-radius:50px;font-size:12px;font-weight:600;">Aktif</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="color:#555;font-size:15px;line-height:1.6;margin:0 0 24px;">
                                Langkah selanjutnya adalah membuat instansi bisnis Anda. Klik tombol di bawah untuk melanjutkan:
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $setupUrl }}" style="display:inline-block;background:linear-gradient(135deg,#3e60d5,#6366f1);color:#fff;text-decoration:none;padding:14px 40px;border-radius:8px;font-size:16px;font-weight:600;">
                                            Buat Instansi Bisnis →
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#f8fafc;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb;">
                            <p style="color:#999;font-size:12px;margin:0;">
                                © {{ date('Y') }} TOKOKU ERP — PT Kuroragi Digital Indonesia
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
