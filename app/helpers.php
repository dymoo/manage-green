<?php

use Filament\Facades\Filament;

/**
 * Get the current tenant from Filament.
 *
 * @return \App\Models\Tenant|null
 */
function tenant()
{
    return Filament::getTenant();
} 