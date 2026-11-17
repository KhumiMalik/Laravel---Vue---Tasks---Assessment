<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'Jane@Example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Jane Doe')
            ->assertJsonPath('data.email', 'jane@example.com')
            ->assertJsonMissingPath('data.password');

        $user = User::firstWhere('email', 'jane@example.com');

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_rejects_invalid_input(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/register', [
            'name' => '',
            'email' => 'TAKEN@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);

        $this->assertGuest();
    }

    public function test_user_can_log_in(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);

        $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_the_wrong_password(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'not-the-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/login', ['email' => 'jane@example.com', 'password' => 'nope'])
                ->assertUnprocessable();
        }

        // even the right password is refused once locked out
        $this->postJson('/api/login', ['email' => 'jane@example.com', 'password' => 'password'])
            ->assertTooManyRequests()
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/logout')
            ->assertNoContent();

        $this->assertGuest('web');
    }

    public function test_authenticated_user_can_fetch_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/user')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at->toIso8601String(),
                ],
            ]);
    }

    #[DataProvider('protectedEndpoints')]
    public function test_guests_cannot_access_protected_endpoints(string $method, string $uri): void
    {
        $this->json($method, $uri)
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public static function protectedEndpoints(): array
    {
        return [
            'profile' => ['GET', '/api/user'],
            'logout' => ['POST', '/api/logout'],
            'list tasks' => ['GET', '/api/tasks'],
            'create task' => ['POST', '/api/tasks'],
            'show task' => ['GET', '/api/tasks/1'],
            'update task' => ['PUT', '/api/tasks/1'],
            'delete task' => ['DELETE', '/api/tasks/1'],
            'toggle completion' => ['PATCH', '/api/tasks/1/toggle-completion'],
            'statistics' => ['GET', '/api/tasks/statistics'],
            'trash' => ['GET', '/api/tasks/trash'],
            'restore' => ['POST', '/api/tasks/1/restore'],
            'force delete' => ['DELETE', '/api/tasks/1/force-delete'],
            'activity' => ['GET', '/api/activities'],
        ];
    }
}
