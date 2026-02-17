<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Seed the system default positions.
     */
    public function run(): void
    {
        $defaults = Position::getSystemDefaults();

        foreach ($defaults as $default) {
            Position::firstOrCreate(
                [
                    'code' => $default['code'],
                    'business_unit_id' => null,
                ],
                [
                    'name' => $default['name'],
                    'description' => "Jabatan bawaan sistem: {$default['name']}",
                    'is_system_default' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
