<?php

namespace App\Notifications;

use App\Models\ShoppingListInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ListInvitationReceived extends Notification
{
    use Queueable;

    public function __construct(public ShoppingListInvitation $invitation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->invitation->loadMissing(['shoppingList', 'inviter']);

        return [
            'type' => 'list_invitation',
            'message' => "{$this->invitation->inviter->name} te invitó a participar en {$this->invitation->shoppingList->name}.",
            'list_id' => $this->invitation->shopping_list_id,
            'list_name' => $this->invitation->shoppingList->name,
            'invitation_id' => $this->invitation->id,
            'actor_name' => $this->invitation->inviter->name,
        ];
    }
}
