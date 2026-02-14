<?php

namespace Database\Factories;

use App\Models\Journal;
use App\Models\JournalMaster;
use App\Models\COA;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Journal>
 */
class JournalFactory extends Factory
{
    protected $model = Journal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_journal_master' => JournalMaster::factory(),
            'id_coa' => COA::factory(),
            'description' => $this->faker->optional()->sentence(),
            'debit' => 0,
            'credit' => 0,
            'sequence' => 1,
        ];
    }

    /**
     * Create a debit entry.
     */
    public function debit(int $amount = 1000000): static
    {
        return $this->state(fn (array $attributes) => [
            'debit' => $amount,
            'credit' => 0,
        ]);
    }

    /**
     * Create a credit entry.
     */
    public function credit(int $amount = 1000000): static
    {
        return $this->state(fn (array $attributes) => [
            'debit' => 0,
            'credit' => $amount,
        ]);
    }
}
