<?php

namespace App\Models;

use App\Enums\ShoppingListStatus;
use Database\Factories\ShoppingListFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workspace_id', 'name', 'status', 'completed_at', 'total_amount'])]
class ShoppingList extends Model
{
    /** @use HasFactory<ShoppingListFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'open',
    ];

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return HasMany<ShoppingListItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShoppingListItem::class)->orderBy('id');
    }

    /**
     * @return HasMany<ShoppingListItem, $this>
     */
    public function checkedItems(): HasMany
    {
        return $this->hasMany(ShoppingListItem::class)->where('checked', true);
    }

    public function isCompleted(): bool
    {
        return $this->status === ShoppingListStatus::Completed;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ShoppingListStatus::class,
            'completed_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }
}
