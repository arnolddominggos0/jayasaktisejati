<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property int $shipment_id
 * @property string $status
 * @property string|null $location
 * @property string|null $notes
 * @property \Carbon\Carbon|null $occurred_at
 * @property \Carbon\Carbon $created_at
 */
class ShipmentTrackResource extends JsonResource
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
            'shipment_id' => $this->shipment_id,
            'status' => $this->status,
            'location' => $this->location,
            'notes' => $this->notes,
            'occurred_at' => $this->occurred_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
