<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserIndexRequest extends FormRequest
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
            'page'      => ['nullable', 'integer', 'min:1'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
            'search'    => ['nullable', 'string', 'max:100'],
            'role'      => ['nullable', 'string', 'max:50'],
            'branch_id' => ['nullable', 'integer', 'min:1'],
            'status'    => ['nullable', 'in:active,inactive'], 
            'sort_by'   => ['nullable', 'in:id,name,email,created_at,branch_id'],
            'sort_dir'  => ['nullable', 'in:asc,desc'],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 15);
    }

    public function sortBy(): string
    {
        return $this->input('sort_by', 'created_at');
    }

    public function sortDir(): string
    {
        return $this->input('sort_dir', 'desc');
    }
}
