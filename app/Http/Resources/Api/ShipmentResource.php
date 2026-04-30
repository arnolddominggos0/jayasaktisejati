<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $code
 * @property int|null $customer_id
 * @property int|null $receiver_id
 * @property string|null $service_type
 * @property string|null $mode
 * @property string|null $status
 * @property int|null $voyage_id
 * @property int|null $branch_id
 * @property \Carbon\Carbon|null $pickup_date
 * @property \Carbon\Carbon|null $delivery_date
 * @property \Carbon\Carbon|null $eta
 * @property string|null $pol_code
 * @property string|null $pod_code
 * @property string|null $destination_city
 * @property string|null $origin_city
 * @property int|null $total_colli
 * @property float|null $total_weight
 * @property float|null $total_volume
 * @property string|null $notes
 * @property \App\Models\Customer $customer
 * @property \App\Models\Customer $receiver
 * @property \App\Models\Voyage $voyage
 * @property \App\Models\Branch $branch
 * @property \Illuminate\Database\Eloquent\Collection $tracks
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ShipmentResource extends JsonResource
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
            'code' => $this->code,
            'service' => [
                'type' => $this->service_type,
                'mode' => $this->mode,
            ],
            'status' => $this->status,
            'parties' => [
                'shipper' => $this->whenLoaded('customer', fn () => [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'code' => $this->customer->code,
                ]),
                'receiver' => $this->whenLoaded('receiver', fn () => [
                    'id' => $this->receiver->id,
                    'name' => $this->receiver->name,
                    'code' => $this->receiver->code,
                ]),
            ],
            'route' => [
                'origin' => [
                    'city' => $this->origin_city,
                    'port_code' => $this->pol_code,
                ],
                'destination' => [
                    'city' => $this->destination_city,
                    'port_code' => $this->pod_code,
                ],
            ],
            'schedule' => [
                'pickup_date' => $this->pickup_date?->toISOString(),
                'delivery_date' => $this->delivery_date?->toISOString(),
                'eta' => $this->eta?->toISOString(),
            ],
            'voyage' => $this->whenLoaded('voyage', fn () => new VoyageResource($this->voyage)),
            'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)),
            'cargo' => [
                'total_colli' => $this->total_colli,
                'total_weight' => $this->total_weight,
                'total_volume' => $this->total_volume,
            ],
            'notes' => $this->notes,
            'tracking' => ShipmentTrackResource::collection($this->whenLoaded('tracks')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
