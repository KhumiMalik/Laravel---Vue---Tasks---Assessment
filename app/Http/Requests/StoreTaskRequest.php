<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    // TEXT holds 65,535 bytes; 5000 chars of 4-byte utf8mb4 still fits with room to spare
    public const DESCRIPTION_MAX_LENGTH = 5000;

    // ownership is enforced by the policy on the route, not here
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:'.self::DESCRIPTION_MAX_LENGTH],
            'is_completed' => ['sometimes', 'boolean'],
        ];
    }
}
