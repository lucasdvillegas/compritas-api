<?php

namespace App\Notifications;

use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ListCompleted extends Notification
{
    use Queueable;

    public function __construct(
        public ShoppingList $shoppingList,
        public User $actor,
    ) {}

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
        return [
            'type' => 'list_completed',
            'message' => "{$this->actor->name} marcó {$this->shoppingList->name} como completa.",
            'list_id' => $this->shoppingList->id,
            'list_name' => $this->shoppingList->name,
            'actor_name' => $this->actor->name,
        ];
    }
}
