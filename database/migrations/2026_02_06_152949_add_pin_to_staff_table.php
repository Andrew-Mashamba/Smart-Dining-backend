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
        Schema::table('staff', function (Blueprint $table) {
            // PIN for quick waiter login (4-digit code, stored hashed)
            $table->string('pin', 255)->nullable()->after('password');

            // Index for faster PIN lookups (we'll lookup by staff_id + pin)
            $table->index(['id', 'pin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropIndex(['id', 'pin']);
            $table->dropColumn('pin');
        });
    }
};
