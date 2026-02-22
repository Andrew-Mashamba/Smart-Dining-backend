<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    /**
     * List reservations with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Reservation::with(['guest', 'table']);

        if ($request->has('date')) {
            $query->whereDate('reservation_date', $request->date);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('guest_id')) {
            $query->where('guest_id', $request->guest_id);
        }

        if ($request->boolean('upcoming', false)) {
            $query->upcoming();
        }

        $reservations = $query->orderBy('reservation_date')
            ->orderBy('reservation_time')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => ReservationResource::collection($reservations),
            'message' => 'Reservations retrieved successfully',
            'meta' => [
                'current_page' => $reservations->currentPage(),
                'last_page' => $reservations->lastPage(),
                'total' => $reservations->total(),
            ],
        ]);
    }

    /**
     * Create a new reservation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'guest_id' => 'required|exists:guests,id',
            'table_id' => 'nullable|exists:tables,id',
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_time' => 'required|date_format:H:i',
            'party_size' => 'required|integer|min:1|max:50',
            'location' => 'nullable|string|in:indoor,outdoor,bar',
            'special_requests' => 'nullable|string|max:500',
            'source' => 'nullable|string|in:whatsapp,phone,walk_in,web',
        ]);

        $reservation = Reservation::create($validated);
        $reservation->load(['guest', 'table']);

        return response()->json([
            'success' => true,
            'data' => new ReservationResource($reservation),
            'message' => 'Reservation created successfully',
        ], 201);
    }

    /**
     * Show a single reservation.
     */
    public function show(int $id): JsonResponse
    {
        $reservation = Reservation::with(['guest', 'table'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ReservationResource($reservation),
            'message' => 'Reservation retrieved successfully',
        ]);
    }

    /**
     * Update reservation status.
     */
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $reservation = Reservation::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:pending,confirmed,cancelled,completed,no_show',
        ]);

        $reservation->update(['status' => $validated['status']]);
        $reservation->load(['guest', 'table']);

        return response()->json([
            'success' => true,
            'data' => new ReservationResource($reservation),
            'message' => 'Reservation status updated to '.$validated['status'],
        ]);
    }

    /**
     * Get available time slots for a given date.
     */
    public function availableSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'party_size' => 'nullable|integer|min:1',
            'location' => 'nullable|string|in:indoor,outdoor,bar',
        ]);

        $date = $validated['date'];
        $partySize = $validated['party_size'] ?? 2;
        $location = $validated['location'] ?? null;

        $slots = [];
        $startHour = 11;
        $endHour = 22;

        if (Carbon::parse($date)->isToday()) {
            $startHour = max($startHour, now()->hour + 1);
        }

        for ($hour = $startHour; $hour <= $endHour; $hour++) {
            foreach (['00', '30'] as $minute) {
                $time = str_pad($hour, 2, '0', STR_PAD_LEFT).':'.$minute;

                // Check table availability
                $query = Table::where('capacity', '>=', $partySize)
                    ->where('status', 'available');

                if ($location) {
                    $query->where('location', $location);
                }

                $availableTables = $query->whereDoesntHave('reservations', function ($q) use ($date, $time) {
                    $start = Carbon::parse($time)->subHours(2)->format('H:i');
                    $end = Carbon::parse($time)->addHours(2)->format('H:i');
                    $q->where('reservation_date', $date)
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->whereBetween('reservation_time', [$start, $end]);
                })->count();

                if ($availableTables > 0) {
                    $slots[] = [
                        'time' => $time,
                        'display_time' => Carbon::createFromTime($hour, (int) $minute)->format('g:i A'),
                        'available_tables' => $availableTables,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $slots,
            'message' => 'Available slots retrieved',
        ]);
    }
}
