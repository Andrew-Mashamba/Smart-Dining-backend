<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'guest' => [
                'id' => $this->guest->id,
                'name' => $this->guest->name,
                'phone_number' => $this->guest->phone_number,
            ],
            'table' => $this->when($this->table, [
                'id' => $this->table?->id,
                'name' => $this->table?->name,
                'location' => $this->table?->location,
            ]),
            'reservation_date' => $this->reservation_date->format('Y-m-d'),
            'reservation_time' => $this->reservation_time,
            'party_size' => $this->party_size,
            'location' => $this->location,
            'status' => $this->status,
            'special_requests' => $this->special_requests,
            'source' => $this->source,
            'created_at' => $this->created_at,
        ];
    }
}
