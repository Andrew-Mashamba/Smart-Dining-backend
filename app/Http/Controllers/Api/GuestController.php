<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    /**
     * Find guest by phone number
     */
    public function findByPhone($phone)
    {
        $guest = Guest::where('phone_number', $phone)->first();

        if (! $guest) {
            return response()->json([
                'message' => 'Guest not found',
            ], 404);
        }

        return response()->json($guest);
    }

    /**
     * Create a new guest
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string|unique:guests,phone_number',
            'name' => 'nullable|string',
            'preferences' => 'nullable|array',
        ]);

        $validated['first_visit_at'] = now();

        $guest = Guest::create($validated);

        return response()->json([
            'message' => 'Guest created successfully',
            'guest' => $guest,
        ], 201);
    }
}
