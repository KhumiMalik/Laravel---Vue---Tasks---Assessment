<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTrashTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_trash_lists_only_the_users_deleted_tasks(): void
    {
        $trashed = Task::factory()->for($this->user)->count(2)->trashed()->create();
        Task::factory()->for($this->user)->create();
        Task::factory()->trashed()->create();

        $response = $this->actingAs($this->user)->getJson('/api/tasks/trash');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $this->assertEqualsCanonicalizing($trashed->pluck('id')->all(), $response->json('data.*.id'));
        $this->assertNotNull($response->json('data.0.deleted_at'));
    }

    public function test_deleted_tasks_do_not_appear_in_the_main_list(): void
    {
        Task::factory()->for($this->user)->trashed()->create();
        $live = Task::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->getJson('/api/tasks')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $live->id);
    }

    public function test_user_can_restore_a_deleted_task(): void
    {
        $task = Task::factory()->for($this->user)->trashed()->create();

        $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.deleted_at', null);

        $this->assertNotSoftDeleted($task);
    }

    public function test_restoring_a_task_that_is_not_in_the_trash_is_a_conflict(): void
    {
        $task = Task::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->id}/restore")
            ->assertConflict()
            ->assertJsonPath('message', 'Only tasks in the trash can be restored.');
    }

    public function test_user_can_permanently_delete_a_trashed_task(): void
    {
        $task = Task::factory()->for($this->user)->completed()->trashed()->create();
        TaskActivity::create([
            'user_id' => $this->user->id,
            'task_id' => $task->id,
            'action' => TaskActivity::ACTION_COMPLETED,
        ]);

        $this->actingAs($this->user)
            ->deleteJson("/api/tasks/{$task->id}/force-delete")
            ->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('task_activities', ['task_id' => $task->id]);
    }

    public function test_permanently_deleting_a_live_task_is_a_conflict(): void
    {
        $task = Task::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->deleteJson("/api/tasks/{$task->id}/force-delete")
            ->assertConflict();

        $this->assertNotSoftDeleted($task);
    }

    public function test_user_cannot_restore_or_purge_another_users_task(): void
    {
        $task = Task::factory()->trashed()->create();

        $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->id}/restore")
            ->assertForbidden();

        $this->actingAs($this->user)
            ->deleteJson("/api/tasks/{$task->id}/force-delete")
            ->assertForbidden();

        $this->assertSoftDeleted($task);
    }
}
