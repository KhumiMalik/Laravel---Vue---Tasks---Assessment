<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTasksRequest extends FormRequest
{
    public const DEFAULT_PER_PAGE = 10;

    public const MAX_PER_PAGE = 50;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function status(): TaskStatus
    {
        return $this->enum('status', TaskStatus::class) ?? TaskStatus::All;
    }

    public function searchTerm(): ?string
    {
        $term = trim((string) $this->validated('search'));

        return $term === '' ? null : $term;
    }

    public function perPage(): int
    {
        return $this->filled('per_page') ? $this->integer('per_page') : self::DEFAULT_PER_PAGE;
    }
}
