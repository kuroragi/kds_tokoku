<?php

namespace Database\Seeders;

use App\Models\COA;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

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
            Auth::login($superAdmin);
        }

        // Create COAs with proper hierarchy and order
        $parentIds = [];

        // Level 1 - Main Categories
        $level1Coas = [
            ['code' => '1000', 'name' => 'ASET', 'type' => 'aktiva', 'level' => 1, 'parent_code' => null, 'order' => 1],
            ['code' => '2000', 'name' => 'KEWAJIBAN', 'type' => 'pasiva', 'level' => 1, 'parent_code' => null, 'order' => 2],
            ['code' => '3000', 'name' => 'MODAL', 'type' => 'modal', 'level' => 1, 'parent_code' => null, 'order' => 3],
            ['code' => '4000', 'name' => 'PENDAPATAN', 'type' => 'pendapatan', 'level' => 1, 'parent_code' => null, 'order' => 4],
            ['code' => '5000', 'name' => 'BEBAN', 'type' => 'beban', 'level' => 1, 'parent_code' => null, 'order' => 5],
        ];

        foreach ($level1Coas as $coa) {
            $createdCoa = COA::create([
                'code' => $coa['code'],
                'name' => $coa['name'],
                'type' => $coa['type'],
                'level' => $coa['level'],
                'parent_code' => $coa['parent_code'],
                'order' => $coa['order'],
                'is_leaf_account' => false, // Level 1 is always parent
            ]);
            $parentIds[$coa['code']] = $createdCoa->id;
        }

        // Level 2 - Sub Categories
        $level2Coas = [
            // Assets
            ['code' => '1100', 'name' => 'Aset Lancar', 'type' => 'aktiva', 'level' => 2, 'parent_code' => '1000', 'order' => 1],
            ['code' => '1200', 'name' => 'Aset Tetap', 'type' => 'aktiva', 'level' => 2, 'parent_code' => '1000', 'order' => 2],
            
            // Liabilities
            ['code' => '2100', 'name' => 'Kewajiban Lancar', 'type' => 'pasiva', 'level' => 2, 'parent_code' => '2000', 'order' => 1],
            ['code' => '2200', 'name' => 'Kewajiban Jangka Panjang', 'type' => 'pasiva', 'level' => 2, 'parent_code' => '2000', 'order' => 2],
        ];

        foreach ($level2Coas as $coa) {
            $coaData = [
                'code' => $coa['code'],
                'name' => $coa['name'],
                'type' => $coa['type'],
                'level' => $coa['level'],
                'parent_code' => $parentIds[$coa['parent_code']],
                'order' => $coa['order'],
                'is_leaf_account' => false, // Level 2 is parent for level 3
            ];
            $created = COA::create($coaData);
            $parentIds[$coa['code']] = $created->id;
        }

        // Level 3 - Account Groups
        $level3Coas = [
            // Current Assets Groups
            ['code' => '1101', 'name' => 'Kas dan Setara Kas', 'type' => 'aktiva', 'level' => 3, 'parent_code' => '1100', 'order' => 1],
            ['code' => '1102', 'name' => 'Piutang', 'type' => 'aktiva', 'level' => 3, 'parent_code' => '1100', 'order' => 2],
            ['code' => '1103', 'name' => 'Persediaan', 'type' => 'aktiva', 'level' => 3, 'parent_code' => '1100', 'order' => 3],
            ['code' => '1104', 'name' => 'Biaya Dibayar Dimuka', 'type' => 'aktiva', 'level' => 3, 'parent_code' => '1100', 'order' => 4],
            
            // Fixed Assets Groups
            ['code' => '1201', 'name' => 'Tanah', 'type' => 'aktiva', 'level' => 3, 'parent_code' => '1200', 'order' => 1],
            ['code' => '1202', 'name' => 'Bangunan', 'type' => 'aktiva', 'level' => 3, 'parent_code' => '1200', 'order' => 2],
            ['code' => '1203', 'name' => 'Kendaraan', 'type' => 'aktiva', 'level' => 3, 'parent_code' => '1200', 'order' => 3],
            ['code' => '1204', 'name' => 'Peralatan', 'type' => 'aktiva', 'level' => 3, 'parent_code' => '1200', 'order' => 4],
            
            // Current Liabilities Groups
            ['code' => '2101', 'name' => 'Hutang Dagang', 'type' => 'pasiva', 'level' => 3, 'parent_code' => '2100', 'order' => 1],
            ['code' => '2102', 'name' => 'Hutang Pajak', 'type' => 'pasiva', 'level' => 3, 'parent_code' => '2100', 'order' => 2],
            ['code' => '2103', 'name' => 'Hutang Gaji', 'type' => 'pasiva', 'level' => 3, 'parent_code' => '2100', 'order' => 3],
            
            // Long Term Liabilities Groups
            ['code' => '2201', 'name' => 'Hutang Bank Jangka Panjang', 'type' => 'pasiva', 'level' => 3, 'parent_code' => '2200', 'order' => 1],
            
            // Equity Groups (directly under main category)
            ['code' => '3101', 'name' => 'Modal Saham', 'type' => 'modal', 'level' => 3, 'parent_code' => '3000', 'order' => 1],
            ['code' => '3201', 'name' => 'Laba Ditahan', 'type' => 'modal', 'level' => 3, 'parent_code' => '3000', 'order' => 2],
            
            // Revenue Groups (directly under main category)
            ['code' => '4101', 'name' => 'Pendapatan Penjualan', 'type' => 'pendapatan', 'level' => 3, 'parent_code' => '4000', 'order' => 1],
            ['code' => '4201', 'name' => 'Pendapatan Lain-lain', 'type' => 'pendapatan', 'level' => 3, 'parent_code' => '4000', 'order' => 2],
            
            // Expense Groups (directly under main category)
            ['code' => '5101', 'name' => 'Beban Pokok Penjualan', 'type' => 'beban', 'level' => 3, 'parent_code' => '5000', 'order' => 1],
            ['code' => '5201', 'name' => 'Beban Operasional', 'type' => 'beban', 'level' => 3, 'parent_code' => '5000', 'order' => 2],
            ['code' => '5301', 'name' => 'Beban Administrasi', 'type' => 'beban', 'level' => 3, 'parent_code' => '5000', 'order' => 3],
        ];

        foreach ($level3Coas as $coa) {
            $coaData = [
                'code' => $coa['code'],
                'name' => $coa['name'],
                'type' => $coa['type'],
                'level' => $coa['level'],
                'parent_code' => $parentIds[$coa['parent_code']],
                'order' => $coa['order'],
                'is_leaf_account' => false, // Level 3 is still parent for level 4
            ];
            $created = COA::create($coaData);
            $parentIds[$coa['code']] = $created->id;
        }
        
        // Level 4 - Detail Accounts (Leaf Accounts)
        $level4Coas = [
            // Cash and Cash Equivalents Details
            ['code' => '1101.001', 'name' => 'Kas di Tangan', 'type' => 'aktiva', 'level' => 4, 'parent_code' => '1101', 'order' => 1],
            ['code' => '1101.002', 'name' => 'Bank BNI', 'type' => 'aktiva', 'level' => 4, 'parent_code' => '1101', 'order' => 2],
            ['code' => '1101.003', 'name' => 'Bank BCA', 'type' => 'aktiva', 'level' => 4, 'parent_code' => '1101', 'order' => 3],
            ['code' => '1101.004', 'name' => 'Bank Mandiri', 'type' => 'aktiva', 'level' => 4, 'parent_code' => '1101', 'order' => 4],
            
            // Receivables Details
            ['code' => '1102.001', 'name' => 'Piutang Dagang', 'type' => 'aktiva', 'level' => 4, 'parent_code' => '1102', 'order' => 1],
            ['code' => '1102.002', 'name' => 'Piutang Karyawan', 'type' => 'aktiva', 'level' => 4, 'parent_code' => '1102', 'order' => 2],
            ['code' => '1102.003', 'name' => 'Piutang Lain-lain', 'type' => 'aktiva', 'level' => 4, 'parent_code' => '1102', 'order' => 3],
            
            // Inventory Details
            ['code' => '1103.001', 'name' => 'Persediaan Barang Dagang', 'type' => 'aktiva', 'level' => 4, 'parent_code' => '1103', 'order' => 1],
            ['code' => '1103.002', 'name' => 'Persediaan Bahan Baku', 'type' => 'aktiva', 'level' => 4, 'parent_code' => '1103', 'order' => 2],
            
            // Prepaid Expenses Details
            ['code' => '1104.001', 'name' => 'Sewa Dibayar Dimuka', 'type' => 'aktiva', 'level' => 4, 'parent_code' => '1104', 'order' => 1],
            ['code' => '1104.002', 'name' => 'Asuransi Dibayar Dimuka', 'type' => 'aktiva', 'level' => 4, 'parent_code' => '1104', 'order' => 2],
            
            // Fixed Assets Details (Note: These are leaf accounts without depreciation for simplicity)
            ['code' => '1201.001', 'name' => 'Tanah Kantor', 'type' => 'aktiva', 'level' => 4, 'parent_code' => '1201', 'order' => 1],
            ['code' => '1202.001', 'name' => 'Gedung Kantor', 'type' => 'aktiva', 'level' => 4, 'parent_code' => '1202', 'order' => 1],
            ['code' => '1203.001', 'name' => 'Mobil Operasional', 'type' => 'aktiva', 'level' => 4, 'parent_code' => '1203', 'order' => 1],
            ['code' => '1204.001', 'name' => 'Komputer', 'type' => 'aktiva', 'level' => 4, 'parent_code' => '1204', 'order' => 1],
            ['code' => '1204.002', 'name' => 'Mesin Fotocopy', 'type' => 'aktiva', 'level' => 4, 'parent_code' => '1204', 'order' => 2],
            
            // Current Liabilities Details
            ['code' => '2101.001', 'name' => 'Hutang Supplier A', 'type' => 'pasiva', 'level' => 4, 'parent_code' => '2101', 'order' => 1],
            ['code' => '2101.002', 'name' => 'Hutang Supplier B', 'type' => 'pasiva', 'level' => 4, 'parent_code' => '2101', 'order' => 2],
            ['code' => '2102.001', 'name' => 'Hutang PPh 21', 'type' => 'pasiva', 'level' => 4, 'parent_code' => '2102', 'order' => 1],
            ['code' => '2102.002', 'name' => 'Hutang PPN', 'type' => 'pasiva', 'level' => 4, 'parent_code' => '2102', 'order' => 2],
            ['code' => '2103.001', 'name' => 'Hutang Gaji Karyawan', 'type' => 'pasiva', 'level' => 4, 'parent_code' => '2103', 'order' => 1],
            
            // Long Term Liabilities Details
            ['code' => '2201.001', 'name' => 'KPR Bank BNI', 'type' => 'pasiva', 'level' => 4, 'parent_code' => '2201', 'order' => 1],
            
            // Equity Details
            ['code' => '3101.001', 'name' => 'Modal Saham Biasa', 'type' => 'modal', 'level' => 4, 'parent_code' => '3101', 'order' => 1],
            ['code' => '3201.001', 'name' => 'Laba Tahun Berjalan', 'type' => 'modal', 'level' => 4, 'parent_code' => '3201', 'order' => 1],
            ['code' => '3201.002', 'name' => 'Laba Tahun Lalu', 'type' => 'modal', 'level' => 4, 'parent_code' => '3201', 'order' => 2],
            
            // Revenue Details
            ['code' => '4101.001', 'name' => 'Penjualan Produk A', 'type' => 'pendapatan', 'level' => 4, 'parent_code' => '4101', 'order' => 1],
            ['code' => '4101.002', 'name' => 'Penjualan Produk B', 'type' => 'pendapatan', 'level' => 4, 'parent_code' => '4101', 'order' => 2],
            ['code' => '4201.001', 'name' => 'Pendapatan Bunga Bank', 'type' => 'pendapatan', 'level' => 4, 'parent_code' => '4201', 'order' => 1],
            ['code' => '4201.002', 'name' => 'Pendapatan Sewa', 'type' => 'pendapatan', 'level' => 4, 'parent_code' => '4201', 'order' => 2],
            
            // Expense Details
            ['code' => '5101.001', 'name' => 'Harga Pokok Penjualan', 'type' => 'beban', 'level' => 4, 'parent_code' => '5101', 'order' => 1],
            ['code' => '5201.001', 'name' => 'Beban Listrik', 'type' => 'beban', 'level' => 4, 'parent_code' => '5201', 'order' => 1],
            ['code' => '5201.002', 'name' => 'Beban Telepon', 'type' => 'beban', 'level' => 4, 'parent_code' => '5201', 'order' => 2],
            ['code' => '5201.003', 'name' => 'Beban Internet', 'type' => 'beban', 'level' => 4, 'parent_code' => '5201', 'order' => 3],
            ['code' => '5301.001', 'name' => 'Beban Gaji Administrasi', 'type' => 'beban', 'level' => 4, 'parent_code' => '5301', 'order' => 1],
            ['code' => '5301.002', 'name' => 'Beban ATK', 'type' => 'beban', 'level' => 4, 'parent_code' => '5301', 'order' => 2],
            ['code' => '5301.003', 'name' => 'Beban Konsumsi', 'type' => 'beban', 'level' => 4, 'parent_code' => '5301', 'order' => 3],
        ];

        foreach ($level4Coas as $coa) {
            $coaData = [
                'code' => $coa['code'],
                'name' => $coa['name'],
                'type' => $coa['type'],
                'level' => $coa['level'],
                'parent_code' => $parentIds[$coa['parent_code']],
                'order' => $coa['order'],
                'is_leaf_account' => true, // Level 4 is leaf account for transactions
            ];
            COA::create($coaData);
        }
    }
}
