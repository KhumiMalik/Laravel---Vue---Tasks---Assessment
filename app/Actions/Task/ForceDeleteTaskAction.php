<?php

namespace App\Actions\Task;

use App\Models\Task;

class ForceDeleteTaskAction
{
    // the task's activity rows go with it via the FK cascade
    public function execute(Task $task): void
    {
        $task->forceDelete();
    }
}
