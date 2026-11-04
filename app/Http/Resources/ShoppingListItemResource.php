<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShoppingListItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'category_name' => $this->category_name,
            'unit' => $this->unit->value,
            'quantity' => (float) $this->quantity,
            'checked' => $this->checked,
            'checked_at' => $this->checked_at,
            'unit_price' => $this->unit_price === null ? null : (float) $this->unit_price,
            'line_total' => $this->lineTotal(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
