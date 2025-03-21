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
            $table->foreignId('tenant_id')->nullable()->after('role_id');
        });

        // Add tenant_id to model_has_permissions pivot table
        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('permission_id');
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