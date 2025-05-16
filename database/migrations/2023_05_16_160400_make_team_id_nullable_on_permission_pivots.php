<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'team_id';

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamForeignKey) {
            $table->unsignedBigInteger($teamForeignKey)->nullable()->change();
        });

        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamForeignKey) {
            $table->unsignedBigInteger($teamForeignKey)->nullable()->change();
        });
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'team_id';

        // Reverting nullable() back to not nullable might require knowing the original state
        // or dropping and re-adding the column if defaults were involved.
        // For simplicity, we'll assume it was not nullable before.
        // IMPORTANT: This down() method might fail if foreign key constraints are an issue
        // or if there's data with NULL in team_id.
        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamForeignKey) {
            $table->unsignedBigInteger($teamForeignKey)->nullable(false)->change();
        });

        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamForeignKey) {
            $table->unsignedBigInteger($teamForeignKey)->nullable(false)->change();
        });
    }
}; 