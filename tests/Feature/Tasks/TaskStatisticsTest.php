<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_statistics_only_include_the_authenticated_users_tasks(): void
    {
        $user = User::factory()->create();

        Task::factory()->for($user)->completed()->count(2)->create();
        Task::factory()->for($user)->pending()->count(3)->create();
        Task::factory()->for($user)->completed()->trashed()->create();
        Task::factory()->count(4)->create();

        $this->actingAs($user)
            ->getJson('/api/tasks/statistics')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'total' => 5,
                    'completed' => 2,
                    'pending' => 3,
                    'trashed' => 1,
                ],
            ]);
    }

    public function test_statistics_are_zero_for_a_user_without_tasks(): void
    {
        Task::factory()->count(2)->create();

        $this->actingAs(User::factory()->create())
            ->getJson('/api/tasks/statistics')
            ->assertExactJson([
                'data' => ['total' => 0, 'completed' => 0, 'pending' => 0, 'trashed' => 0],
            ]);
    }
}
