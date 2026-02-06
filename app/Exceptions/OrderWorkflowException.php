<?php

namespace App\Exceptions;

use Exception;

class OrderWorkflowException extends Exception
{
    /**
     * Create a new exception for invalid status transition.
     */
    public static function invalidTransition(string $currentStatus, string $newStatus): self
    {
        return new self("Invalid status transition from '{$currentStatus}' to '{$newStatus}'.");
    }

    /**
     * Create a new exception when order items are not ready.
     */
    public static function itemsNotReady(int $orderId): self
    {
        return new self("Order #{$orderId} cannot be marked as ready because not all items have prep_status='ready'.");
    }

    /**
     * Create a new exception when payment is insufficient.
     */
    public static function insufficientPayment(int $orderId, float $totalPaid, float $totalRequired): self
    {
        return new self("Order #{$orderId} cannot be marked as paid. Total paid: {$totalPaid}, Required: {$totalRequired}.");
    }

    /**
     * Create a new exception for order not found.
     */
    public static function orderNotFound(int $orderId): self
    {
        return new self("Order #{$orderId} not found.");
    }
}
