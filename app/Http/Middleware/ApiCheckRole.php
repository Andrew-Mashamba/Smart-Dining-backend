<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API-specific role checking middleware
 *
 * This middleware extends CheckRole functionality for API routes,
 * returning JSON responses instead of redirects.
 */
class ApiCheckRole extends CheckRole
{
    /**
     * Handle an incoming API request with role-based authorization.
     *
     * This middleware checks both user roles and Sanctum token abilities
     * for granular access control.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Check if user is authenticated
        if (! $request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user = $request->user();

        // Check if user has one of the required roles
        if (! in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'Insufficient permissions',
            ], 403);
        }

        // Check Sanctum token abilities for additional granular control
        // This allows tokens to be issued with specific scopes even if the user has the role
        // Only check abilities if token abilities are explicitly set
        $token = $user->currentAccessToken();
        if ($token && $token->abilities !== null && is_array($token->abilities) && count($token->abilities) > 0 && ! in_array('*', $token->abilities)) {
            $requiredAbility = $this->mapRoleToAbility($user->role);

            if ($requiredAbility && ! $user->tokenCan($requiredAbility)) {
                return response()->json([
                    'message' => 'Insufficient permissions',
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * Map user role to required Sanctum token ability.
     *
     * This provides granular permission control using Sanctum token abilities.
     * When creating tokens, assign abilities based on what actions the token should allow:
     *
     * Examples:
     * - Waiter: ['waiter:access', 'order:create', 'order:view', 'payment:process']
     * - Chef: ['chef:access', 'kitchen:view', 'kitchen:update']
     * - Bartender: ['bartender:access', 'bar:view', 'bar:update']
     * - Manager: ['manager:access', '*'] (full access)
     * - Admin: ['admin:access', '*'] (full access)
     */
    protected function mapRoleToAbility(string $role): ?string
    {
        return match ($role) {
            'waiter' => 'waiter:access',
            'chef' => 'chef:access',
            'bartender' => 'bartender:access',
            'manager' => 'manager:access',
            'admin' => 'admin:access',
            default => null,
        };
    }
}
