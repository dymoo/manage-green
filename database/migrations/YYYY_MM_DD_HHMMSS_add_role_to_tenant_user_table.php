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
        // Check if the tenant_user table exists before attempting to modify it
        if (!Schema::hasTable('tenant_user')) {
            // If it doesn't exist, create it with the necessary columns
            // This might happen if the default many-to-many table wasn't created by a specific package migration
            Schema::create('tenant_user', function (Blueprint $table) {
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('role')->nullable(); // Add the role column
                $table->timestamps(); // Optional: if you want to track when users are added/updated in tenants

                $table->primary(['tenant_id', 'user_id']); // Composite primary key
            });
        } else {
            // If the table exists, just add the role column if it doesn't already exist
            if (!Schema::hasColumn('tenant_user', 'role')) {
                Schema::table('tenant_user', function (Blueprint $table) {
                    $table->string('role')->nullable()->after('user_id'); // Add the role column
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tenant_user') && Schema::hasColumn('tenant_user', 'role')) {
            Schema::table('tenant_user', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
        // If the table was created by this migration's up() method, uncomment the line below to drop it on rollback.
        // else if (Schema::hasTable('tenant_user')) { // Be cautious if other migrations depend on this table
        //     Schema::dropIfExists('tenant_user');
        // }
    }
}; 