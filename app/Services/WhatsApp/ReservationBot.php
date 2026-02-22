<?php

namespace App\Services\WhatsApp;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReservationBot
{
    protected WhatsAppService $whatsappService;

    protected ConversationManager $conversationManager;

    public function __construct(
        WhatsAppService $whatsappService,
        ConversationManager $conversationManager
    ) {
        $this->whatsappService = $whatsappService;
        $this->conversationManager = $conversationManager;
    }

    /**
     * Start the reservation flow.
     */
    public function start(Guest $guest): void
    {
        $this->whatsappService->sendTextMessage(
            $guest->phone_number,
            "Let's make a reservation!\n\nHow many guests will be dining? (Enter a number, e.g., 2)"
        );

        $this->conversationManager->setState($guest->phone_number, 'RESERVATION_GUESTS', [
            'reservation' => [],
        ]);
    }

    /**
     * Route to appropriate handler based on state.
     */
    public function handleState(Guest $guest, string $state, array $messageData): void
    {
        match ($state) {
            'RESERVATION_GUESTS' => $this->handleGuests($guest, $messageData),
            'RESERVATION_DATE' => $this->handleDate($guest, $messageData),
            'RESERVATION_TIME' => $this->handleTime($guest, $messageData),
            'RESERVATION_LOCATION' => $this->handleLocation($guest, $messageData),
            'RESERVATION_NAME' => $this->handleName($guest, $messageData),
            'RESERVATION_CONFIRM' => $this->handleConfirm($guest, $messageData),
            default => $this->start($guest),
        };
    }

    /**
     * Handle party size input.
     */
    protected function handleGuests(Guest $guest, array $messageData): void
    {
        $text = trim($messageData['text'] ?? '');

        if (! is_numeric($text) || (int) $text < 1 || (int) $text > 50) {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Please enter a valid number of guests (1-50)."
            );

            return;
        }

        $partySize = (int) $text;
        $this->conversationManager->updateSessionData($guest->phone_number, 'reservation', [
            'party_size' => $partySize,
        ]);

        $this->whatsappService->sendButtonMessage(
            $guest->phone_number,
            "Party of {$partySize}. When would you like to dine?",
            [
                ['id' => 'date_today', 'title' => 'Today'],
                ['id' => 'date_tomorrow', 'title' => 'Tomorrow'],
                ['id' => 'date_custom', 'title' => 'Other Date'],
            ]
        );

