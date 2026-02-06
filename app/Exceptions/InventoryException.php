<?php

namespace App\Exceptions;

use Exception;

class InventoryException extends Exception
{
    /**
     * Create a new exception for insufficient stock.
     */
    public static function insufficientStock(int $menuItemId, int $requested, int $available): self
    {
        return new self("Insufficient stock for menu item #{$menuItemId}. Requested: {$requested}, Available: {$available}");
    }

    /**
     * Create a new exception for out of stock.
     */
    public static function outOfStock(int $menuItemId, string $itemName): self
    {
        return new self("Menu item '{$itemName}' (#{$menuItemId}) is out of stock");
    }

    /**
     * Create a new exception for invalid quantity.
     */
    public static function invalidQuantity(int $quantity): self
    {
        return new self("Invalid quantity: {$quantity}. Quantity must be a positive integer");
    }

    /**
     * Create a new exception for stock update failure.
     */
    public static function updateFailed(int $menuItemId, string $reason): self
    {
        return new self("Failed to update stock for menu item #{$menuItemId}: {$reason}");
    }

    /**
     * Create a new exception for inventory item not found.
     */
    public static function itemNotFound(int $menuItemId): self
    {
        return new self("Inventory item #{$menuItemId} not found");
    }

    /**
     * Create a new exception for negative stock.
     */
    public static function negativeStock(int $menuItemId): self
    {
        return new self("Cannot set negative stock for menu item #{$menuItemId}");
    }
}
