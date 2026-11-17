<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TaskFilteringTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[DataProvider('statusFilters')]
    public function test_tasks_can_be_filtered_by_status(string $status, int $expected): void
    {
        Task::factory()->for($this->user)->pending()->count(2)->create();
        Task::factory()->for($this->user)->completed()->create();

        $this->actingAs($this->user)
            ->getJson("/api/tasks?status={$status}")
            ->assertOk()
            ->assertJsonPath('meta.total', $expected);
    }

    public static function statusFilters(): array
    {
        return [
            'all' => ['all', 3],
            'pending' => ['pending', 2],
            'completed' => ['completed', 1],
        ];
    }

    public function test_tasks_can_be_searched_by_title(): void
    {
        Task::factory()->for($this->user)->create(['title' => 'Learn Laravel queues']);
        Task::factory()->for($this->user)->create(['title' => 'laravel policies']);
        Task::factory()->for($this->user)->create(['title' => 'Buy groceries']);
        Task::factory()->create(['title' => 'Laravel for someone else']);

        $response = $this->actingAs($this->user)->getJson('/api/tasks?search=LARAVEL');

        $response->assertOk()->assertJsonPath('meta.total', 2);
        $this->assertEqualsCanonicalizing(
            ['Learn Laravel queues', 'laravel policies'],
            $response->json('data.*.title'),
        );
    }

    public function test_search_treats_like_wildcards_as_literal_characters(): void
    {
        Task::factory()->for($this->user)->create(['title' => 'Hit 100% coverage']);
        Task::factory()->for($this->user)->create(['title' => 'Hit 100 tests']);
        Task::factory()->for($this->user)->create(['title' => 'rename snake_case vars']);
        Task::factory()->for($this->user)->create(['title' => 'rename snakeXcase vars']);

        $this->actingAs($this->user)
            ->getJson('/api/tasks?search='.urlencode('100%'))
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Hit 100% coverage');

        $this->actingAs($this->user)
            ->getJson('/api/tasks?search=snake_case')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'rename snake_case vars');
    }

    public function test_search_and_status_filters_combine(): void
    {
        Task::factory()->for($this->user)->completed()->create(['title' => 'Deploy release']);
        Task::factory()->for($this->user)->pending()->create(['title' => 'Deploy hotfix']);
        Task::factory()->for($this->user)->pending()->create(['title' => 'Write docs']);

        $this->actingAs($this->user)
            ->getJson('/api/tasks?search=deploy&status=pending')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Deploy hotfix');
    }

    public function test_tasks_are_paginated(): void
    {
        Task::factory()->for($this->user)->count(12)->create();

        $this->actingAs($this->user)
            ->getJson('/api/tasks?per_page=5&page=3')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 3)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 12);
    }

    public function test_pagination_defaults_to_ten_per_page(): void
    {
        Task::factory()->for($this->user)->count(11)->create();

        $this->actingAs($this->user)
            ->getJson('/api/tasks')
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_pagination_links_keep_the_active_filters(): void
    {
        Task::factory()->for($this->user)->pending()->count(3)->create(['title' => 'Report']);

        $next = $this->actingAs($this->user)
            ->getJson('/api/tasks?status=pending&search=report&per_page=1')
            ->json('links.next');

        $this->assertStringContainsString('status=pending', $next);
        $this->assertStringContainsString('search=report', $next);
    }

    public function test_newest_tasks_are_listed_first(): void
    {
        Task::factory()->for($this->user)->create(['title' => 'Older', 'created_at' => now()->subDay()]);
        Task::factory()->for($this->user)->create(['title' => 'Newer', 'created_at' => now()]);

        $this->actingAs($this->user)
            ->getJson('/api/tasks')
            ->assertJsonPath('data.0.title', 'Newer')
            ->assertJsonPath('data.1.title', 'Older');
    }

    #[DataProvider('invalidQueries')]
    public function test_invalid_list_parameters_are_rejected(string $query, string $field): void
    {
        $this->actingAs($this->user)
            ->getJson("/api/tasks?{$query}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);
    }

    public static function invalidQueries(): array
    {
        return [
            'unknown status' => ['status=archived', 'status'],
            'per_page zero' => ['per_page=0', 'per_page'],
            'per_page over the cap' => ['per_page=51', 'per_page'],
            'page not a number' => ['page=abc', 'page'],
        ];
    }
}
