<?php

use App\Http\Controllers\WhatsApp\WebhookController;
use Illuminate\Support\Facades\Route;

// WhatsApp webhook (no authentication)
Route::prefix('webhooks')->group(function () {
    Route::get('whatsapp', [WebhookController::class, 'verify']);
    Route::post('whatsapp', [WebhookController::class, 'handle']);
});
