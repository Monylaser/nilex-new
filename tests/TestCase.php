<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Test fixtures build trusted models directly via Model::create([...])
        // (e.g. Listing::create(['user_id' => ..., 'status' => ...])). Production
        // now guards those columns against mass-assignment, so we unguard inside
        // the test environment only — mirroring what Eloquent factories already do
        // internally. Request-driven flows still run through the real controllers,
        // so the mass-assignment protection remains fully exercised end-to-end.
        Model::unguard();
    }
}
