<?php

namespace Database\Factories;

use App\Models\Period;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Period>
 */
class PeriodFactory extends Factory
{
    protected $model = Period::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = now()->year;
        $month = now()->month;
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        return [
            'code' => $year . str_pad($month, 2, '0', STR_PAD_LEFT),
            'name' => $startDate->translatedFormat('F') . ' ' . $year,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'year' => $year,
            'month' => $month,
            'is_active' => true,
            'is_closed' => false,
            'description' => 'Periode ' . $startDate->translatedFormat('F') . ' ' . $year,
        ];
    }

    /**
     * Create a period for a specific year and month.
     */
    public function forMonth(int $year, int $month): static
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        return $this->state(fn (array $attributes) => [
            'code' => $year . str_pad($month, 2, '0', STR_PAD_LEFT),
            'name' => $startDate->translatedFormat('F') . ' ' . $year,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'year' => $year,
            'month' => $month,
        ]);
    }

    /**
     * Create a closed period.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_closed' => true,
            'closed_at' => now(),
        ]);
    }

    /**
     * Create an inactive period.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
