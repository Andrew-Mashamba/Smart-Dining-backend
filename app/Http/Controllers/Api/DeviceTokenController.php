<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Register or update an FCM device token for the authenticated staff member.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_token' => 'required|string|max:512',
            'device_name' => 'nullable|string|max:255',
        ]);

        $staff = $request->user();

        $deviceToken = DeviceToken::updateOrCreate(
            ['fcm_token' => $validated['fcm_token']],
            [
                'staff_id' => $staff->id,
                'device_name' => $validated['device_name'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $deviceToken,
            'message' => 'Device token registered successfully',
        ]);
    }

    /**
     * Remove an FCM device token (e.g., on logout).
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_token' => 'required|string|max:512',
        ]);

        $deleted = DeviceToken::where('fcm_token', $validated['fcm_token'])
            ->where('staff_id', $request->user()->id)
            ->delete();

        return response()->json([
            'success' => true,
            'data' => ['removed' => $deleted > 0],
            'message' => $deleted > 0
                ? 'Device token removed successfully'
                : 'Device token not found',
        ]);
    }
}
