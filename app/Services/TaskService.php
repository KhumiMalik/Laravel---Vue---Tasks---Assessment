<?php

namespace App\Services;

use App\Actions\Task\ToggleTaskCompletionAction;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Owns the task workflows that are more than a single write: user-scoped listing,
 * statistics, and the locked completion toggle. Single-step operations (create,
 * update, delete, restore, force delete) are called as actions straight from the
 * controller - wrapping them here would just be pass-through methods.
 */
class TaskService
{
    public function __construct(
        private readonly ToggleTaskCompletionAction $toggleCompletion,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Task>
     */
    public function paginateForUser(User $user, TaskStatus $status, ?string $search, int $perPage): LengthAwarePaginator
    {
        return $user->tasks()
            ->withStatus($status)
            ->titleContains($search)
            ->latest()
            ->latest('id') // tie-breaker so pages stay stable when timestamps match
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, Task>
     */
    public function paginateTrashForUser(User $user, ?string $search, int $perPage): LengthAwarePaginator
    {
        return $user->tasks()
            ->onlyTrashed()
            ->titleContains($search)
            ->latest('deleted_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{total: int, completed: int, pending: int, trashed: int}
     */
    public function statisticsForUser(User $user): array
    {
        // one round trip instead of four count() queries
        $row = Task::withTrashed()
            ->whereBelongsTo($user)
            ->toBase()
            ->selectRaw('SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) AS total')
            ->selectRaw('SUM(CASE WHEN deleted_at IS NULL AND is_completed = 1 THEN 1 ELSE 0 END) AS completed')
            ->selectRaw('SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) AS trashed')
            ->first();

        // SUM over zero rows is NULL
        $total = (int) $row->total;
        $completed = (int) $row->completed;

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $total - $completed,
            'trashed' => (int) $row->trashed,
        ];
    }

    public function toggleCompletion(Task $task): Task
    {
        // Toggle is read-then-write. Without the row lock two near-simultaneous requests
        // (double click, two tabs) can both read "pending", both write "completed" and
        // fire TaskCompleted twice. With it the second one sees the first one's result.
        return DB::transaction(function () use ($task) {
            $locked = Task::query()->lockForUpdate()->findOrFail($task->getKey());

            return $this->toggleCompletion->execute($locked);
        });
    }

    /**
     * @return Collection<int, TaskActivity>
     */
    public function recentActivityForUser(User $user, int $limit = 8): Collection
    {
        return $user->taskActivities()
            ->with('task')
            ->latest()
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
