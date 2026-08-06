<?php

namespace Tests;

use BladeUI\Icons\BladeIconsServiceProvider;
use LyraDs\Blade\BladeServiceProvider;
use MallardDuck\LucideIcons\BladeLucideIconsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeLucideIconsServiceProvider::class,
            BladeServiceProvider::class,
        ];
    }
}
