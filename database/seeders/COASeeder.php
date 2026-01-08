<?php

namespace Database\Seeders;

use App\Models\COA;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class COASeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Login as super admin for blameable trait
        $superAdmin = \App\Models\User::where('email', 'superadmin@tokoku.com')->first();
        if ($superAdmin) {
            auth()->login($superAdmin);
        }

        $coas = [
            // Assets
            ['code' => '1000', 'name' => 'ASET', 'type' => 'asset', 'level' => 1, 'parent_id' => null],
            ['code' => '1100', 'name' => 'Aset Lancar', 'type' => 'asset', 'level' => 2, 'parent_id' => null],
            ['code' => '1101', 'name' => 'Kas di Tangan', 'type' => 'asset', 'level' => 3, 'parent_id' => null],
            ['code' => '1102', 'name' => 'Kas di Bank', 'type' => 'asset', 'level' => 3, 'parent_id' => null],
            ['code' => '1201', 'name' => 'Piutang Dagang', 'type' => 'asset', 'level' => 3, 'parent_id' => null],
            ['code' => '1301', 'name' => 'Persediaan Barang', 'type' => 'asset', 'level' => 3, 'parent_id' => null],

            // Liabilities
            ['code' => '2000', 'name' => 'KEWAJIBAN', 'type' => 'liability', 'level' => 1, 'parent_id' => null],
            ['code' => '2100', 'name' => 'Kewajiban Lancar', 'type' => 'liability', 'level' => 2, 'parent_id' => null],
            ['code' => '2101', 'name' => 'Hutang Dagang', 'type' => 'liability', 'level' => 3, 'parent_id' => null],
            ['code' => '2201', 'name' => 'Hutang Pajak', 'type' => 'liability', 'level' => 3, 'parent_id' => null],

            // Equity
            ['code' => '3000', 'name' => 'MODAL', 'type' => 'equity', 'level' => 1, 'parent_id' => null],
            ['code' => '3101', 'name' => 'Modal Saham', 'type' => 'equity', 'level' => 3, 'parent_id' => null],
            ['code' => '3201', 'name' => 'Laba Ditahan', 'type' => 'equity', 'level' => 3, 'parent_id' => null],

            // Revenue
            ['code' => '4000', 'name' => 'PENDAPATAN', 'type' => 'revenue', 'level' => 1, 'parent_id' => null],
            ['code' => '4101', 'name' => 'Pendapatan Penjualan', 'type' => 'revenue', 'level' => 3, 'parent_id' => null],
            ['code' => '4201', 'name' => 'Pendapatan Jasa', 'type' => 'revenue', 'level' => 3, 'parent_id' => null],

            // Expenses
            ['code' => '5000', 'name' => 'BEBAN', 'type' => 'expense', 'level' => 1, 'parent_id' => null],
            ['code' => '5101', 'name' => 'Beban Pokok Penjualan', 'type' => 'expense', 'level' => 3, 'parent_id' => null],
            ['code' => '5201', 'name' => 'Beban Operasional', 'type' => 'expense', 'level' => 3, 'parent_id' => null],
            ['code' => '5301', 'name' => 'Beban Administrasi', 'type' => 'expense', 'level' => 3, 'parent_id' => null],
        ];

        foreach ($coas as $coa) {
            COA::create($coa);
        }
    }
}
