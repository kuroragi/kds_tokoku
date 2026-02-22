<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Role::create([
            'name' => 'superadmin',
        ]);
        // User::factory(10)->create();

        $user = User::create([
            'name' => 'Super Admin',
            'username' => 'mysuperadmin',
            'email' => 'superadmin@tokoku.com',
            'password' => bcrypt('@Zaq123qwerty'),
        ])->assignRole('superadmin');

        $this->call([
            COASeeder::class,
            PeriodSeeder::class,
            RolePermissionSeeder::class,
            BusinessUnitSeeder::class,
            UnitOfMeasureSeeder::class,
            PositionSeeder::class,
            SystemSettingsSeeder::class,
        ]);
    } 
}
