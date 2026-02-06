<?php

namespace App\Policies;

use App\Models\Staff;

class StaffPolicy
{
    /**
     * Determine whether the user can view any models.
     * Admin has full access, manager can view all staff.
     */
    public function viewAny(Staff $staff): bool
    {
        return $staff->isAdmin() || $staff->isManager();
    }

    /**
     * Determine whether the user can view the model.
     * Admin has full access, manager can view staff, staff can view themselves.
     */
    public function view(Staff $staff, Staff $model): bool
    {
        if ($staff->isAdmin()) {
            return true;
        }

        if ($staff->isManager()) {
            return true;
        }

        return $staff->id === $model->id;
    }

    /**
     * Determine whether the user can create models.
     * Admin has full access, manager can create waiters, chefs, and bartenders.
     */
    public function create(Staff $staff): bool
    {
        return $staff->isAdmin() || $staff->isManager();
    }

    /**
     * Determine whether the user can update the model.
     * Admin has full access, manager can update waiters, chefs, and bartenders.
     */
    public function update(Staff $staff, Staff $model): bool
    {
        if ($staff->isAdmin()) {
            return true;
        }

        if ($staff->isManager()) {
            return in_array($model->role, ['waiter', 'chef', 'bartender']);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     * Admin has full access, manager can delete waiters, chefs, and bartenders.
     */
    public function delete(Staff $staff, Staff $model): bool
    {
        if ($staff->isAdmin()) {
            return true;
        }

        if ($staff->isManager()) {
            return in_array($model->role, ['waiter', 'chef', 'bartender']);
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     * Admin has full access, manager can restore waiters, chefs, and bartenders.
     */
    public function restore(Staff $staff, Staff $model): bool
    {
        if ($staff->isAdmin()) {
            return true;
        }

        if ($staff->isManager()) {
            return in_array($model->role, ['waiter', 'chef', 'bartender']);
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Only admin can permanently delete.
     */
    public function forceDelete(Staff $staff, Staff $model): bool
    {
        return $staff->isAdmin();
    }
}
