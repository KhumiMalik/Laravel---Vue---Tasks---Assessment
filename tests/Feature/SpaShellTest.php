<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpaShellTest extends TestCase
{
    public function test_frontend_routes_are_served_the_spa_shell(): void
    {
        $this->withoutVite();

        foreach (['/', '/login', '/trash'] as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSee('<div id="app"', false);
        }
    }
}
