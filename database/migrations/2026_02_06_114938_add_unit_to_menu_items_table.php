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
        Schema::table('menu_items', function (Blueprint $table) {
            // Add unit column after stock_quantity
            if (! Schema::hasColumn('menu_items', 'unit')) {
                $table->enum('unit', ['pieces', 'kg', 'liters', 'ml', 'grams'])->default('pieces')->after('stock_quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            if (Schema::hasColumn('menu_items', 'unit')) {
                $table->dropColumn('unit');
            }
        });
    }
};
