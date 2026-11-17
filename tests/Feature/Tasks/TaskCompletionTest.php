<?php

namespace Tests\Feature\Tasks;

use App\Events\TaskCompleted;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TaskCompletionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_user_can_complete_a_task(): void
    {
        Event::fake([TaskCompleted::class]);

        $task = Task::factory()->for($this->user)->pending()->create();

        $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}/toggle-completion")
            ->assertOk()
            ->assertJsonPath('data.is_completed', true);

        $this->assertTrue($task->fresh()->is_completed);
        Event::assertDispatchedTimes(TaskCompleted::class, 1);
        Event::assertDispatched(TaskCompleted::class, fn (TaskCompleted $event) => $event->task->is($task));
    }

    public function test_user_can_mark_a_completed_task_as_pending(): void
    {
        Event::fake([TaskCompleted::class]);

        $task = Task::factory()->for($this->user)->completed()->create();

        $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}/toggle-completion")
            ->assertOk()
            ->assertJsonPath('data.is_completed', false);

        $this->assertFalse($task->fresh()->is_completed);
        Event::assertNotDispatched(TaskCompleted::class);
    }

    public function test_completing_through_the_update_endpoint_also_fires_the_event(): void
    {
        Event::fake([TaskCompleted::class]);

        $task = Task::factory()->for($this->user)->pending()->create();

        $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}", ['is_completed' => true])
            ->assertOk();

        Event::assertDispatchedTimes(TaskCompleted::class, 1);
    }

    public function test_saving_an_already_completed_task_does_not_fire_the_event_again(): void
    {
        Event::fake([TaskCompleted::class]);

        $task = Task::factory()->for($this->user)->completed()->create();

        $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}", ['title' => 'Renamed', 'is_completed' => true])
            ->assertOk();

        Event::assertNotDispatched(TaskCompleted::class);
    }

    public function test_creating_a_task_as_completed_is_not_a_completion(): void
    {
        Event::fake([TaskCompleted::class]);

        $this->actingAs($this->user)
            ->postJson('/api/tasks', ['title' => 'Already done', 'is_completed' => true])
            ->assertCreated()
            ->assertJsonPath('data.is_completed', true);

        Event::assertNotDispatched(TaskCompleted::class);
    }

    public function test_completion_is_recorded_in_the_activity_feed(): void
    {
        // queue is sync in tests, so the listener runs inline
        $task = Task::factory()->for($this->user)->pending()->create();

        $this->actingAs($this->user)->patchJson("/api/tasks/{$task->id}/toggle-completion");

        $this->assertDatabaseHas('task_activities', [
            'user_id' => $this->user->id,
            'task_id' => $task->id,
            'action' => 'completed',
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/activities')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'completed')
            ->assertJsonPath('data.0.task.id', $task->id)
            ->assertJsonPath('data.0.task.title', $task->title)
            ->assertJsonPath('data.0.task.is_trashed', false);
    }

    public function test_activity_feed_only_shows_the_users_own_activity(): void
    {
        $other = User::factory()->create();
        $theirs = Task::factory()->for($other)->pending()->create();

        $this->actingAs($other)->patchJson("/api/tasks/{$theirs->id}/toggle-completion")->assertOk();

        $this->actingAs($this->user)
            ->getJson('/api/activities')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
