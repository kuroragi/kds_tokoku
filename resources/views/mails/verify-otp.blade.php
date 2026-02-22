<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi - TOKOKU</title>
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
                                ✉️ Verifikasi Email
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
                                Halo <strong>{{ $userName }}</strong>! 👋
                            </p>
                            <p style="font-size: 14px; color: #666; line-height: 1.6; margin: 0;">
                                Terima kasih telah mendaftar di <strong>TOKOKU</strong>. Masukkan kode verifikasi
                                di bawah ini untuk mengonfirmasi alamat email Anda.
                            </p>
                        </td>
                    </tr>

                    <!-- OTP Code Card -->
                    <tr>
                        <td style="padding: 10px 40px 30px; text-align: center;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 35px 30px; text-align: center;">
                                        <div style="font-size: 11px; color: rgba(255,255,255,0.6); letter-spacing: 4px; text-transform: uppercase; margin-bottom: 16px;">
                                            KODE VERIFIKASI
                                        </div>

                                        <!-- The OTP Code -->
                                        <div style="background: rgba(255,255,255,0.15); border: 2px dashed rgba(255,255,255,0.4); border-radius: 12px; padding: 20px 24px; display: inline-block; margin-bottom: 20px;">
                                            <div style="font-size: 36px; font-weight: bold; color: #ffffff; letter-spacing: 12px; font-family: 'Courier New', Courier, monospace;">
                                                {{ $otpCode }}
                                            </div>
                                        </div>

                                        <!-- Timer Badge -->
                                        <div style="margin-top: 8px;">
                                            <span style="background: rgba(255,255,255,0.2); color: #fff; padding: 6px 20px; border-radius: 50px; font-size: 13px;">
                                                ⏱️ Berlaku selama 15 menit
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Info Box -->
                    <tr>
                        <td style="padding: 0 40px 20px;">
                            <div style="background: #f8f9fe; border-left: 4px solid #764ba2; border-radius: 0 8px 8px 0; padding: 16px 20px;">
                                <p style="font-size: 13px; color: #888; margin: 0 0 6px;">ℹ️ Cara Verifikasi</p>
                                <p style="font-size: 14px; color: #444; margin: 0; line-height: 1.5;">
                                    Buka halaman verifikasi email di browser Anda, lalu masukkan kode <strong>6 digit</strong>
                                    di atas. Setelah verifikasi, Anda dapat memilih paket dan mulai menggunakan TOKOKU.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 40px;">
                            <hr style="border: none; border-top: 1px solid #eee; margin: 0;">
                        </td>
                    </tr>

                    <!-- Security Notice -->
                    <tr>
                        <td style="padding: 20px 40px;">
                            <p style="font-size: 13px; color: #999; line-height: 1.6; margin: 0;">
                                🔒 Jangan bagikan kode ini kepada siapapun. Jika Anda tidak mendaftar di TOKOKU, abaikan email ini.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 25px 40px; text-align: center; border-top: 1px solid #eee;">
                            <p style="font-size: 13px; color: #999; margin: 0 0 8px;">
                                &copy; {{ date('Y') }} TOKOKU by <strong style="color: #667eea;">Kuroragi Digital Studio</strong>
                            </p>
                            <p style="font-size: 11px; color: #bbb; margin: 0;">
                                Email ini dikirim secara otomatis. Mohon tidak membalas email ini.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
