<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold text-emerald-600">Point of Sale</h2>
            
            <x-filament::button
                size="lg"
                color="success"
                wire:click="createOrder"
            >
                Complete Sale
            </x-filament::button>
        </div>
        
        <form wire:submit.prevent="createOrder">
            {{ $this->form }}
        </form>
    </div>
</x-filament-panels::page> 