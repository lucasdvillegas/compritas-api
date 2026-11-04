<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShoppingListResource extends JsonResource
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
            'name' => $this->name,
            'status' => $this->status->value,
            'role' => $this->pivot?->role,
            'items_count' => $this->items_count,
            'checked_items_count' => $this->checked_items_count,
            'members_count' => $this->members_count ?? null,
            'completed_at' => $this->completed_at,
            'total_amount' => $this->resolvedTotal(),
            'manual_total' => $this->manual_total === null ? null : (float) $this->manual_total,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'items' => ShoppingListItemResource::collection($this->whenLoaded('items')),
        ];
    }

    private function resolvedTotal(): ?float
    {
        if ($this->manual_total !== null) {
            return (float) $this->manual_total;
        }

        if ($this->relationLoaded('items')) {
            return $this->calculatedTotal();
        }

        return $this->total_amount === null ? null : (float) $this->total_amount;
    }
}
