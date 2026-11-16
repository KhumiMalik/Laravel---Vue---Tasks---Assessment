<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Events\TaskCompleted;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'description', 'is_completed'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'is_completed' => false,
    ];

    protected static function booted(): void
    {
        // Lives on the model rather than in an action so every path that completes a
        // task (update endpoint, toggle, tinker, future bulk ops) fires it exactly once.
        // Creating a task as already-done isn't a transition, so it's not counted.
        static::updated(function (Task $task) {
            if ($task->wasChanged('is_completed') && $task->is_completed) {
                TaskCompleted::dispatch($task);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<TaskActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class);
    }

    #[Scope]
    protected function completed(Builder $query): void
    {
        $query->where('is_completed', true);
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('is_completed', false);
    }

    #[Scope]
    protected function withStatus(Builder $query, TaskStatus $status): void
    {
        match ($status) {
            TaskStatus::Completed => $query->completed(),
            TaskStatus::Pending => $query->pending(),
            TaskStatus::All => null,
        };
    }

    #[Scope]
    protected function titleContains(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        // Escape LIKE wildcards so searching "100%" means the literal string.
        // '!' instead of backslash because MySQL and SQLite disagree on backslash escaping.
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);

        $query->whereRaw("title LIKE ? ESCAPE '!'", ["%{$escaped}%"]);
    }
}
