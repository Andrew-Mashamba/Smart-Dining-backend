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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', ['appetizer', 'main', 'dessert', 'drink', 'special'])->default('main');
            $table->decimal('price', 10, 2);
            $table->enum('prep_area', ['kitchen', 'bar']);
            $table->string('image_url')->nullable();
            $table->boolean('is_available')->default(true);
            $table->integer('preparation_time')->comment('Estimated prep time in minutes');
            $table->timestamps();

            // Indexes for performance
            $table->index('category');
            $table->index('prep_area');
            $table->index('is_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
