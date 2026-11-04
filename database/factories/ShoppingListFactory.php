<?php

namespace Database\Factories;

use App\Enums\ShoppingListStatus;
use App\Models\ShoppingList;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShoppingList>
 */
class ShoppingListFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->words(3, true),
            'status' => ShoppingListStatus::Open,
            'completed_at' => null,
            'total_amount' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ShoppingListStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (ShoppingList $list): void {
            $list->members()->attach($list->workspace->owner_id, ['role' => 'owner']);
        });
    }
}
