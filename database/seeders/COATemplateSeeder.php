<?php

namespace Database\Seeders;

use App\Models\CoaTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class COATemplateSeeder extends Seeder
{
    /**
     * Seed the coa_templates table with a comprehensive standard chart of accounts.
     * These templates will be cloned into each BusinessUnit on creation.
     */
    public function run(): void
    {
        $superAdmin = User::where('email', 'superadmin@tokoku.com')->first();
        if ($superAdmin) {
            Auth::login($superAdmin);
        }

        $ids = []; // code => id mapping

        // Helper to create a template row and store its id
        $make = function (string $code, string $name, string $type, int $level, ?string $parent, int $order, bool $leaf = false) use (&$ids) {
            $t = CoaTemplate::create([
                'code'            => $code,
                'name'            => $name,
                'type'            => $type,
                'level'           => $level,
                'parent_code'     => $parent ? ($ids[$parent] ?? null) : null,
                'order'           => $order,
                'is_leaf_account' => $leaf,
            ]);
            $ids[$code] = $t->id;
        };

        // ════════════════════════════════════════════════════════
        // LEVEL 1 — Klasifikasi Utama
        // ════════════════════════════════════════════════════════
        $make('1000', 'ASET',        'aktiva',     1, null, 1);
        $make('2000', 'KEWAJIBAN',    'pasiva',     1, null, 2);
        $make('3000', 'MODAL',        'modal',      1, null, 3);
        $make('4000', 'PENDAPATAN',   'pendapatan', 1, null, 4);
        $make('5000', 'BEBAN',        'beban',      1, null, 5);

        // ════════════════════════════════════════════════════════
        // LEVEL 2 — Sub Klasifikasi
        // ════════════════════════════════════════════════════════
        // Aset
        $make('1100', 'Aset Lancar',              'aktiva', 2, '1000', 1);
        $make('1200', 'Aset Tetap',               'aktiva', 2, '1000', 2);
        $make('1300', 'Aset Tak Berwujud',        'aktiva', 2, '1000', 3);
        $make('1400', 'Aset Lain-lain',           'aktiva', 2, '1000', 4);
        // Kewajiban
        $make('2100', 'Kewajiban Lancar',           'pasiva', 2, '2000', 1);
        $make('2200', 'Kewajiban Jangka Panjang',   'pasiva', 2, '2000', 2);
        // Modal
        $make('3100', 'Modal Disetor',              'modal', 2, '3000', 1);
        $make('3200', 'Laba Ditahan',               'modal', 2, '3000', 2);
        // Pendapatan
        $make('4100', 'Pendapatan Usaha',           'pendapatan', 2, '4000', 1);
        $make('4200', 'Pendapatan Lain-lain',       'pendapatan', 2, '4000', 2);
        // Beban
        $make('5100', 'Beban Pokok Penjualan',      'beban', 2, '5000', 1);
        $make('5200', 'Beban Operasional',          'beban', 2, '5000', 2);
        $make('5300', 'Beban Administrasi & Umum',  'beban', 2, '5000', 3);
        $make('5400', 'Beban Lain-lain',            'beban', 2, '5000', 4);

        // ════════════════════════════════════════════════════════
        // LEVEL 3 — Kelompok Akun
        // ════════════════════════════════════════════════════════

        // ── Aset Lancar ──
        $make('1101', 'Kas dan Setara Kas',         'aktiva', 3, '1100', 1);
        $make('1102', 'Piutang',                    'aktiva', 3, '1100', 2);
        $make('1103', 'Persediaan',                 'aktiva', 3, '1100', 3);
        $make('1104', 'Biaya Dibayar Dimuka',       'aktiva', 3, '1100', 4);
        $make('1105', 'Pajak Dibayar Dimuka',       'aktiva', 3, '1100', 5);

        // ── Aset Tetap (Berwujud) ──
        $make('1201', 'Aset Berwujud',              'aktiva', 3, '1200', 1);
        $make('1202', 'Akumulasi Penyusutan',       'aktiva', 3, '1200', 2);

        // ── Aset Tak Berwujud ──
        $make('1301', 'Hak Cipta & Lisensi',        'aktiva', 3, '1300', 1);
        $make('1302', 'Amortisasi Aset Tak Berwujud','aktiva', 3, '1300', 2);

        // ── Aset Lain-lain ──
        $make('1401', 'Jaminan & Deposito',         'aktiva', 3, '1400', 1);

        // ── Kewajiban Lancar ──
        $make('2101', 'Hutang Dagang',              'pasiva', 3, '2100', 1);
        $make('2102', 'Hutang Pajak',               'pasiva', 3, '2100', 2);
        $make('2103', 'Hutang Gaji & Tunjangan',    'pasiva', 3, '2100', 3);
        $make('2104', 'Pendapatan Diterima Dimuka',  'pasiva', 3, '2100', 4);
        $make('2105', 'Hutang Lain-lain',           'pasiva', 3, '2100', 5);

        // ── Kewajiban Jangka Panjang ──
        $make('2201', 'Hutang Bank',                'pasiva', 3, '2200', 1);
        $make('2202', 'Hutang Sewa Pembiayaan',     'pasiva', 3, '2200', 2);

        // ── Modal Disetor ──
        $make('3101', 'Modal Pemilik',              'modal', 3, '3100', 1);
        $make('3102', 'Prive / Penarikan',          'modal', 3, '3100', 2);

        // ── Laba Ditahan ──
        $make('3201', 'Laba Tahun Berjalan',        'modal', 3, '3200', 1);
        $make('3202', 'Laba Tahun Lalu',            'modal', 3, '3200', 2);

        // ── Pendapatan Usaha ──
        $make('4101', 'Pendapatan Penjualan',       'pendapatan', 3, '4100', 1);
        $make('4102', 'Potongan Penjualan',         'pendapatan', 3, '4100', 2);
        $make('4103', 'Retur Penjualan',            'pendapatan', 3, '4100', 3);

        // ── Pendapatan Lain-lain ──
        $make('4201', 'Pendapatan Bunga',           'pendapatan', 3, '4200', 1);
        $make('4202', 'Pendapatan Sewa',            'pendapatan', 3, '4200', 2);
        $make('4203', 'Laba Penjualan Aset',        'pendapatan', 3, '4200', 3);
        $make('4204', 'Pendapatan Lain-lain',       'pendapatan', 3, '4200', 4);

        // ── Beban Pokok Penjualan ──
        $make('5101', 'Harga Pokok Penjualan',      'beban', 3, '5100', 1);

        // ── Beban Operasional ──
        $make('5201', 'Beban Penjualan',            'beban', 3, '5200', 1);
        $make('5202', 'Beban Pemasaran',            'beban', 3, '5200', 2);

        // ── Beban Administrasi & Umum ──
        $make('5301', 'Beban Gaji & Upah',          'beban', 3, '5300', 1);
        uyjjjjjjjj){>{}', 'Beban Utilitas',             'beban', 3, '5300', 2);
        $make('5303', 'Beban Sewa',                 'beban', 3, '5300', 3);)_PPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPv        'beban', 3, '5300', 4);
        $make('5305', 'Beban Amortisasi',           'beban', 3, '5300', 5);
        $make('5306', 'Beban Administrasi Kantor',  'beban', 3, '5300', 6);

        // ── Beban Lain-lain ──
        $make('5401', 'Beban Bunga',                'beban', 3, '5400', 1);
        $make('5402', 'Rugi Penjualan Aset',        'beban', 3, '5400', 2);
        $make('5403', 'Beban Pajak',                'beban', 3, '5400', 3);
        $make('5404', 'Beban Lain-lain',            'beban', 3, '5400', 4);

        // ════════════════════════════════════════════════════════
        // LEVEL 4 — Akun Detail (Leaf)
        // ════════════════════════════════════════════════════════

        // ── Kas dan Setara Kas ──
        $make('1101.001', 'Kas di Tangan',              'aktiva', 4, '1101', 1, true);
        $make('1101.002', 'Kas Kecil (Petty Cash)',     'aktiva', 4, '1101', 2, true);
        $make('1101.003', 'Bank BCA',                   'aktiva', 4, '1101', 3, true);
        $make('1101.004', 'Bank BNI',                   'aktiva', 4, '1101', 4, true);
        $make('1101.005', 'Bank Mandiri',               'aktiva', 4, '1101', 5, true);
        $make('1101.006', 'Bank BRI',                   'aktiva', 4, '1101', 6, true);

        // ── Piutang ──
        $make('1102.001', 'Piutang Dagang',             'aktiva', 4, '1102', 1, true);
        $make('1102.002', 'Piutang Karyawan',           'aktiva', 4, '1102', 2, true);
        $make('1102.003', 'Piutang Lain-lain',          'aktiva', 4, '1102', 3, true);
        $make('1102.004', 'Cadangan Kerugian Piutang',  'aktiva', 4, '1102', 4, true);

        // ── Persediaan ──
        $make('1103.001', 'Persediaan Barang Dagang',   'aktiva', 4, '1103', 1, true);
        $make('1103.002', 'Persediaan Bahan Baku',      'aktiva', 4, '1103', 2, true);
        $make('1103.003', 'Persediaan Barang Dalam Proses', 'aktiva', 4, '1103', 3, true);

        // ── Biaya Dibayar Dimuka ──
        $make('1104.001', 'Sewa Dibayar Dimuka',        'aktiva', 4, '1104', 1, true);
        $make('1104.002', 'Asuransi Dibayar Dimuka',    'aktiva', 4, '1104', 2, true);

        // ── Pajak Dibayar Dimuka ──
        $make('1105.001', 'PPN Masukan',                'aktiva', 4, '1105', 1, true);
        $make('1105.002', 'PPh 23 Dibayar Dimuka',      'aktiva', 4, '1105', 2, true);
        $make('1105.003', 'PPh 25 Dibayar Dimuka',      'aktiva', 4, '1105', 3, true);

        // ── Aset Berwujud ──
        $make('1201.001', 'Tanah',                      'aktiva', 4, '1201', 1, true);
        $make('1201.002', 'Bangunan Kantor',            'aktiva', 4, '1201', 2, true);
        $make('1201.003', 'Kendaraan Operasional',      'aktiva', 4, '1201', 3, true);
        $make('1201.004', 'Peralatan Kantor',           'aktiva', 4, '1201', 4, true);
        $make('1201.005', 'Inventaris Kantor',          'aktiva', 4, '1201', 5, true);
        $make('1201.006', 'Mesin & Peralatan Produksi', 'aktiva', 4, '1201', 6, true);

        // ── Akumulasi Penyusutan ──
        $make('1202.001', 'Akum. Penyusutan Bangunan',  'aktiva', 4, '1202', 1, true);
        $make('1202.002', 'Akum. Penyusutan Kendaraan', 'aktiva', 4, '1202', 2, true);
        $make('1202.003', 'Akum. Penyusutan Peralatan', 'aktiva', 4, '1202', 3, true);
        $make('1202.004', 'Akum. Penyusutan Inventaris','aktiva', 4, '1202', 4, true);
        $make('1202.005', 'Akum. Penyusutan Mesin',     'aktiva', 4, '1202', 5, true);

        // ── Aset Tak Berwujud ──
        $make('1301.001', 'Software / Lisensi',         'aktiva', 4, '1301', 1, true);
        $make('1301.002', 'Goodwill',                   'aktiva', 4, '1301', 2, true);
        $make('1302.001', 'Akum. Amortisasi Software',  'aktiva', 4, '1302', 1, true);

        // ── Aset Lain-lain ──
        $make('1401.001', 'Uang Jaminan Sewa',         'aktiva', 4, '1401', 1, true);
        $make('1401.002', 'Deposito Berjangka',         'aktiva', 4, '1401', 2, true);

        // ── Hutang Dagang ──
        $make('2101.001', 'Hutang Usaha / Supplier',    'pasiva', 4, '2101', 1, true);

        // ── Hutang Pajak ──
        $make('2102.001', 'Hutang PPh 21',              'pasiva', 4, '2102', 1, true);
        $make('2102.002', 'Hutang PPh 23',              'pasiva', 4, '2102', 2, true);
        $make('2102.003', 'Hutang PPh 25/29',           'pasiva', 4, '2102', 3, true);
        $make('2102.004', 'Hutang PPN Keluaran',        'pasiva', 4, '2102', 4, true);

        // ── Hutang Gaji & Tunjangan ──
        $make('2103.001', 'Hutang Gaji Karyawan',       'pasiva', 4, '2103', 1, true);
        $make('2103.002', 'Hutang THR',                 'pasiva', 4, '2103', 2, true);
        $make('2103.003', 'Hutang BPJS',                'pasiva', 4, '2103', 3, true);

        // ── Pendapatan Diterima Dimuka ──
        $make('2104.001', 'Uang Muka Pelanggan',        'pasiva', 4, '2104', 1, true);

        // ── Hutang Lain-lain ──
        $make('2105.001', 'Hutang Lain-lain',           'pasiva', 4, '2105', 1, true);

        // ── Hutang Bank ──
        $make('2201.001', 'Kredit Investasi',           'pasiva', 4, '2201', 1, true);
        $make('2201.002', 'Kredit Modal Kerja',         'pasiva', 4, '2201', 2, true);

        // ── Hutang Sewa Pembiayaan ──
        $make('2202.001', 'Hutang Leasing',            'pasiva', 4, '2202', 1, true);

        // ── Modal Pemilik ──
        $make('3101.001', 'Modal Awal',                 'modal', 4, '3101', 1, true);
        $make('3101.002', 'Tambahan Modal Disetor',     'modal', 4, '3101', 2, true);

        // ── Prive ──
        $make('3102.001', 'Prive Pemilik',              'modal', 4, '3102', 1, true);

        // ── Laba ──
        $make('3201.001', 'Laba Bersih Tahun Berjalan', 'modal', 4, '3201', 1, true);
        $make('3202.001', 'Saldo Laba Tahun Lalu',      'modal', 4, '3202', 1, true);

        // ── Pendapatan Penjualan ──
        $make('4101.001', 'Penjualan Barang',           'pendapatan', 4, '4101', 1, true);
        $make('4101.002', 'Penjualan Jasa',             'pendapatan', 4, '4101', 2, true);

        // ── Potongan & Retur Penjualan ──
        $make('4102.001', 'Diskon Penjualan',           'pendapatan', 4, '4102', 1, true);
        $make('4103.001', 'Retur Penjualan',            'pendapatan', 4, '4103', 1, true);

        // ── Pendapatan Lain-lain ──
        $make('4201.001', 'Pendapatan Bunga Bank',      'pendapatan', 4, '4201', 1, true);
        $make('4202.001', 'Pendapatan Sewa',            'pendapatan', 4, '4202', 1, true);
        $make('4203.001', 'Laba Penjualan Aset Tetap',  'pendapatan', 4, '4203', 1, true);
        $make('4204.001', 'Pendapatan Lain-lain',       'pendapatan', 4, '4204', 1, true);

        // ── HPP ──
        $make('5101.001', 'Harga Pokok Penjualan',      'beban', 4, '5101', 1, true);
        $make('5101.002', 'Ongkos Kirim Pembelian',     'beban', 4, '5101', 2, true);

        // ── Beban Penjualan ──
        $make('5201.001', 'Beban Pengiriman',           'beban', 4, '5201', 1, true);
        $make('5201.002', 'Beban Packaging',            'beban', 4, '5201', 2, true);

        // ── Beban Pemasaran ──
        $make('5202.001', 'Beban Iklan & Promosi',      'beban', 4, '5202', 1, true);
        $make('5202.002', 'Beban Marketplace Fee',      'beban', 4, '5202', 2, true);

        // ── Beban Gaji & Upah ──
        $make('5301.001', 'Beban Gaji Karyawan',        'beban', 4, '5301', 1, true);
        $make('5301.002', 'Beban Tunjangan',            'beban', 4, '5301', 2, true);
        $make('5301.003', 'Beban BPJS Perusahaan',      'beban', 4, '5301', 3, true);
        $make('5301.004', 'Beban THR',                  'beban', 4, '5301', 4, true);
        $make('5301.005', 'Beban Lembur',               'beban', 4, '5301', 5, true);

        // ── Beban Utilitas ──
        $make('5302.001', 'Beban Listrik',              'beban', 4, '5302', 1, true);
        $make('5302.002', 'Beban Air / PDAM',           'beban', 4, '5302', 2, true);
        $make('5302.003', 'Beban Telepon & Internet',   'beban', 4, '5302', 3, true);

        // ── Beban Sewa ──
        $make('5303.001', 'Beban Sewa Gedung',          'beban', 4, '5303', 1, true);
        $make('5303.002', 'Beban Sewa Kendaraan',       'beban', 4, '5303', 2, true);

        // ── Beban Penyusutan ──
        $make('5304.001', 'Beban Penyusutan Bangunan',  'beban', 4, '5304', 1, true);
        $make('5304.002', 'Beban Penyusutan Kendaraan', 'beban', 4, '5304', 2, true);
        $make('5304.003', 'Beban Penyusutan Peralatan', 'beban', 4, '5304', 3, true);
        $make('5304.004', 'Beban Penyusutan Inventaris','beban', 4, '5304', 4, true);
        $make('5304.005', 'Beban Penyusutan Mesin',     'beban', 4, '5304', 5, true);

        // ── Beban Amortisasi ──
        $make('5305.001', 'Beban Amortisasi Software',  'beban', 4, '5305', 1, true);

        // ── Beban Administrasi Kantor ──
        $make('5306.001', 'Beban ATK',                  'beban', 4, '5306', 1, true);
        $make('5306.002', 'Beban Konsumsi',             'beban', 4, '5306', 2, true);
        $make('5306.003', 'Beban Fotocopy & Cetak',     'beban', 4, '5306', 3, true);
        $make('5306.004', 'Beban Materai & Pos',        'beban', 4, '5306', 4, true);
        $make('5306.005', 'Beban Perizinan',            'beban', 4, '5306', 5, true);

        // ── Beban Lain-lain ──
        $make('5401.001', 'Beban Bunga Pinjaman',       'beban', 4, '5401', 1, true);
        $make('5401.002', 'Beban Administrasi Bank',    'beban', 4, '5401', 2, true);
        $make('5402.001', 'Rugi Penjualan Aset Tetap',  'beban', 4, '5402', 1, true);
        $make('5403.001', 'Beban PPh Badan',            'beban', 4, '5403', 1, true);
        $make('5404.001', 'Beban Denda & Penalti',      'beban', 4, '5404', 1, true);
        $make('5404.002', 'Beban Lain-lain',            'beban', 4, '5404', 2, true);
    }
}
