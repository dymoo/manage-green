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
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }

        // Add tenant_id to roles table
        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained();
        });

        // Add tenant_id to model_has_roles pivot table
        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) {
            // Define column type explicitly, then make nullable, then add constraint
            $table->unsignedBigInteger('tenant_id')->after('role_id')->nullable(); 
            // Add foreign key constraint separately
            // Assuming the tenants table uses the default 'id' primary key
            // We might need to specify the table name if Tenant model uses a different one
            // $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete(); // Or cascade? Check requirements
        });

        // Add tenant_id to model_has_permissions pivot table
        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) {
            // Define column type explicitly, then make nullable, then add constraint
            $table->unsignedBigInteger('tenant_id')->after('permission_id')->nullable();
            // Add foreign key constraint separately
            // $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }

        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
}; 