        $this->conversationManager->setState($guest->phone_number, 'RESERVATION_DATE');
    }

    /**
     * Handle date selection.
     */
    protected function handleDate(Guest $guest, array $messageData): void
    {
        $buttonId = $messageData['button_id'] ?? null;
        $text = trim($messageData['text'] ?? '');

        $date = null;

        if ($buttonId === 'date_today') {
            $date = today();
        } elseif ($buttonId === 'date_tomorrow') {
            $date = today()->addDay();
        } elseif ($buttonId === 'date_custom') {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Please enter your preferred date (e.g., Feb 25, 2026-02-25, or 25/02/2026)."
            );

            return;
        } else {
            // Try to parse date from text
            try {
                $date = Carbon::parse($text);
                if ($date->isPast() && ! $date->isToday()) {
                    $this->whatsappService->sendTextMessage(
                        $guest->phone_number,
                        "That date has already passed. Please choose a future date."
                    );

                    return;
                }
            } catch (\Exception $e) {
                $this->whatsappService->sendTextMessage(
                    $guest->phone_number,
                    "I couldn't understand that date. Please try a format like: Feb 25, 2026-02-25, or 25/02/2026."
                );

                return;
            }
        }

        $sessionData = $this->conversationManager->getSessionData($guest->phone_number);
        $reservation = $sessionData['reservation'] ?? [];
        $reservation['date'] = $date->format('Y-m-d');
        $this->conversationManager->updateSessionData($guest->phone_number, 'reservation', $reservation);

        // Send available time slots
        $timeSlots = $this->getAvailableTimeSlots($date->format('Y-m-d'));

        if (empty($timeSlots)) {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Sorry, no time slots are available for {$date->format('M d, Y')}. Please choose another date."
            );
            $this->conversationManager->setState($guest->phone_number, 'RESERVATION_DATE');

            return;
        }

        $sections = [
            [
                'title' => $date->format('M d, Y'),
                'rows' => $timeSlots,
            ],
        ];

        $this->whatsappService->sendListMessage(
            $guest->phone_number,
            "Available times for {$date->format('M d, Y')}.\nSelect a time slot:",
            'Select Time',
            $sections
        );

        $this->conversationManager->setState($guest->phone_number, 'RESERVATION_TIME');
    }

    /**
     * Handle time selection.
     */
    protected function handleTime(Guest $guest, array $messageData): void
    {
        $listId = $messageData['list_id'] ?? null;
        $text = trim($messageData['text'] ?? '');

        $time = null;

        if ($listId && str_starts_with($listId, 'time_')) {
            $time = str_replace('time_', '', $listId);
            $time = substr($time, 0, 2).':'.substr($time, 2, 2);
        } else {
            // Try to parse time from text
            try {
                $parsed = Carbon::parse($text);
                $time = $parsed->format('H:i');
            } catch (\Exception $e) {
                $this->whatsappService->sendTextMessage(
                    $guest->phone_number,
                    "I couldn't understand that time. Please select from the list or enter a time like: 7:00 PM, 19:00."
                );

                return;
            }
        }

        $sessionData = $this->conversationManager->getSessionData($guest->phone_number);
        $reservation = $sessionData['reservation'] ?? [];
        $reservation['time'] = $time;
        $this->conversationManager->updateSessionData($guest->phone_number, 'reservation', $reservation);

        $this->whatsappService->sendButtonMessage(
            $guest->phone_number,
            "Great! Where would you like to sit?",
            [
                ['id' => 'loc_indoor', 'title' => 'Indoor'],
                ['id' => 'loc_outdoor', 'title' => 'Outdoor'],
                ['id' => 'loc_bar', 'title' => 'Bar Area'],
            ]
        );

        $this->conversationManager->setState($guest->phone_number, 'RESERVATION_LOCATION');
    }

    /**
     * Handle location selection.
     */
    protected function handleLocation(Guest $guest, array $messageData): void
    {
        $buttonId = $messageData['button_id'] ?? null;
        $text = strtolower(trim($messageData['text'] ?? ''));

        $location = match ($buttonId ?? $text) {
            'loc_indoor', 'indoor' => 'indoor',
            'loc_outdoor', 'outdoor' => 'outdoor',
            'loc_bar', 'bar' => 'bar',
            default => null,
        };

        if (! $location) {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Please choose: Indoor, Outdoor, or Bar Area."
            );

            return;
        }

        $sessionData = $this->conversationManager->getSessionData($guest->phone_number);
        $reservation = $sessionData['reservation'] ?? [];
        $reservation['location'] = $location;
        $this->conversationManager->updateSessionData($guest->phone_number, 'reservation', $reservation);

        // If guest already has a name, skip to confirmation
        if ($guest->name) {
            $reservation['name'] = $guest->name;
            $reservation['phone'] = $guest->phone_number;
            $this->conversationManager->updateSessionData($guest->phone_number, 'reservation', $reservation);
            $this->showConfirmation($guest);
        } else {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Almost done! What name should we put the reservation under?"
            );
            $this->conversationManager->setState($guest->phone_number, 'RESERVATION_NAME');
        }
    }

    /**
     * Handle name input.
     */
    protected function handleName(Guest $guest, array $messageData): void
    {
        $text = trim($messageData['text'] ?? '');

        if (strlen($text) < 2) {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Please enter a valid name."
            );

            return;
        }

        // Update guest name
        $guest->update(['name' => $text]);

        $sessionData = $this->conversationManager->getSessionData($guest->phone_number);
        $reservation = $sessionData['reservation'] ?? [];
        $reservation['name'] = $text;
        $reservation['phone'] = $guest->phone_number;
        $this->conversationManager->updateSessionData($guest->phone_number, 'reservation', $reservation);

        $this->showConfirmation($guest);
    }

    /**
     * Show reservation summary and ask for confirmation.
     */
    protected function showConfirmation(Guest $guest): void
    {
        $sessionData = $this->conversationManager->getSessionData($guest->phone_number);
        $reservation = $sessionData['reservation'] ?? [];

        $date = Carbon::parse($reservation['date'])->format('M d, Y');
        $time = Carbon::parse($reservation['time'])->format('g:i A');

        $summary = "Reservation Summary:\n\n";
        $summary .= "Name: {$reservation['name']}\n";
        $summary .= "Guests: {$reservation['party_size']}\n";
        $summary .= "Date: {$date}\n";
        $summary .= "Time: {$time}\n";
        $summary .= "Location: ".ucfirst($reservation['location'])."\n";
        $summary .= "\nConfirm this reservation?";

        $this->whatsappService->sendButtonMessage(
            $guest->phone_number,
            $summary,
            [
                ['id' => 'res_confirm', 'title' => 'Confirm'],
                ['id' => 'res_cancel', 'title' => 'Cancel'],
            ]
        );

        $this->conversationManager->setState($guest->phone_number, 'RESERVATION_CONFIRM');
    }

    /**
     * Handle confirmation or cancellation.
     */
    protected function handleConfirm(Guest $guest, array $messageData): void
    {
        $buttonId = $messageData['button_id'] ?? null;
        $text = strtolower(trim($messageData['text'] ?? ''));

        $selection = $buttonId ?? $text;

        if ($selection === 'res_cancel' || $selection === 'cancel' || $selection === 'no') {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Reservation cancelled. Type 'menu' for the main menu."
            );
            $this->conversationManager->clearSession($guest->phone_number);

            return;
        }

        if ($selection !== 'res_confirm' && $selection !== 'confirm' && $selection !== 'yes') {
            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Please tap Confirm or Cancel."
            );

            return;
        }

        $sessionData = $this->conversationManager->getSessionData($guest->phone_number);
        $reservation = $sessionData['reservation'] ?? [];

        try {
            // Find a suitable table
            $table = $this->findAvailableTable(
                $reservation['date'],
                $reservation['time'],
                $reservation['party_size'],
                $reservation['location']
            );

            $newReservation = Reservation::create([
                'guest_id' => $guest->id,
                'table_id' => $table?->id,
                'reservation_date' => $reservation['date'],
                'reservation_time' => $reservation['time'],
                'party_size' => $reservation['party_size'],
                'location' => $reservation['location'],
                'status' => 'confirmed',
                'source' => 'whatsapp',
            ]);

            $date = Carbon::parse($reservation['date'])->format('M d, Y');
            $time = Carbon::parse($reservation['time'])->format('g:i A');
            $tableInfo = $table ? "Table: {$table->name}" : "Table: Will be assigned on arrival";

            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Reservation confirmed!\n\nRef: {$newReservation->reference_number}\nDate: {$date}\nTime: {$time}\nGuests: {$reservation['party_size']}\nLocation: ".ucfirst($reservation['location'])."\n{$tableInfo}\n\nSee you then! Type 'menu' for the main menu."
            );

            $this->conversationManager->clearSession($guest->phone_number);

            Log::info('WhatsApp reservation created', [
                'reference' => $newReservation->reference_number,
                'guest' => $guest->phone_number,
            ]);
        } catch (\Exception $e) {
            Log::error('Reservation creation failed', [
                'guest' => $guest->phone_number,
                'error' => $e->getMessage(),
            ]);

            $this->whatsappService->sendTextMessage(
                $guest->phone_number,
                "Sorry, we couldn't create your reservation. Please try again or call us directly."
            );
            $this->conversationManager->clearSession($guest->phone_number);
        }
    }

    /**
     * Get available time slots for a given date.
     */
    protected function getAvailableTimeSlots(string $date): array
    {
        $slots = [];
        $startHour = 11;
        $endHour = 22;

        // For today, only show future slots
        $now = now();
        if (Carbon::parse($date)->isToday()) {
            $startHour = max($startHour, $now->hour + 1);
        }

        for ($hour = $startHour; $hour <= $endHour; $hour++) {
            foreach (['00', '30'] as $minute) {
                $timeKey = str_pad($hour, 2, '0', STR_PAD_LEFT).$minute;
                $displayTime = Carbon::createFromTime($hour, (int) $minute)->format('g:i A');

                $slots[] = [
                    'id' => "time_{$timeKey}",
                    'title' => $displayTime,
                    'description' => "Reserve for {$displayTime}",
                ];

                // WhatsApp list max is 10 rows per section
                if (count($slots) >= 10) {
                    return $slots;
                }
            }
        }

        return $slots;
    }

    /**
     * Find an available table matching the criteria.
     */
    protected function findAvailableTable(string $date, string $time, int $partySize, string $location): ?Table
    {
        // Get tables that can fit the party at the requested location
        $tables = Table::where('location', $location)
            ->where('capacity', '>=', $partySize)
            ->where('status', 'available')
            ->orderBy('capacity')
            ->get();

        foreach ($tables as $table) {
            // Check if this table has no conflicting reservations (within 2 hours)
            $conflicting = Reservation::where('table_id', $table->id)
                ->where('reservation_date', $date)
                ->whereIn('status', ['pending', 'confirmed'])
                ->where(function ($query) use ($time) {
                    $start = Carbon::parse($time)->subHours(2)->format('H:i');
                    $end = Carbon::parse($time)->addHours(2)->format('H:i');
                    $query->whereBetween('reservation_time', [$start, $end]);
                })
                ->exists();

            if (! $conflicting) {
                return $table;
            }
        }

        return null;
    }
}
