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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained()->onDelete('cascade');
            $table->enum('transaction_type', ['restock', 'sale', 'adjustment', 'waste']);
            $table->integer('quantity');
            $table->enum('unit', ['pieces', 'kg', 'liters', 'ml', 'grams']);
            $table->string('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('staff')->onDelete('cascade');
            $table->timestamps();

            // Indexes for performance
            $table->index('menu_item_id');
            $table->index('transaction_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
