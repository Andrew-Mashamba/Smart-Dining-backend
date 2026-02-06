<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Kitchen channel - only kitchen staff and managers
Broadcast::channel('kitchen', function ($user) {
    return in_array($user->role, ['kitchen_staff', 'manager']);
});

// Bar channel - only bar staff and managers
Broadcast::channel('bar', function ($user) {
    return in_array($user->role, ['bar_staff', 'manager']);
});

// Orders channel - managers and authorized staff
Broadcast::channel('orders', function ($user) {
    return in_array($user->role, ['manager', 'waiter', 'kitchen_staff', 'bar_staff']);
});

// Dashboard channel - managers only
Broadcast::channel('dashboard', function ($user) {
    return $user->role === 'manager';
});

// Waiter-specific channel
Broadcast::channel('waiter.{waiterId}', function ($user, $waiterId) {
    return (int) $user->id === (int) $waiterId && $user->role === 'waiter';
});
