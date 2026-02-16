<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use Illuminate\Database\Seeder;

class BusinessUnitSeeder extends Seeder
{
    public function run(): void
    {
        BusinessUnit::create([
            'code' => 'UNT-001',
            'name' => 'Toko Sejahtera Pusat',
            'owner_name' => 'Ahmad Susanto',
            'phone' => '081234567890',
            'email' => 'pusat@tokosejahtera.com',
            'address' => 'Jl. Merdeka No. 1',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'postal_code' => '10110',
            'tax_id' => '01.234.567.8-901.000',
            'business_type' => 'toko',
            'description' => 'Toko utama / pusat',
            'is_active' => true,
        ]);

        BusinessUnit::create([
            'code' => 'UNT-002',
            'name' => 'Toko Sejahtera Cabang Bandung',
            'owner_name' => 'Budi Santoso',
            'phone' => '081234567891',
            'email' => 'bandung@tokosejahtera.com',
            'address' => 'Jl. Asia Afrika No. 10',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'postal_code' => '40111',
            'business_type' => 'toko',
            'description' => 'Cabang Bandung',
            'is_active' => true,
        ]);
    }
}
