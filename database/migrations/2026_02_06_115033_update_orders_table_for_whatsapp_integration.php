<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Make waiter_id and order_number nullable to support WhatsApp orders.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Make waiter_id nullable for WhatsApp orders (which don't have a waiter)
            $table->foreignId('waiter_id')->nullable()->change();

            // Make order_number nullable temporarily (it's generated after creation)
            $table->string('order_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Restore waiter_id as required
            $table->foreignId('waiter_id')->nullable(false)->change();

            // Restore order_number as required
            $table->string('order_number')->nullable(false)->change();
        });
    }
};
