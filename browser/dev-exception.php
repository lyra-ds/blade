<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';

$_ENV['APP_KEY'] = $_SERVER['APP_KEY'] = 'base64:'.base64_encode(random_bytes(32));
putenv('APP_KEY='.$_ENV['APP_KEY']);

Orchestra\Testbench\Foundation\Application::create(
    basePath: $root.'/vendor/orchestra/testbench-core/laravel',
    options: ['extra' => ['providers' => [LyraDs\Blade\BladeServiceProvider::class]]],
);
app()->detectEnvironment(fn () => 'local');

try {
    Blade::render('<lyra:tabs x-data="{ n: 0 }" active="a" :items="[[\'id\' => \'a\', \'label\' => \'A\']]" />');
    echo '<div role="alert">Missing expected exception</div>';
} catch (Throwable $exception) {
    echo '<div role="alert">'.htmlspecialchars($exception->getMessage(), ENT_QUOTES).'</div>';
}
