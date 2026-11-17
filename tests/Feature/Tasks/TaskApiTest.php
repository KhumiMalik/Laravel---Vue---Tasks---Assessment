<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_user_can_list_only_their_own_tasks(): void
    {
        $mine = Task::factory()->for($this->user)->count(3)->create();
        Task::factory()->count(2)->create();

        $response = $this->actingAs($this->user)->getJson('/api/tasks');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure([
                'data' => [['id', 'title', 'description', 'is_completed', 'created_at', 'updated_at', 'deleted_at']],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertEqualsCanonicalizing($mine->pluck('id')->all(), $response->json('data.*.id'));
    }

    public function test_user_can_create_a_task(): void
    {
        $someoneElse = User::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/tasks', [
            'title' => 'Write the API tests',
            'description' => 'Cover the happy path and the edges.',
            'user_id' => $someoneElse->id, // must be ignored
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Write the API tests')
            ->assertJsonPath('data.description', 'Cover the happy path and the edges.')
            ->assertJsonPath('data.is_completed', false);

        $this->assertDatabaseHas('tasks', [
            'id' => $response->json('data.id'),
            'user_id' => $this->user->id,
            'title' => 'Write the API tests',
        ]);
    }

    public function test_user_can_view_a_task(): void
    {
        $task = Task::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->getJson("/api/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.title', $task->title);
    }

    public function test_user_can_update_a_task(): void
    {
        $task = Task::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->putJson("/api/tasks/{$task->id}", [
                'title' => 'Updated title',
                'description' => null,
                'is_completed' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.description', null)
            ->assertJsonPath('data.is_completed', true);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated title',
            'description' => null,
            'is_completed' => true,
        ]);
    }

    public function test_patch_only_changes_the_fields_that_were_sent(): void
    {
        $task = Task::factory()->for($this->user)->create([
            'title' => 'Original',
            'description' => 'Keep me',
        ]);

        $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}", ['title' => 'Renamed'])
            ->assertOk();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Renamed',
            'description' => 'Keep me',
        ]);
    }

    public function test_user_can_delete_a_task(): void
    {
        $task = Task::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->deleteJson("/api/tasks/{$task->id}")
            ->assertNoContent();

        $this->assertSoftDeleted($task);

        $this->actingAs($this->user)
            ->getJson("/api/tasks/{$task->id}")
            ->assertNotFound();
    }

    #[DataProvider('invalidTaskPayloads')]
    public function test_invalid_task_data_is_rejected(array $payload, string $field): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/tasks', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);

        $this->assertDatabaseCount('tasks', 0);
    }

    public static function invalidTaskPayloads(): array
    {
        return [
            'missing title' => [['description' => 'No title'], 'title'],
            'blank title' => [['title' => '   '], 'title'],
            'title too long' => [['title' => str_repeat('a', 256)], 'title'],
            'description is not a string' => [['title' => 'Ok', 'description' => ['nested']], 'description'],
            'description too long' => [['title' => 'Ok', 'description' => str_repeat('a', 5001)], 'description'],
            'is_completed is not a boolean' => [['title' => 'Ok', 'is_completed' => 'definitely'], 'is_completed'],
        ];
    }

    public function test_update_rejects_a_blank_title(): void
    {
        $task = Task::factory()->for($this->user)->create(['title' => 'Original']);

        $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}", ['title' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Original']);
    }

    #[DataProvider('foreignTaskRequests')]
    public function test_user_cannot_access_another_users_task(string $method, string $suffix, array $payload = []): void
    {
        $task = Task::factory()->create(['title' => 'Not yours']);

        $this->actingAs($this->user)
            ->json($method, "/api/tasks/{$task->id}{$suffix}", $payload)
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Not yours',
            'is_completed' => false,
            'deleted_at' => null,
        ]);
    }

    public static function foreignTaskRequests(): array
    {
        return [
            'view' => ['GET', ''],
            'replace' => ['PUT', '', ['title' => 'Hijacked']],
            // authorization has to win over validation, or a 422 would confirm the id exists
            'patch with invalid data' => ['PATCH', '', ['title' => '']],
            'delete' => ['DELETE', ''],
            'toggle completion' => ['PATCH', '/toggle-completion'],
        ];
    }

    public function test_missing_task_returns_a_generic_not_found_message(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/tasks/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Resource not found.']);
    }
}
