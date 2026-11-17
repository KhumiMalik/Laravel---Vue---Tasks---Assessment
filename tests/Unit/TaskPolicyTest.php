<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\User;
use App\Policies\TaskPolicy;
use Tests\TestCase;

class TaskPolicyTest extends TestCase
{
    private TaskPolicy $policy;

    private User $owner;

    private User $stranger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TaskPolicy;
        $this->owner = (new User)->forceFill(['id' => 1]);
        $this->stranger = (new User)->forceFill(['id' => 2]);
    }

    public function test_only_the_owner_can_view_update_or_delete(): void
    {
        $task = $this->taskOwnedBy($this->owner);

        foreach (['view', 'update', 'delete'] as $ability) {
            $this->assertTrue($this->policy->{$ability}($this->owner, $task), "owner should be able to {$ability}");
            $this->assertFalse($this->policy->{$ability}($this->stranger, $task), "stranger should not {$ability}");
        }
    }

    public function test_owner_can_restore_and_purge_only_trashed_tasks(): void
    {
        $trashed = $this->taskOwnedBy($this->owner, trashed: true);
        $live = $this->taskOwnedBy($this->owner);

        foreach (['restore', 'forceDelete'] as $ability) {
            $this->assertTrue($this->policy->{$ability}($this->owner, $trashed)->allowed());

            $response = $this->policy->{$ability}($this->owner, $live);
            $this->assertTrue($response->denied());
            $this->assertSame(409, $response->status());
        }
    }

    public function test_stranger_is_forbidden_from_restoring_or_purging(): void
    {
        $trashed = $this->taskOwnedBy($this->owner, trashed: true);

        foreach (['restore', 'forceDelete'] as $ability) {
            $response = $this->policy->{$ability}($this->stranger, $trashed);
            $this->assertTrue($response->denied());
            $this->assertNull($response->status(), 'should fall through to the default 403');
        }
    }

    private function taskOwnedBy(User $user, bool $trashed = false): Task
    {
        return (new Task)->forceFill([
            'user_id' => $user->id,
            'deleted_at' => $trashed ? now() : null,
        ]);
    }
}
