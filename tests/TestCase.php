<?php

declare(strict_types=1);

namespace Tests;

use App\Services\SidingContext;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Static contexts survive between tests (the DB does not); a stale
        // organization/siding id from a previous test causes FK violations.
        TenantContext::flush();
        SidingContext::flush();
    }
}
