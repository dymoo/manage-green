<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'team_id';

        // For model_has_roles table
        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamForeignKey) {
            // Drop the primary key first
            $table->dropPrimary();
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamForeignKey) {
            // Make the team_id column nullable
            $table->unsignedBigInteger($teamForeignKey)->nullable()->change();
            
            // Add a new primary key that doesn't include team_id
            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        // For model_has_permissions table
        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamForeignKey) {
            // Drop the primary key first
            $table->dropPrimary();
        });

        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamForeignKey) {
            // Make the team_id column nullable
            $table->unsignedBigInteger($teamForeignKey)->nullable()->change();
            
            // Add a new primary key that doesn't include team_id
            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'team_id';

        // For model_has_roles table - revert changes
        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamForeignKey) {
            // First, we need to make sure there are no NULL values
            DB::table($tableNames['model_has_roles'])->whereNull($teamForeignKey)->update([$teamForeignKey => 0]);
            
            // Drop the existing primary key
            $table->dropPrimary('model_has_roles_role_model_type_primary');
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamForeignKey) {
            // Make not nullable again
            $table->unsignedBigInteger($teamForeignKey)->nullable(false)->change();
            
            // Recreate the primary key with team_id included
            $table->primary([$teamForeignKey, 'role_id', 'model_id', 'model_type']);
        });

        // For model_has_permissions table - revert changes
        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamForeignKey) {
            // First, we need to make sure there are no NULL values
            DB::table($tableNames['model_has_permissions'])->whereNull($teamForeignKey)->update([$teamForeignKey => 0]);
            
            // Drop the existing primary key
            $table->dropPrimary('model_has_permissions_permission_model_type_primary');
        });

        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamForeignKey) {
            // Make not nullable again
            $table->unsignedBigInteger($teamForeignKey)->nullable(false)->change();
            
            // Recreate the primary key with team_id included
            $table->primary([$teamForeignKey, 'permission_id', 'model_id', 'model_type']);
        });
    }
}; 