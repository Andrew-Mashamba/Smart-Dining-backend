<?php

namespace App\Providers;

use App\Models\Staff;
use App\Policies\StaffPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Staff::class => StaffPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define role-specific gates
        Gate::define('manage-staff', function (Staff $staff) {
            return $staff->isAdmin() || $staff->isManager();
        });

        Gate::define('manage-orders', function (Staff $staff) {
            return in_array($staff->role, ['admin', 'manager', 'waiter']);
        });

        Gate::define('manage-kitchen', function (Staff $staff) {
            return in_array($staff->role, ['admin', 'manager', 'chef']);
        });

        Gate::define('manage-bar', function (Staff $staff) {
            return in_array($staff->role, ['admin', 'manager', 'bartender']);
        });

        Gate::define('access-admin', function (Staff $staff) {
            return $staff->isAdmin();
        });

        Gate::define('access-manager', function (Staff $staff) {
            return $staff->isAdmin() || $staff->isManager();
        });

        Gate::define('view-reports', function (Staff $staff) {
            return $staff->isAdmin() || $staff->isManager();
        });

        Gate::define('manage-settings', function (Staff $staff) {
            return $staff->isAdmin();
        });
    }
}
