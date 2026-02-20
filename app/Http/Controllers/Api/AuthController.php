<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login staff member and generate API token
     */
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $staff = Staff::where('email', $validated['email'])->first();

        if (! $staff || ! Hash::check($validated['password'], $staff->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($staff->status !== 'active') {
            return response()->json([
                'message' => 'Your account is inactive. Please contact the administrator.',
            ], 403);
        }

        $staff->tokens()->delete();

        $abilities = $this->getAbilitiesByRole($staff->role);
        $token = $staff->createToken(
            $validated['device_name'] ?? 'default',
            $abilities
        )->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => $staff->role,
                'phone_number' => $staff->phone_number,
                'has_pin' => $staff->hasPin(),
            ],
        ]);
    }

    /**
     * Login staff member using 4-digit PIN (for waiters/quick access)
     */
    public function loginWithPin(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|integer|exists:staff,id',
            'pin' => 'required|string|size:4',
            'device_name' => 'nullable|string',
        ]);

        $staff = Staff::find($validated['staff_id']);

        if (! $staff) {
            return response()->json([
                'message' => 'Staff member not found.',
            ], 404);
        }

        if (! $staff->hasPin()) {
            return response()->json([
                'message' => 'PIN not set. Please contact your manager to set up your PIN.',
            ], 422);
        }

        if (! $staff->verifyPin($validated['pin'])) {
            return response()->json([
                'message' => 'Invalid PIN.',
            ], 401);
        }

        if ($staff->status !== 'active') {
            return response()->json([
                'message' => 'Your account is inactive. Please contact the administrator.',
            ], 403);
        }

        // Revoke existing tokens
        $staff->tokens()->delete();

        $abilities = $this->getAbilitiesByRole($staff->role);
        $token = $staff->createToken(
            $validated['device_name'] ?? 'pos-pin-login',
            $abilities
        )->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => $staff->role,
                'phone_number' => $staff->phone_number,
                'has_pin' => true,
            ],
        ]);
    }

    /**
     * Set or update own PIN (requires current password verification)
     */
    public function setPin(Request $request)
    {
        $validated = $request->validate([
            'pin' => 'required|string|size:4|regex:/^[0-9]{4}$/',
            'current_password' => 'required|string',
        ]);

        $staff = $request->user();

        // Verify current password before allowing PIN change
        if (! Hash::check($validated['current_password'], $staff->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
            ], 401);
        }

        $staff->setPin($validated['pin']);

        return response()->json([
            'message' => 'PIN set successfully.',
        ]);
    }

    /**
     * Set PIN for a staff member (manager/admin only)
     */
    public function setStaffPin(Request $request, int $staffId)
    {
        $validated = $request->validate([
            'pin' => 'required|string|size:4|regex:/^[0-9]{4}$/',
        ]);

        $currentUser = $request->user();

        // Only managers and admins can set other staff PINs
        if (! in_array($currentUser->role, ['manager', 'admin'])) {
            return response()->json([
                'message' => 'Unauthorized. Only managers can set staff PINs.',
            ], 403);
        }

        $staff = Staff::find($staffId);

        if (! $staff) {
            return response()->json([
                'message' => 'Staff member not found.',
            ], 404);
        }

        $staff->setPin($validated['pin']);

        return response()->json([
            'message' => 'PIN set successfully for ' . $staff->name,
        ]);
    }

    /**
     * Get list of all active staff members for PIN login selection.
     * Returns only staff who have a PIN set (id, name, role).
     */
    public function getStaffForPinLogin()
    {
        $staff = Staff::where('status', 'active')
            ->whereNotNull('pin')
            ->where('pin', '!=', '')
            ->select('id', 'name', 'role')
            ->orderBy('name')
            ->get();

        return response()->json([
            'staff' => $staff,
        ]);
    }

    /**
     * Logout staff member and revoke token
     */
    public function logout(Request $request)
    {
        // Remove FCM device tokens for this staff member
        $request->user()->deviceTokens()->delete();

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Refresh token by generating a new one
     */
    public function refresh(Request $request)
    {
        $staff = $request->user();
        $request->user()->currentAccessToken()->delete();

        $abilities = $this->getAbilitiesByRole($staff->role);
        $token = $staff->createToken('refreshed-token', $abilities)->plainTextToken;

        return response()->json([
            'message' => 'Token refreshed successfully',
            'token' => $token,
        ]);
    }

    /**
     * Get current authenticated user
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'role' => $request->user()->role,
                'phone_number' => $request->user()->phone_number,
            ],
        ]);
    }

    /**
     * Get token abilities based on staff role
     *
     * Token abilities provide granular permission control for Sanctum API tokens.
     * These can be checked using $user->tokenCan('ability') in controllers.
     *
     * Each role gets a base access ability (e.g., 'waiter:access') that the
     * ApiCheckRole middleware validates, plus specific action abilities.
     */
    protected function getAbilitiesByRole(string $role): array
    {
        $abilities = [
            // Admin: Full access including staff management
            'admin' => ['*', 'admin:access'],

            // Manager: Full access to all operational endpoints
            'manager' => ['*', 'manager:access'],

            // Waiter: Can create orders, view own orders, process payments
            'waiter' => [
                'waiter:access',
                'orders:create',
                'orders:view',
                'orders:view-own',
                'orders:update',
                'tables:view',
                'tables:update',
                'payments:create',
                'payments:process',
                'tips:create',
                'menu:view',
                'guests:manage',
            ],

            // Chef: Can view kitchen orders and update prep status for kitchen items only
            'chef' => [
                'chef:access',
                'orders:view',
                'order-items:view',
                'order-items:update-kitchen',
                'menu:view',
            ],

            // Bartender: Can view bar orders and update prep status for bar items only
            'bartender' => [
                'bartender:access',
                'orders:view',
                'order-items:view',
                'order-items:update-bar',
                'menu:view',
            ],
        ];

        return $abilities[$role] ?? [];
    }
}
