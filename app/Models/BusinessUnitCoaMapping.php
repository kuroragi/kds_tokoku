<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessUnitCoaMapping extends Model
{
    protected $fillable = [
        'business_unit_id',
        'account_key',
        'label',
        'coa_id',
    ];

    // Relationships
    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function coa()
    {
        return $this->belongsTo(COA::class, 'coa_id');
    }

    /**
     * Predefined account keys with labels grouped by COA type.
     *
     * Setiap key digunakan oleh service-service dalam aplikasi untuk
     * menentukan akun COA mana yang di-debit/credit saat membuat jurnal.
     */
    public static function getAccountKeyDefinitions(): array
    {
        return [
            'aktiva' => [
                // Kas & Bank
                ['key' => 'kas_utama', 'label' => 'Kas Utama'],
                ['key' => 'kas_kecil', 'label' => 'Kas Kecil'],
                ['key' => 'bank_utama', 'label' => 'Bank Utama'],
                // Piutang
                ['key' => 'piutang_usaha', 'label' => 'Piutang Usaha'],
                ['key' => 'piutang_karyawan', 'label' => 'Piutang Karyawan'],
                ['key' => 'piutang_lain', 'label' => 'Piutang Lain-lain'],
                // Persediaan
                ['key' => 'persediaan_barang', 'label' => 'Persediaan Barang Dagang'],
                // Biaya Dibayar Dimuka & Perlengkapan
                ['key' => 'perlengkapan', 'label' => 'Perlengkapan'],
                ['key' => 'sewa_dibayar_dimuka', 'label' => 'Sewa Dibayar Dimuka'],
                ['key' => 'asuransi_dibayar_dimuka', 'label' => 'Asuransi Dibayar Dimuka'],
                // Pajak Dibayar Dimuka
                ['key' => 'ppn_masukan', 'label' => 'PPN Masukan'],
                // Aset Tetap (Berwujud)
                ['key' => 'aset_tanah', 'label' => 'Tanah'],
                ['key' => 'aset_bangunan', 'label' => 'Bangunan'],
                ['key' => 'aset_kendaraan', 'label' => 'Kendaraan'],
                ['key' => 'aset_peralatan', 'label' => 'Peralatan Kantor'],
                ['key' => 'aset_inventaris', 'label' => 'Inventaris Kantor'],
                ['key' => 'aset_mesin', 'label' => 'Mesin & Peralatan Produksi'],
                // Akumulasi Penyusutan
                ['key' => 'akum_peny_bangunan', 'label' => 'Akum. Penyusutan Bangunan'],
                ['key' => 'akum_peny_kendaraan', 'label' => 'Akum. Penyusutan Kendaraan'],
                ['key' => 'akum_peny_peralatan', 'label' => 'Akum. Penyusutan Peralatan'],
                ['key' => 'akum_peny_inventaris', 'label' => 'Akum. Penyusutan Inventaris'],
                ['key' => 'akum_peny_mesin', 'label' => 'Akum. Penyusutan Mesin'],
            ],
            'pasiva' => [
                ['key' => 'hutang_usaha', 'label' => 'Hutang Usaha / Supplier'],
                ['key' => 'hutang_pajak', 'label' => 'Hutang Pajak (PPh)'],
                ['key' => 'hutang_ppn', 'label' => 'Hutang PPN Keluaran'],
                ['key' => 'hutang_gaji', 'label' => 'Hutang Gaji Karyawan'],
                ['key' => 'hutang_bpjs', 'label' => 'Hutang BPJS'],
                ['key' => 'hutang_lain', 'label' => 'Hutang Lain-lain'],
                ['key' => 'hutang_bank', 'label' => 'Hutang Bank'],
                ['key' => 'hutang_leasing', 'label' => 'Hutang Leasing'],
                ['key' => 'pendapatan_diterima_dimuka', 'label' => 'Pendapatan Diterima Dimuka'],
            ],
            'modal' => [
                ['key' => 'modal_pemilik', 'label' => 'Modal Pemilik'],
                ['key' => 'tambahan_modal', 'label' => 'Tambahan Modal Disetor'],
                ['key' => 'prive', 'label' => 'Prive (Pengambilan Pribadi)'],
                ['key' => 'laba_ditahan', 'label' => 'Laba Ditahan / Tahun Lalu'],
            ],
            'pendapatan' => [
                ['key' => 'pendapatan_utama', 'label' => 'Pendapatan Penjualan Barang'],
                ['key' => 'pendapatan_jasa', 'label' => 'Pendapatan Jasa'],
                ['key' => 'pendapatan_lain', 'label' => 'Pendapatan Lain-lain'],
                ['key' => 'diskon_penjualan', 'label' => 'Diskon Penjualan'],
                ['key' => 'retur_penjualan', 'label' => 'Retur Penjualan'],
                ['key' => 'laba_penjualan_aset', 'label' => 'Laba Penjualan Aset Tetap'],
            ],
            'beban' => [
                // HPP
                ['key' => 'hpp', 'label' => 'Harga Pokok Penjualan (HPP)'],
                // Beban Operasional
                ['key' => 'beban_gaji', 'label' => 'Beban Gaji & Upah'],
                ['key' => 'beban_tunjangan', 'label' => 'Beban Tunjangan'],
                ['key' => 'beban_bpjs', 'label' => 'Beban BPJS Perusahaan'],
                ['key' => 'beban_sewa', 'label' => 'Beban Sewa'],
                ['key' => 'beban_listrik', 'label' => 'Beban Listrik & Air'],
                ['key' => 'beban_telepon', 'label' => 'Beban Telepon & Internet'],
                ['key' => 'beban_perlengkapan', 'label' => 'Beban Perlengkapan'],
                ['key' => 'beban_transportasi', 'label' => 'Beban Transportasi'],
                ['key' => 'beban_iklan', 'label' => 'Beban Iklan & Promosi'],
                // Beban Penyusutan per kategori
                ['key' => 'beban_peny_bangunan', 'label' => 'Beban Penyusutan Bangunan'],
                ['key' => 'beban_peny_kendaraan', 'label' => 'Beban Penyusutan Kendaraan'],
                ['key' => 'beban_peny_peralatan', 'label' => 'Beban Penyusutan Peralatan'],
                ['key' => 'beban_peny_inventaris', 'label' => 'Beban Penyusutan Inventaris'],
                ['key' => 'beban_peny_mesin', 'label' => 'Beban Penyusutan Mesin'],
                // Lain-lain
                ['key' => 'beban_admin_bank', 'label' => 'Beban Administrasi Bank'],
                ['key' => 'beban_bunga', 'label' => 'Beban Bunga Pinjaman'],
                ['key' => 'beban_pajak', 'label' => 'Beban Pajak (PPh Badan)'],
                ['key' => 'beban_lain', 'label' => 'Beban Lain-lain'],
                ['key' => 'rugi_penjualan_aset', 'label' => 'Rugi Penjualan Aset Tetap'],
            ],
        ];
    }
}
