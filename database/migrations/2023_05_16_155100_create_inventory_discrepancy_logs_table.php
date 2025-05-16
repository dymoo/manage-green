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
        Schema::create('inventory_discrepancy_logs', function (Blueprint $table) {
            $table->id();
            // Assuming filament tenancy setup handles tenant scoping automatically.
            // If using a different package or manual scoping, add:
            // $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->comment('Staff member who performed the end-of-day check')->constrained()->cascadeOnDelete();
            $table->date('log_date');
            $table->decimal('expected_weight_start', 10, 3)->comment('Expected weight at start of day (g)');
            $table->decimal('actual_weight_start', 10, 3)->nullable()->comment('Actual weight counted at start of day (g)'); // May not always be counted
            $table->decimal('expected_weight_end', 10, 3)->comment('Calculated expected weight at end of day (g)');
            $table->decimal('actual_weight_end', 10, 3)->comment('Actual weight counted at end of day (g)');
            $table->decimal('discrepancy_weight', 10, 3)->comment('Difference: actual_weight_end - expected_weight_end (g)');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'log_date']); // Ensure only one log per product per day
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_discrepancy_logs');
    }
};
