<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'roles'      => $this->getRoleNames()->values(),
            'branch'     => $this->whenLoaded('branch', function () {
                return [
                    'id'   => $this->branch->id,
                    'code' => $this->branch->code,
                    'name' => $this->branch->name,
                ];
            }),
            'branch_id'  => $this->branch_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
