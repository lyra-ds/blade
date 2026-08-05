<?php

use Illuminate\Support\Facades\Blade;
use LyraDs\Blade\BladeServiceProvider;

it('boots the package service provider', function (): void {
    expect($this->app->getProvider(BladeServiceProvider::class))
        ->toBeInstanceOf(BladeServiceProvider::class);
});

it('renders a component through the lyra namespace', function (): void {
    $componentPath = dirname(__DIR__, 2).'/resources/views/components/button.blade.php';
    $fixturePath = dirname(__DIR__).'/Fixtures/views/components/button.blade.php';

    copy($fixturePath, $componentPath);

    try {
        expect(Blade::render('<x-lyra::button variant="primary">Save</x-lyra::button>'))
            ->toContain('<button data-variant="primary">Save</button>');
    } finally {
        unlink($componentPath);
    }
});
