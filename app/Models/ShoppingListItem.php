<?php

namespace App\Models;

use App\Enums\ProductUnit;
use Database\Factories\ShoppingListItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shopping_list_id',
    'product_id',
    'product_name',
    'category_name',
    'unit',
    'quantity',
    'checked',
    'checked_at',
    'unit_price',
])]
class ShoppingListItem extends Model
{
    /** @use HasFactory<ShoppingListItemFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'unit' => 'unit',
        'quantity' => 1,
        'checked' => false,
    ];

    /**
     * @return BelongsTo<ShoppingList, $this>
     */
    public function shoppingList(): BelongsTo
    {
        return $this->belongsTo(ShoppingList::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lineTotal(): ?float
    {
        if ($this->unit_price === null) {
            return null;
        }

        return round((float) $this->unit_price * (float) $this->quantity, 2);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit' => ProductUnit::class,
            'quantity' => 'float',
            'checked' => 'boolean',
            'checked_at' => 'datetime',
            'unit_price' => 'decimal:2',
        ];
    }
}
