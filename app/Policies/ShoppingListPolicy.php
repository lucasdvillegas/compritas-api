<?php

namespace App\Policies;

use App\Enums\ShoppingListStatus;
use App\Models\ShoppingList;
use App\Models\User;

class ShoppingListPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ShoppingList $shoppingList): bool
    {
        return $this->isMember($user, $shoppingList);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ShoppingList $shoppingList): bool
    {
        return $this->isOwner($user, $shoppingList)
            && $shoppingList->status === ShoppingListStatus::Open;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ShoppingList $shoppingList): bool
    {
        return $this->isOwner($user, $shoppingList);
    }

    public function complete(User $user, ShoppingList $shoppingList): bool
    {
        return $this->isMember($user, $shoppingList)
            && $shoppingList->status === ShoppingListStatus::Open;
    }

    public function manageItems(User $user, ShoppingList $shoppingList): bool
    {
        return $this->isMember($user, $shoppingList)
            && $shoppingList->status === ShoppingListStatus::Open;
    }

    public function manageMembers(User $user, ShoppingList $shoppingList): bool
    {
        return $this->isOwner($user, $shoppingList)
            && $shoppingList->status === ShoppingListStatus::Open;
    }

    private function isMember(User $user, ShoppingList $shoppingList): bool
    {
        return $shoppingList->members()
            ->where('users.id', $user->id)
            ->exists();
    }

    private function isOwner(User $user, ShoppingList $shoppingList): bool
    {
        return $shoppingList->members()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'owner')
            ->exists();
    }
}
