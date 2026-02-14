<?php

namespace Database\Factories;

use App\Models\COA;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\COA>
 */
class COAFactory extends Factory
{
    protected $model = COA::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numerify('####'),
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['aktiva', 'pasiva', 'modal', 'pendapatan', 'beban']),
            'parent_code' => null,
            'level' => 2,
            'order' => $this->faker->numberBetween(1, 100),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
            'is_leaf_account' => true,
        ];
    }

    /**
     * Create a parent account.
     */
    public function parent(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 1,
            'is_leaf_account' => false,
        ]);
    }

    /**
     * Create a cash account (Kas).
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => '1101',
            'name' => 'Kas di Tangan',
            'type' => 'aktiva',
            'is_leaf_account' => true,
        ]);
    }

    /**
     * Create a revenue account.
     */
    public function revenue(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => '4101',
            'name' => 'Pendapatan Penjualan',
            'type' => 'pendapatan',
            'is_leaf_account' => true,
        ]);
    }

    /**
     * Create an inactive account.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
