<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShipmentStoreRequest extends FormRequest
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
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'receiver_id' => ['nullable', 'integer', 'exists:customers,id'],
            'service_type' => ['required', Rule::in(['SeaFreight', 'LandTrucking', 'CarCarrier'])],
            'mode' => ['required', Rule::in(['Sea', 'Land'])],
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

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'shipper',
            'receiver_id' => 'receiver',
            'service_type' => 'service type',
            'pol_code' => 'port of loading',
            'pod_code' => 'port of discharge',
            'eta' => 'estimated time of arrival',
        ];
    }
}
