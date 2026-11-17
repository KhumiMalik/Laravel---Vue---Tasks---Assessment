<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    public function restore(User $user, Task $task): Response
    {
        return $this->ownsTrashed($user, $task, 'Only tasks in the trash can be restored.');
    }

    public function forceDelete(User $user, Task $task): Response
    {
        return $this->ownsTrashed($user, $task, 'Only tasks in the trash can be permanently deleted.');
    }

    private function owns(User $user, Task $task): bool
    {
        // compares keys only, no query
        return $task->user()->is($user);
    }

    private function ownsTrashed(User $user, Task $task, string $notTrashedMessage): Response
    {
        if (! $this->owns($user, $task)) {
            return Response::deny();
        }

        // the owner is allowed, the task is just in the wrong state - that's a conflict, not a 403
        return $task->trashed()
            ? Response::allow()
            : Response::denyWithStatus(409, $notTrashedMessage);
    }
}
