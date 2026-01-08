<?php

namespace Database\Seeders;

use App\Models\Period;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PeriodSeeder extends Seeder
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

        // Generate periods for 2024 and 2025
        $years = [2024, 2025, 2026];
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        foreach ($years as $year) {
            foreach ($months as $month => $monthName) {
                $startDate = Carbon::create($year, $month, 1);
                $endDate = $startDate->copy()->endOfMonth();
                
                // Set current period as active
                $isActive = ($year == 2026 && $month == 1);
                
                Period::create([
                    'code' => $year . str_pad($month, 2, '0', STR_PAD_LEFT),
                    'name' => $monthName . ' ' . $year,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'year' => $year,
                    'month' => $month,
                    'is_active' => $isActive,
                    'is_closed' => $year < 2026,
                    'description' => 'Periode ' . $monthName . ' ' . $year,
                ]);
            }
        }
    }
}
