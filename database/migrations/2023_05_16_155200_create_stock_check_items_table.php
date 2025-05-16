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
        Schema::create('stock_check_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_check_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            
            $table->decimal('start_quantity', 10, 3)->comment('Stock quantity at check-in');
            $table->decimal('end_quantity', 10, 3)->nullable()->comment('Stock quantity at check-out');
            $table->decimal('quantity_sold', 10, 3)->nullable()->comment('Calculated difference or POS recorded');
            $table->decimal('discrepancy', 10, 3)->nullable()->comment('Calculated start - end - sold');
            
            $table->timestamps(); // Optional, might not be needed per item
            
            // Ensure a product isn't added twice to the same check
            $table->unique(['stock_check_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_check_items');
    }
};
