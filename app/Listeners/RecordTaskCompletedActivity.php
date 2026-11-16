<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Models\TaskActivity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;

// The task can be permanently deleted before a worker picks this up; drop the job quietly then.
#[DeleteWhenMissingModels]
class RecordTaskCompletedActivity implements ShouldQueue
{
    public function handle(TaskCompleted $event): void
    {
        $event->task->activities()->create([
            'user_id' => $event->task->user_id,
            'action' => TaskActivity::ACTION_COMPLETED,
        ]);
    }
}
