<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tests\Exceptions\DatabaseAccessException;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function alertUnwantedDBAccess(): void
    {
        DB::listen(function ($query) {
            throw new DatabaseAccessException($query->sql);
        });
    }

    protected function todo(): void
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
        $this->markTestIncomplete(sprintf('Todo: %s::%s', $caller['class'], $caller['function']));
    }
}