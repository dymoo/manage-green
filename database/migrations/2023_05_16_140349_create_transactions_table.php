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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->comment('User associated with the transaction (e.g., member)')->constrained('users')->cascadeOnDelete();
            
            $table->string('type'); // e.g., 'purchase', 'top_up', 'refund', 'adjustment'
            $table->decimal('amount', 10, 2); // Positive for income (top-up), negative for expense (purchase)
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after', 10, 2);
            
            $table->string('description')->nullable(); // Optional description
            $table->string('reference')->nullable()->unique(); // Optional unique reference code
            
            // Polymorphic relation to link to the source of the transaction (optional but useful)
            // $table->morphs('transactable'); // e.g., Sale model, TopUp model
            
            $table->foreignId('staff_id')->nullable()->comment('Staff member who processed the transaction')->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
