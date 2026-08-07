<?php

use Livewire\Component;
use Livewire\Livewire;

it('renders a Lyra component through Livewire', function (): void {
    $component = new class extends Component
    {
        public function render(): string
        {
            return <<<'BLADE'
                <div>
                    <x-lyra::button>Continue</x-lyra::button>
                </div>
                BLADE;
        }
    };

    Livewire::test($component)->assertSeeHtml('lyra-btn');
});
