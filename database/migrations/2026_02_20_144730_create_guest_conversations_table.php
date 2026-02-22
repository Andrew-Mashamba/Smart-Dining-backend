<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained('guests')->cascadeOnDelete();
            $table->string('role', 20);          // 'user' or 'assistant'
            $table->text('content');              // message text
            $table->string('message_type', 30)->default('text'); // text, order, reservation, feedback
            $table->json('metadata')->nullable(); // extra data (order_id, items, etc.)
            $table->timestamps();

            $table->index('guest_id');
            $table->index('created_at');
            $table->index(['guest_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_conversations');
    }
};
