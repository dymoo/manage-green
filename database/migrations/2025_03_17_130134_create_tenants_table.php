<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            
            // Branding
            $table->string('logo_path')->nullable();
            $table->string('primary_color')->default('#059669'); // emerald-600 default
            $table->string('secondary_color')->nullable();
            
            // Domain settings
            $table->string('domain')->nullable()->unique();
            $table->boolean('use_custom_domain')->default(false);
            
            // Club settings
            $table->string('currency')->default('EUR');
            $table->string('timezone')->default('Europe/Amsterdam');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('registration_number')->nullable();
            
            // Feature flags
            $table->boolean('enable_wallet')->default(true);
            $table->boolean('enable_inventory')->default(true);
            $table->boolean('enable_pos')->default(true);
            $table->boolean('enable_member_portal')->default(false);
            
            $table->timestamps();
        });

        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('tenants');
    }
};
