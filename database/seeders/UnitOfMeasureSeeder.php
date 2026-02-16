<?php

namespace Database\Seeders;

use App\Models\UnitOfMeasure;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitOfMeasureSeeder extends Seeder
{
    /**
     * Seed the system default unit of measures.
     */
    public function run(): void
    {
        $defaults = UnitOfMeasure::getSystemDefaults();

        foreach ($defaults as $default) {
            UnitOfMeasure::firstOrCreate(
                [
                    'code' => $default['code'],
                    'business_unit_id' => null,
                ],
                [
                    'name' => $default['name'],
                    'symbol' => $default['symbol'],
                    'description' => "Satuan bawaan sistem: {$default['name']}",
                    'is_system_default' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
