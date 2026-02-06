<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'guest' => [
                'id' => $this->guest->id,
                'name' => $this->guest->name,
                'phone_number' => $this->guest->phone_number,
                'loyalty_points' => $this->guest->loyalty_points,
            ],
            'table' => [
                'id' => $this->table->id,
                'name' => $this->table->name,
                'location' => $this->table->location,
            ],
            'waiter' => [
                'id' => $this->waiter->id,
                'name' => $this->waiter->name,
            ],
            'status' => $this->status,
            'order_source' => $this->order_source,
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'menu_item' => [
                            'id' => $item->menuItem->id,
                            'name' => $item->menuItem->name,
                            'price' => $item->unit_price,
                        ],
                        'quantity' => $item->quantity,
                        'subtotal' => $item->subtotal,
                        'status' => $item->status,
                        'special_instructions' => $item->special_instructions,
                    ];
                });
            }),
            'totals' => [
                'subtotal' => (float) $this->subtotal,
                'tax' => (float) $this->tax,
                'service_charge' => (float) $this->service_charge,
                'total_amount' => (float) $this->total_amount,
            ],
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
