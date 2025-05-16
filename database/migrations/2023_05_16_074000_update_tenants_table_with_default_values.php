<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenant;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Set default values for all existing tenants
        Tenant::all()->each(function (Tenant $tenant) {
            $tenant->update([
                'primary_color' => '#059669', // emerald-600
                'currency' => 'EUR',
                'timezone' => 'Europe/Amsterdam',
                'enable_wallet' => true,
                'enable_inventory' => true,
                'enable_pos' => true,
                'enable_member_portal' => false,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this migration as it only adds default values
    }
};
