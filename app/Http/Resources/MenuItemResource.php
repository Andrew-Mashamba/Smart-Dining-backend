<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
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
            'description' => $this->description,
            'price' => (float) $this->price,
            'category' => $this->whenLoaded('menuCategory', function () {
                return [
                    'id' => $this->menuCategory->id,
                    'name' => $this->menuCategory->name,
                ];
            }),
            'prep_area' => $this->prep_area,
            'prep_time_minutes' => $this->prep_time_minutes,
            'image_url' => $this->image_url ?? null,
            'available' => $this->status === 'available',
            'is_popular' => (bool) ($this->is_popular ?? false),
            'dietary_info' => $this->dietary_info ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
