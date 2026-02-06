<?php

namespace App\Exceptions;

use Exception;

class PaymentException extends Exception
{
    /**
     * Create a new exception for payment processing failure.
     */
    public static function processingFailed(string $reason): self
    {
        return new self("Payment processing failed: {$reason}");
    }

    /**
     * Create a new exception for insufficient amount.
     */
    public static function insufficientAmount(float $amount, float $required): self
    {
        return new self("Insufficient payment amount. Provided: {$amount}, Required: {$required}");
    }

    /**
     * Create a new exception for invalid payment method.
     */
    public static function invalidMethod(string $method): self
    {
        return new self("Invalid payment method: {$method}");
    }

    /**
     * Create a new exception for payment gateway error.
     */
    public static function gatewayError(string $gateway, string $error): self
    {
        return new self("Payment gateway '{$gateway}' error: {$error}");
    }

    /**
     * Create a new exception for refund failure.
     */
    public static function refundFailed(int $paymentId, string $reason): self
    {
        return new self("Refund failed for payment #{$paymentId}: {$reason}");
    }

    /**
     * Create a new exception for duplicate payment.
     */
    public static function duplicatePayment(string $transactionId): self
    {
        return new self("Duplicate payment detected. Transaction ID: {$transactionId}");
    }
}
