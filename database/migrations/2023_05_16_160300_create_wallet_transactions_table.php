<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // User who owns the wallet
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete(); // Staff who initiated
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();

            $table->string('type'); // e.g., deposit, withdrawal, purchase, refund, correction
            $table->decimal('amount', 15, 4); // Positive for deposit, negative for withdrawal
            $table->decimal('balance_before', 15, 4);
            $table->decimal('balance_after', 15, 4);
            
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
}; 