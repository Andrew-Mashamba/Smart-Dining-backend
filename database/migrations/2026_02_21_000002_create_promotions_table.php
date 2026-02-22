<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 30)->default('general'); // daily_special, happy_hour, discount, combo, general
            $table->string('discount_type', 20)->nullable(); // percentage, fixed_amount
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->json('applicable_items')->nullable(); // menu_item_ids or category_ids, null = all
            $table->string('day_of_week', 10)->nullable(); // monday, tuesday, ... null = every day
            $table->time('start_time')->nullable(); // for happy hour
            $table->time('end_time')->nullable(); // for happy hour
            $table->date('start_date')->nullable(); // promotion validity start
            $table->date('end_date')->nullable(); // promotion validity end
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'start_date', 'end_date']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
