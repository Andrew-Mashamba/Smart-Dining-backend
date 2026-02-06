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
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                // Check and drop old columns if they exist
                if (Schema::hasColumn('orders', 'session_id')) {
                    $table->dropColumn('session_id');
                }
                if (Schema::hasColumn('orders', 'service_charge')) {
                    $table->dropColumn('service_charge');
                }
                if (Schema::hasColumn('orders', 'total_amount')) {
                    $table->dropColumn('total_amount');
                }
                if (Schema::hasColumn('orders', 'notes')) {
                    $table->dropColumn('notes');
                }
            });

            Schema::table('orders', function (Blueprint $table) {
                // Add new columns if they don't exist
                if (! Schema::hasColumn('orders', 'order_number')) {
                    $table->string('order_number')->unique()->after('id');
                }
                if (! Schema::hasColumn('orders', 'total')) {
                    $table->decimal('total', 10, 2)->after('tax');
                }
                if (! Schema::hasColumn('orders', 'special_instructions')) {
                    $table->text('special_instructions')->nullable()->after('total');
                }

                // Modify existing columns to be nullable
                $table->foreignId('table_id')->nullable()->change();
                $table->foreignId('guest_id')->nullable()->change();
            });

            // Update status enum values
            if (Schema::hasColumn('orders', 'status')) {
                Schema::table('orders', function (Blueprint $table) {
                    // Drop index first if it exists
                    if (Schema::hasIndex('orders', 'orders_status_index')) {
                        $table->dropIndex('orders_status_index');
                    }
                    $table->dropColumn('status');
                });
            }

            Schema::table('orders', function (Blueprint $table) {
                $table->enum('status', ['pending', 'preparing', 'ready', 'delivered', 'paid', 'cancelled'])->default('pending')->after('order_source');
            });

            // Add new index
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasColumn('orders', 'order_number')) {
                    $table->index('order_number');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Restore old columns
            $table->dropColumn(['order_number', 'total', 'special_instructions']);
            $table->foreignId('session_id')->nullable()->after('waiter_id');
            $table->decimal('service_charge', 10, 2)->default(0)->after('tax');
            $table->decimal('total_amount', 10, 2)->default(0)->after('service_charge');
            $table->text('notes')->nullable()->after('total_amount');

            // Restore status enum
            $table->dropColumn('status');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'ready', 'served', 'completed', 'cancelled'])->default('pending')->after('session_id');

            // Restore indexes
            $table->index('guest_id');
            $table->index('table_id');
            $table->index('waiter_id');
            $table->index('order_source');
            $table->dropIndex(['order_number']);
        });
    }
};
