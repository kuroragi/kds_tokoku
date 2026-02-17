<?php

namespace Database\Seeders;

use App\Models\PayrollSetting;
use App\Models\SalaryComponent;
use App\Models\BusinessUnit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class PayrollSettingSeeder extends Seeder
{
    /**
     * Seed default payroll settings and salary components for all business units.
     */
    public function run(): void
    {
        $superAdmin = User::where('email', 'superadmin@tokoku.com')->first();
        if ($superAdmin) {
            Auth::login($superAdmin);
        }

        $businessUnits = BusinessUnit::all();

        foreach ($businessUnits as $bu) {
            PayrollSetting::seedDefaultsForBusinessUnit($bu->id);
            SalaryComponent::seedDefaultsForBusinessUnit($bu->id);
        }
    }
}
