<?php

namespace App\Models;

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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'checked' => 'boolean',
            'checked_at' => 'datetime',
            'unit_price' => 'decimal:2',
        ];
    }
}
