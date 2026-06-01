<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShipmentUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'receiver_id' => ['nullable', 'integer', 'exists:customers,id'],
            'voyage_id' => ['nullable', 'integer', 'exists:voyages,id'],
            'pickup_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:pickup_date'],
            'eta' => ['nullable', 'date'],
            'pol_code' => ['nullable', 'string', 'max:10'],
            'pod_code' => ['nullable', 'string', 'max:10'],
            'origin_city' => ['nullable', 'string', 'max:100'],
            'destination_city' => ['nullable', 'string', 'max:100'],
            'total_colli' => ['nullable', 'integer', 'min:0'],
            'total_weight' => ['nullable', 'numeric', 'min:0'],
            'total_volume' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
