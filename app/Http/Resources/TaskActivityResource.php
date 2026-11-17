<?php

namespace App\Http\Resources;

use App\Models\TaskActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TaskActivity */
class TaskActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'created_at' => $this->created_at?->toIso8601String(),
            'task' => $this->whenLoaded('task', fn () => [
                'id' => $this->task->id,
                'title' => $this->task->title,
                'is_trashed' => $this->task->trashed(),
            ]),
        ];
    }
}
