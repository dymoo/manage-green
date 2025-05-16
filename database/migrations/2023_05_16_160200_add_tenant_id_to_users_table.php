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
        Schema::table('users', function (Blueprint $table) {
            // Add tenant_id after remember_token or another suitable column
            // Ensure it allows nulls if users can exist without a tenant (e.g., global admins)
            // Or make it non-nullable if every user MUST belong to a tenant.
            // Let's assume nullable for flexibility for now.
            $table->foreignId('tenant_id')
                  ->nullable() // Adjust if necessary
                  ->after('remember_token') 
                  ->constrained('tenants') // Assumes your tenants table is named 'tenants'
                  ->onDelete('cascade'); // Or 'set null' depending on desired behavior
            
            // Optional: Add index for performance
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key constraint first (naming convention: table_column_foreign)
            $table->dropForeign(['tenant_id']);
            // Drop the column
            $table->dropColumn('tenant_id');
        });
    }
}; 