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
        Schema::table('tips', function (Blueprint $table) {
            // Drop the foreign key first
            if (Schema::hasColumn('tips', 'payment_id')) {
                $table->dropForeign(['payment_id']);
            }

            // Drop indexes if they exist
            if (Schema::hasIndex('tips', 'tips_payment_id_index')) {
                $table->dropIndex('tips_payment_id_index');
            }
            if (Schema::hasIndex('tips', 'tips_order_id_index')) {
                $table->dropIndex('tips_order_id_index');
            }

            // Drop columns if they exist
            if (Schema::hasColumn('tips', 'payment_id')) {
                $table->dropColumn('payment_id');
            }
            if (Schema::hasColumn('tips', 'method')) {
                $table->dropColumn('method');
            }
        });

        Schema::table('tips', function (Blueprint $table) {
            // Add tip_method column with correct enum values if it doesn't exist
            if (! Schema::hasColumn('tips', 'tip_method')) {
                $table->enum('tip_method', ['cash', 'card'])->after('amount');
            }

            // Update amount precision from 10,2 to 8,2
            $table->decimal('amount', 8, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tips', function (Blueprint $table) {
            // Reverse the changes
            $table->dropColumn('tip_method');
        });

        Schema::table('tips', function (Blueprint $table) {
            $table->enum('method', ['cash', 'digital'])->default('cash')->after('amount');
            $table->decimal('amount', 10, 2)->change();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->index('order_id');
            $table->index('payment_id');
        });
    }
};
