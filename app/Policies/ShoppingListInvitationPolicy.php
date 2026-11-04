<?php

namespace App\Policies;

use App\Models\ShoppingListInvitation;
use App\Models\User;

class ShoppingListInvitationPolicy
{
    public function accept(User $user, ShoppingListInvitation $invitation): bool
    {
        return $invitation->invitee_id === $user->id
            && $invitation->isPending();
    }

    public function decline(User $user, ShoppingListInvitation $invitation): bool
    {
        return $invitation->invitee_id === $user->id
            && $invitation->isPending();
    }

    public function delete(User $user, ShoppingListInvitation $invitation): bool
    {
        return $invitation->shoppingList->members()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'owner')
            ->exists();
    }
}
