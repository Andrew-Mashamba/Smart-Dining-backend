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
        // Check if status column exists before attempting to drop it
        $hasStatusColumn = Schema::hasColumn('guest_sessions', 'status');

        // For SQLite, we need to drop index first if it exists
        if (Schema::getConnection()->getDriverName() === 'sqlite' && $hasStatusColumn) {
            // Drop index manually for SQLite before dropping column
            try {
                Schema::table('guest_sessions', function (Blueprint $table) {
                    $table->dropIndex('guest_sessions_status_index');
                });
            } catch (\Exception $e) {
                // Index might not exist, continue
            }
        }

        Schema::table('guest_sessions', function (Blueprint $table) use ($hasStatusColumn) {
            // Drop existing foreign keys if they exist
            try {
                $table->dropForeign(['guest_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }

            try {
                $table->dropForeign(['table_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }

            // Drop status column only if it exists
            if ($hasStatusColumn) {
                $table->dropColumn('status');
            }
        });

        Schema::table('guest_sessions', function (Blueprint $table) {
            // Make guest_id nullable and table_id not nullable
            $table->foreignId('guest_id')->nullable()->change()->constrained('guests')->onDelete('set null');
            $table->foreignId('table_id')->nullable(false)->change()->constrained('tables')->onDelete('cascade');

            // Update session_token to have length of 32
            $table->string('session_token', 32)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guest_sessions', function (Blueprint $table) {
            // Reverse the changes
            $table->dropForeign(['guest_id']);
            $table->dropForeign(['table_id']);

            $table->foreignId('guest_id')->nullable(false)->change()->constrained('guests')->onDelete('cascade');
            $table->foreignId('table_id')->nullable()->change()->constrained('tables')->onDelete('set null');

            $table->string('session_token')->change();

            $table->enum('status', ['active', 'closed'])->default('active')->after('session_token');
        });
    }
};
