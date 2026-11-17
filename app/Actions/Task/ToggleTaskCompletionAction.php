<?php

namespace App\Actions\Task;

use App\Models\Task;

class ToggleTaskCompletionAction
{
    public function execute(Task $task): Task
    {
        $task->is_completed = ! $task->is_completed;
        $task->save();

        return $task;
    }
}
