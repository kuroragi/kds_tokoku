<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * BusinessUnitSeeder
 *
 * Dummy data sudah dihapus. Unit usaha sekarang dibuat oleh pengguna
 * saat registrasi, dan COA otomatis di-clone dari coa_templates
 * melalui BusinessUnitObserver.
 */
class BusinessUnitSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: business units are created by users at registration time.
    }
}
