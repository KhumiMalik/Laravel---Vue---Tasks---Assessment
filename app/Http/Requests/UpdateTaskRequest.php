<?php

namespace App\Http\Requests;

class UpdateTaskRequest extends StoreTaskRequest
{
    public function rules(): array
    {
        // Same constraints as create, but any field may be omitted so PATCH can send one.
        // Sent fields are still fully validated, e.g. an empty title is rejected.
        return array_map(
            fn (array $rules) => in_array('sometimes', $rules, true) ? $rules : ['sometimes', ...$rules],
            parent::rules(),
        );
    }
}
