<?php

namespace App\Actions\Task;

use App\Models\Task;
use App\Models\User;

class CreateTaskAction
{
    /**
     * @param  array{title: string, description?: ?string, is_completed?: bool}  $data
     */
    public function execute(User $user, array $data): Task
    {
        // owner always comes from the session, never from the payload
        return $user->tasks()->create($data);
    }
}
