<?php

namespace App\Listeners;

use App\Events\OrderItemReady;

class NotifyWaiter
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
    public function handle(OrderItemReady $event): void
    {
        //
    }
}
