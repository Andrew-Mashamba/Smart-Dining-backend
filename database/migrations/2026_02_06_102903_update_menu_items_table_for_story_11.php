<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            // Add category_id foreign key if it doesn't exist
            if (! Schema::hasColumn('menu_items', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('id')->constrained('menu_categories')->onDelete('cascade');
            }

            // Rename preparation_time to prep_time_minutes if needed
            if (Schema::hasColumn('menu_items', 'preparation_time') && ! Schema::hasColumn('menu_items', 'prep_time_minutes')) {
                $table->renameColumn('preparation_time', 'prep_time_minutes');
            }
        });

        Schema::table('menu_items', function (Blueprint $table) {
            // Add stock tracking columns if they don't exist
            if (! Schema::hasColumn('menu_items', 'stock_quantity')) {
                $table->integer('stock_quantity')->nullable()->after('prep_time_minutes');
            }
            if (! Schema::hasColumn('menu_items', 'low_stock_threshold')) {
                $table->integer('low_stock_threshold')->nullable()->after('stock_quantity');
            }

            // Add status column if it doesn't exist
            if (! Schema::hasColumn('menu_items', 'status')) {
                $table->enum('status', ['available', 'unavailable'])->default('available')->after('low_stock_threshold');
            }

            // Modify prep_area to include 'both' option
            $table->enum('prep_area', ['kitchen', 'bar', 'both'])->change();
        });

        // Migrate is_available to status if is_available exists
        if (Schema::hasColumn('menu_items', 'is_available')) {
            DB::statement("UPDATE menu_items SET status = CASE WHEN is_available = 1 THEN 'available' ELSE 'unavailable' END");

            Schema::table('menu_items', function (Blueprint $table) {
                // Drop old indexes
                $table->dropIndex('menu_items_category_index');
                $table->dropIndex('menu_items_prep_area_index');
                $table->dropIndex('menu_items_is_available_index');

                // Drop old columns
                $table->dropColumn(['category', 'image_url', 'is_available']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            // Add back old columns
            $table->enum('category', ['appetizer', 'main', 'dessert', 'drink', 'special'])->default('main')->after('description');
            $table->string('image_url')->nullable()->after('prep_area');
            $table->boolean('is_available')->default(true)->after('image_url');

            // Rename back
            $table->renameColumn('prep_time_minutes', 'preparation_time');

            // Remove new columns
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'status', 'stock_quantity', 'low_stock_threshold']);

            // Modify prep_area back
            $table->enum('prep_area', ['kitchen', 'bar'])->change();

            // Re-add indexes
            $table->index('category');
            $table->index('is_available');
        });
    }
};
