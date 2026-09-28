<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use LyraDs\Blade\BladeServiceProvider;
use LyraDs\Blade\ToastStackScope;
use Orchestra\Testbench\Foundation\Application;

$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';

$_ENV['APP_KEY'] = $_SERVER['APP_KEY'] = 'base64:'.base64_encode(random_bytes(32));
putenv('APP_KEY='.$_ENV['APP_KEY']);

Application::create(
    basePath: $root.'/vendor/orchestra/testbench-core/laravel',
    options: ['extra' => ['providers' => [BladeServiceProvider::class]]],
);
app()->detectEnvironment(fn () => 'local');

// Simulates an error page: a stack whose slot throws is caught (as a real app
// would around an optional block), then a standalone toast renders on the
// same page/request. Proves ToastStackScope::active() is false afterwards —
// the scope must not leak past the exception.
try {
    Blade::render('<x-lyra::toast-stack>@php(throw new RuntimeException("boom"))</x-lyra::toast-stack>');
} catch (Throwable) {
    // swallowed, as an app-level error boundary would
}

echo '<div data-scope-active="'.(ToastStackScope::active() ? 'true' : 'false').'"></div>';
echo Blade::render('<x-lyra::toast>Saved after a failed stack</x-lyra::toast>');
