<?php

namespace Database\Factories;

use App\Models\JournalMaster;
use App\Models\Period;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JournalMaster>
 */
class JournalMasterFactory extends Factory
{
    protected $model = JournalMaster::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-3 months', 'now');
        $amount = $this->faker->numberBetween(100000, 10000000);

        return [
            'journal_no' => 'JRN/' . $date->format('Y') . '/' . $date->format('m') . '/' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'journal_date' => $date->format('Y-m-d'),
            'reference' => $this->faker->optional()->bothify('REF-####'),
            'description' => $this->faker->optional()->sentence(),
            'id_period' => Period::factory(),
            'total_debit' => $amount,
            'total_credit' => $amount,
            'status' => 'draft',
            'posted_at' => null,
        ];
    }

    /**
     * Indicate that the journal is posted.
     */
    public function posted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'posted',
            'posted_at' => now(),
        ]);
    }

    /**
     * Indicate that the journal is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
