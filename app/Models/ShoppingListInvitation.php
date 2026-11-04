<?php

namespace App\Models;

use App\Enums\ShoppingListInvitationStatus;
use Database\Factories\ShoppingListInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shopping_list_id',
    'inviter_id',
    'invitee_id',
    'status',
    'responded_at',
])]
class ShoppingListInvitation extends Model
{
    /** @use HasFactory<ShoppingListInvitationFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @return BelongsTo<ShoppingList, $this>
     */
    public function shoppingList(): BelongsTo
    {
        return $this->belongsTo(ShoppingList::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }

    public function isPending(): bool
    {
        return $this->status === ShoppingListInvitationStatus::Pending;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ShoppingListInvitationStatus::class,
            'responded_at' => 'datetime',
        ];
    }
}
