<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Staff extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'pin',
        'role',
        'phone_number',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'pin',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'pin' => 'hashed',
        'role' => 'string',
        'status' => 'string',
    ];

    /**
     * Set the staff's PIN (4-digit code).
     */
    public function setPin(string $pin): void
    {
        if (strlen($pin) !== 4 || !ctype_digit($pin)) {
            throw new \InvalidArgumentException('PIN must be exactly 4 digits');
        }
        $this->pin = $pin; // Will be hashed automatically via cast
        $this->save();
    }

    /**
     * Verify the staff's PIN.
     */
    public function verifyPin(string $pin): bool
    {
        if (!$this->pin) {
            return false;
        }
        return \Illuminate\Support\Facades\Hash::check($pin, $this->pin);
    }

    /**
     * Check if staff has a PIN set.
     */
    public function hasPin(): bool
    {
        return !empty($this->pin);
    }

    /**
     * Get orders assigned to this waiter.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'waiter_id');
    }

    /**
     * Get order items prepared by this staff.
     */
    public function preparedItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'prepared_by');
    }

    /**
     * Get tips received by this waiter.
     */
    public function tips(): HasMany
    {
        return $this->hasMany(Tip::class, 'waiter_id');
    }

    /**
     * Get all inventory transactions created by this staff member.
     */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'created_by');
    }

    /**
     * Check if staff is a waiter.
     */
    public function isWaiter(): bool
    {
        return $this->role === 'waiter';
    }

    /**
     * Check if staff is a chef.
     */
    public function isChef(): bool
    {
        return $this->role === 'chef';
    }

    /**
     * Check if staff is a bartender.
     */
    public function isBartender(): bool
    {
        return $this->role === 'bartender';
    }

    /**
     * Check if staff is a manager.
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Check if staff is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if staff has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
}
