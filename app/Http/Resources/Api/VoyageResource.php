<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $voyage_number
 * @property int $vessel_id
 * @property string|null $service_code
 * @property \Carbon\Carbon|null $etd
 * @property \Carbon\Carbon|null $eta
 * @property string|null $pol_code
 * @property string|null $pod_code
 * @property string|null $status
 * @property \App\Models\Vessel $vessel
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class VoyageResource extends JsonResource
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
            'voyage_number' => $this->voyage_number,
            'service_code' => $this->service_code,
            'schedule' => [
                'etd' => $this->etd?->toISOString(),
                'eta' => $this->eta?->toISOString(),
                'pol_code' => $this->pol_code,
                'pod_code' => $this->pod_code,
            ],
            'status' => $this->status,
            'vessel' => $this->whenLoaded('vessel', fn () => new VesselResource($this->vessel)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
