<?php

/*
|--------------------------------------------------------------------------
| Pest bootstrap
|--------------------------------------------------------------------------
|
| Wire all Feature tests to Laravel's test-case so that helpers like
| $this->get(), actingAs(), assertAuthenticated() etc. are available.
| Unit tests keep the default PHPUnit\Framework\TestCase.
|
*/

uses(Tests\TestCase::class)->in('Feature');

require_once __DIR__ . '/Feature/Plans/helpers.php';
