<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;

class UpdateKitchenDisplay
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        //
    }
}
