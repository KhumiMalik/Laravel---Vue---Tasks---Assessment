<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Sanctum only attaches a session to requests that come from a stateful domain,
        // so make every test request look like it came from the SPA.
        $this->withHeader('Referer', config('app.url'));
    }
}
