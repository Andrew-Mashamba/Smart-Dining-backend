<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_type', 20)->default('dine_in')->after('order_source');
            $table->string('delivery_address')->nullable()->after('special_instructions');
            $table->string('delivery_phone')->nullable()->after('delivery_address');
            $table->timestamp('estimated_ready_at')->nullable()->after('delivery_phone');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['order_type', 'delivery_address', 'delivery_phone', 'estimated_ready_at']);
        });
    }
};
