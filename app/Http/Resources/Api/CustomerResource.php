<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $pic_name
 * @property string|null $pic_phone
 * @property string|null $pic_email
 * @property int|null $branch_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class CustomerResource extends JsonResource
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
            'name' => $this->name,
            'contact' => [
                'email' => $this->email,
                'phone' => $this->phone,
            ],
            'address' => $this->address,
            'pic' => [
                'name' => $this->pic_name,
                'phone' => $this->pic_phone,
                'email' => $this->pic_email,
            ],
            'branch_id' => $this->branch_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
