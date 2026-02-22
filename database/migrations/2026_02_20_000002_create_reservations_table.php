<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('guest_id')->constrained('guests');
            $table->foreignId('table_id')->nullable()->constrained('tables')->nullOnDelete();
            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->integer('party_size');
            $table->string('location')->default('indoor');
            $table->string('status')->default('pending');
            $table->text('special_requests')->nullable();
            $table->string('source')->default('whatsapp');
            $table->timestamps();

            $table->index(['reservation_date', 'reservation_time']);
            $table->index('status');
            $table->index('guest_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
