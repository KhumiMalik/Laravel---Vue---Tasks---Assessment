<?php

namespace App\Actions\Task;

use App\Models\Task;

class UpdateTaskAction
{
    /**
     * @param  array{title?: string, description?: ?string, is_completed?: bool}  $data
     */
    public function execute(Task $task, array $data): Task
    {
        $task->update($data);

        return $task;
    }
}
