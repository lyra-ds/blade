<?php

namespace Tests;

use LyraDs\Blade\BladeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            BladeServiceProvider::class,
        ];
    }
}
