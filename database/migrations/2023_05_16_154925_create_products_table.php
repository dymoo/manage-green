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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('weight', 8, 3)->nullable(); // Weight in grams
            $table->decimal('current_stock', 10, 3)->default(0); // Current stock in grams
            $table->decimal('minimum_stock', 10, 3)->default(0); // Minimum stock threshold
            $table->boolean('active')->default(true);
            $table->json('attributes')->nullable(); // For additional attributes like THC/CBD percentage
            $table->timestamps();
            
            // Create a unique constraint on SKU per tenant
            $table->unique(['tenant_id', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
