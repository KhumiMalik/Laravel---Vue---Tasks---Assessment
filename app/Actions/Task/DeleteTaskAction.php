<?php

namespace App\Actions\Task;

use App\Models\Task;

class DeleteTaskAction
{
    // soft delete - the task moves to the trash and can be restored
    public function execute(Task $task): void
    {
        $task->delete();
    }
}
