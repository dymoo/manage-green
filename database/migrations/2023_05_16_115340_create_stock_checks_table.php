<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\StockCheckType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->comment('User performing the check-in')->constrained('users')->cascadeOnDelete();
            $table->string('type')->default(StockCheckType::CHECK_IN->value);
            $table->timestamp('check_in_at')->useCurrent();
            $table->timestamp('check_out_at')->nullable();
            $table->foreignId('checked_out_by')->nullable()->comment('User performing the check-out')->constrained('users')->nullOnDelete();
            $table->text('start_notes')->nullable();
            $table->text('end_notes')->nullable();
            $table->decimal('total_expected_value', 10, 2)->nullable();
            $table->decimal('total_actual_value', 10, 2)->nullable();
            $table->decimal('total_discrepancy_value', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_checks');
    }
};
