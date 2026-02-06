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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Table identifier (e.g., Table 1, Bar Seat 3)');
            $table->string('location')->comment('Table location in restaurant');
            $table->integer('capacity')->comment('Number of seats');
            $table->enum('status', ['available', 'occupied', 'reserved'])->default('available');
            $table->text('qr_code')->nullable()->comment('QR code SVG for table self-service');
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
