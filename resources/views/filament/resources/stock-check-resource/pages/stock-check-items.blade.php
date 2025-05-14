<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-col gap-4">
            <div class="flex flex-col md:flex-row gap-4 justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $record->type === \App\Enums\StockCheckType::CHECK_IN ? 'Check In' : 'Check Out' }} -
                        {{ $record->check_in_at ? $record->check_in_at->format('M d, Y') : 'N/A' }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Staff: {{ $record->staff?->name ?? 'N/A' }}
                        {{-- Status: <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium rounded-full
                            @if($record->status === 'pending') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                            @elseif($record->status === 'in_progress') bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-300
                            @else bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-300
                            @endif
                        ">{{ ucfirst($record->status) }}</span> --}}
                    </p>
                </div>
                
                {{-- @if ($record->status === 'completed') --}}
                <div class="md:text-right">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Total Weight Discrepancy</h3>
                    <p class="text-2xl font-bold 
                        @if(($record->total_weight_discrepancy ?? 0) > 0) text-emerald-600 dark:text-emerald-400
                        @elseif(($record->total_weight_discrepancy ?? 0) < 0) text-red-600 dark:text-red-400
                        @else text-gray-500 dark:text-gray-400
                        @endif
                    ">
                        {{ number_format($record->total_weight_discrepancy ?? 0, 3) }}
                    </p>
                </div>
                {{-- @endif --}}
            </div>
            
            @if ($record->start_notes)
            <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">Start Notes</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $record->start_notes }}</p>
            </div>
            @endif

            @if ($record->end_notes)
            <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">End Notes</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $record->end_notes }}</p>
            </div>
            @endif
            
            {{-- @if ($record->status !== 'completed') --}}
            <div class="bg-amber-50 dark:bg-amber-900/20 p-3 rounded-lg border border-amber-200 dark:border-amber-700">
                <h3 class="text-sm font-medium text-amber-800 dark:text-amber-300">Instructions</h3>
                <p class="text-sm text-amber-700 dark:text-amber-400">
                    @if ($record->type === \App\Enums\StockCheckType::CHECK_IN)
                        This is a morning check-in. Verify the actual quantities of each product at the start of the day.
                    @else
                        This is an evening check-out. Record the final stock levels before closing.
                        These values will update your system stock when you complete the check.
                    @endif
                </p>
            </div>
            {{-- @endif --}}
        </div>
    </x-filament::section>
    
    <x-filament::section>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page> 