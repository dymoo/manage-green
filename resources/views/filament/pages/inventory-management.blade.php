<x-filament-panels::page>
    <form wire:submit="checkIn" id="check-in-form">
        {{ $this->form }}
    
        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit" form="check-in-form" wire:target="checkIn">
                Check In Stock
            </x-filament::button>
        </div>
    </form>
    
    <form wire:submit="checkOut" id="check-out-form">
        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit" form="check-out-form" wire:target="checkOut" color="warning">
                Check Out Stock
            </x-filament::button>
        </div>
    </form>
    
    <form wire:submit="adjustStock" id="adjustment-form">
        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit" form="adjustment-form" wire:target="adjustStock" color="danger">
                Adjust Stock
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page> 