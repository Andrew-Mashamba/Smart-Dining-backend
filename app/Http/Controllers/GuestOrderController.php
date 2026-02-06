<?php

namespace App\Http\Controllers;

use App\Models\GuestSession;
use Illuminate\Http\Request;

class GuestOrderController extends Controller
{
    /**
     * Display the guest ordering page.
     * This is accessed via QR code scan with a session token.
     */
    public function index(Request $request)
    {
        $token = $request->query('token');

        if (! $token) {
            return view('guest.order-error', [
                'error' => 'Invalid QR code. Please scan again or contact staff.',
            ]);
        }

        // Find the guest session by token
        $guestSession = GuestSession::where('session_token', $token)
            ->with('table')
            ->first();

        if (! $guestSession) {
            return view('guest.order-error', [
                'error' => 'Session not found. Please scan a valid QR code or contact staff.',
            ]);
        }

        // Check if session is still active
        if (! $guestSession->isActive()) {
            return view('guest.order-error', [
                'error' => 'This session has ended. Please request a new QR code from staff.',
            ]);
        }

        // Placeholder: Return guest ordering view
        // This will be implemented in a future story
        return view('guest.order', [
            'session' => $guestSession,
            'table' => $guestSession->table,
        ]);
    }
}
