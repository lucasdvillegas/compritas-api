<?php

namespace Database\Factories;

use App\Enums\ShoppingListInvitationStatus;
use App\Models\ShoppingList;
use App\Models\ShoppingListInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShoppingListInvitation>
 */
class ShoppingListInvitationFactory extends Factory
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
            'inviter_id' => User::factory(),
            'invitee_id' => User::factory(),
            'status' => ShoppingListInvitationStatus::Pending,
            'responded_at' => null,
        ];
    }
}
