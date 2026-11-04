<?php

namespace Database\Factories;

use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShoppingListItem>
 */
class ShoppingListItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shopping_list_id' => ShoppingList::factory(),
            'product_id' => null,
            'product_name' => fake()->words(2, true),
            'category_name' => null,
            'quantity' => 1,
            'checked' => false,
            'checked_at' => null,
            'unit_price' => null,
        ];
    }
}
