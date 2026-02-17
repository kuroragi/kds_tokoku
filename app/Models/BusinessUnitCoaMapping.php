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
     * Predefined account keys with labels grouped by COA type
     */
    public static function getAccountKeyDefinitions(): array
    {
        return [
            'aktiva' => [
                ['key' => 'kas_utama', 'label' => 'Kas Utama'],
                ['key' => 'kas_kecil', 'label' => 'Kas Kecil'],
                ['key' => 'bank_utama', 'label' => 'Bank Utama'],
                ['key' => 'piutang_usaha', 'label' => 'Piutang Usaha'],
                ['key' => 'piutang_karyawan', 'label' => 'Piutang Karyawan'],
                ['key' => 'persediaan_barang', 'label' => 'Persediaan Barang Dagang'],
                ['key' => 'perlengkapan', 'label' => 'Perlengkapan'],
                ['key' => 'peralatan', 'label' => 'Peralatan'],
                ['key' => 'akumulasi_penyusutan', 'label' => 'Akumulasi Penyusutan'],
            ],
            'pasiva' => [
                ['key' => 'hutang_usaha', 'label' => 'Hutang Usaha'],
                ['key' => 'hutang_bank', 'label' => 'Hutang Bank'],
                ['key' => 'hutang_pajak', 'label' => 'Hutang Pajak'],
                ['key' => 'hutang_gaji', 'label' => 'Hutang Gaji'],
            ],
            'modal' => [
                ['key' => 'modal_pemilik', 'label' => 'Modal Pemilik'],
                ['key' => 'laba_ditahan', 'label' => 'Laba Ditahan'],
                ['key' => 'prive', 'label' => 'Prive (Pengambilan Pribadi)'],
            ],
            'pendapatan' => [
                ['key' => 'pendapatan_utama', 'label' => 'Pendapatan Utama'],
                ['key' => 'pendapatan_jasa', 'label' => 'Pendapatan Jasa'],
                ['key' => 'pendapatan_lain', 'label' => 'Pendapatan Lain-lain'],
                ['key' => 'diskon_penjualan', 'label' => 'Diskon Penjualan'],
                ['key' => 'retur_penjualan', 'label' => 'Retur Penjualan'],
            ],
            'beban' => [
                ['key' => 'beban_gaji', 'label' => 'Beban Gaji'],
                ['key' => 'beban_sewa', 'label' => 'Beban Sewa'],
                ['key' => 'beban_listrik', 'label' => 'Beban Listrik & Air'],
                ['key' => 'beban_telepon', 'label' => 'Beban Telepon & Internet'],
                ['key' => 'beban_perlengkapan', 'label' => 'Beban Perlengkapan'],
                ['key' => 'beban_penyusutan', 'label' => 'Beban Penyusutan'],
                ['key' => 'beban_transportasi', 'label' => 'Beban Transportasi'],
                ['key' => 'beban_lain', 'label' => 'Beban Lain-lain'],
                ['key' => 'hpp', 'label' => 'Harga Pokok Penjualan (HPP)'],
            ],
        ];
    }
}
