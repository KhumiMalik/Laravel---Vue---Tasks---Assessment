<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // login: demo@example.com / password
        $demo = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        $tasks = [
            ['Draft Q4 product roadmap', 'Collect input from sales and support before the planning session.', false],
            ['Review pull request #142', 'Focus on the new caching layer and its invalidation rules.', true],
            ['Update onboarding documentation', null, false],
            ['Fix flaky checkout test', 'Fails roughly 1 in 20 runs on CI, likely a timing issue.', true],
            ['Prepare sprint demo', 'Five minute walkthrough of the task filters and statistics.', false],
            ['Renew SSL certificate', 'Current certificate expires at the end of the month.', true],
            ['Plan team offsite agenda', null, false],
            ['Migrate cron jobs to the scheduler', 'Move the three legacy crontab entries into routes/console.php.', false],
            ['Write release notes for v2.3', null, true],
            ['Audit npm dependencies', 'Run npm audit and triage anything above moderate.', false],
            ['Set up staging database backups', 'Nightly dump, keep seven days.', true],
            ['Book dentist appointment', null, false],
            ['Reply to client feedback email', 'They asked about bulk export, check feasibility first.', false],
            ['Refactor invoice PDF generation', null, true],
            ['Organise shared drive folders', null, false],
        ];

        foreach ($tasks as $i => [$title, $description, $completed]) {
            $createdAt = now()->subHours(($i + 1) * 9);

            $task = Task::factory()->for($demo)->create([
                'title' => $title,
                'description' => $description,
                'is_completed' => $completed,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // events are muted while seeding, so write the history the listener would have
            if ($completed) {
                TaskActivity::create([
                    'user_id' => $demo->id,
                    'task_id' => $task->id,
                    'action' => TaskActivity::ACTION_COMPLETED,
                    'created_at' => $createdAt->copy()->addHours(3),
                ]);
            }
        }

        Task::factory()->for($demo)->count(3)->trashed()->create();

        // someone else's data, to show it never leaks into the demo account
        Task::factory()->for(User::factory()->create(['email' => 'other@example.com']))->count(5)->create();
    }
}
