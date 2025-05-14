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
            // Add new columns after an existing column, e.g., 'password' or 'remember_token'
            // Please adjust 'after' column name if your 'users' table structure is different.
            $table->string('member_status')->nullable()->after('remember_token'); // e.g., pending_approval, active, inactive, rejected, banned
            $table->string('fob_id')->nullable()->after('member_status'); 
            // For tenant-specific uniqueness on fob_id, if tenant_id exists on users table:
            // $table->unique(['tenant_id', 'fob_id']); // Add this if tenant_id column exists
            // Otherwise, uniqueness needs to be handled at the application layer validation scoped to tenant.
            $table->string('phone_number')->nullable()->after('fob_id');
            $table->date('date_of_birth')->nullable()->after('phone_number');
            $table->text('address')->nullable()->after('date_of_birth');
            $table->text('membership_notes')->nullable()->after('address'); // Internal notes by staff
            $table->string('registration_type')->nullable()->after('membership_notes'); // e.g., staff_created, self_signup

            $table->timestamp('approved_at')->nullable()->after('registration_type');
            
            $table->foreignId('approved_by_user_id')
                  ->nullable()
                  ->after('approved_at')
                  ->constrained('users')
                  ->onDelete('set null');
            
            $table->foreignId('registered_by_user_id')
                  ->nullable()
                  ->after('approved_by_user_id')
                  ->constrained('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_by_user_id']);
            $table->dropForeign(['registered_by_user_id']);
            
            $table->dropColumn([
                'member_status',
                'fob_id',
                'phone_number',
                'date_of_birth',
                'address',
                'membership_notes',
                'registration_type',
                'approved_at',
                'approved_by_user_id',
                'registered_by_user_id',
            ]);
        });
    }
}; 