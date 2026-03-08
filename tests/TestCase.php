<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Event;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Disable CSRF for all tests — forms are tested via browser/E2E if needed
        $this->withoutMiddleware(ValidateCsrfToken::class);
        // Prevent any test from accidentally hitting Reverb
        Event::fake();
        $this->withoutExceptionHandling();
    }
}
