<?php

namespace App\Actions\Task;

use App\Models\Task;

class RestoreTaskAction
{
    public function execute(Task $task): Task
    {
        $task->restore();

        return $task;
    }
}
