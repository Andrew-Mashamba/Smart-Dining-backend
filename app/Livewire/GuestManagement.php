<?php

namespace App\Livewire;

use App\Models\Guest;
use App\Models\Order;
use Livewire\Component;

class GuestManagement extends Component
{
    // Search property
    public $search = '';

    // Add guest modal properties
    public $showAddGuestModal = false;

    public $guestPhoneNumber = '';

    public $guestName = '';

    // View guest details modal properties
    public $showGuestDetailsModal = false;

    public $selectedGuest = null;

    public $guestOrders = [];

    // Points adjustment modal properties
    public $showPointsModal = false;

    public $pointsAdjustment = '';

    public $pointsReason = '';

    /**
     * Validation rules for adding guest
     */
    protected function addGuestRules()
    {
        return [
            'guestPhoneNumber' => 'required|string|max:20|unique:guests,phone_number',
            'guestName' => 'nullable|string|max:255',
        ];
    }

    /**
     * Validation rules for points adjustment
     */
    protected function pointsRules()
    {
        return [
            'pointsAdjustment' => 'required|integer|not_in:0',
            'pointsReason' => 'required|string|max:255',
        ];
    }

    /**
     * Open modal to add new guest
     */
    public function addGuest()
    {
        $this->resetAddGuestForm();
        $this->showAddGuestModal = true;
    }

    /**
     * Save new guest
     */
    public function saveGuest()
    {
        $this->validate($this->addGuestRules());

        try {
            Guest::create([
                'phone_number' => $this->guestPhoneNumber,
                'name' => $this->guestName,
                'loyalty_points' => 0,
            ]);

            session()->flash('success', 'Guest added successfully.');
            $this->resetAddGuestForm();
            $this->showAddGuestModal = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add guest: '.$e->getMessage());
        }
    }

    /**
     * View guest details - show order history modal
     */
    public function viewGuest($guestId)
    {
        try {
            $this->selectedGuest = Guest::findOrFail($guestId);

            // Load order history with items count
            $this->guestOrders = Order::where('guest_id', $guestId)
                ->withCount('orderItems')
                ->with(['table'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'date' => $order->created_at->format('Y-m-d H:i'),
                        'total' => $order->total,
                        'items_count' => $order->order_items_count,
                        'status' => $order->status,
                        'table' => $order->table ? $order->table->table_number : 'N/A',
                    ];
                })
                ->toArray();

            $this->showGuestDetailsModal = true;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load guest details: '.$e->getMessage());
        }
    }

    /**
     * Open points adjustment modal
     */
    public function openPointsModal()
    {
        $this->resetPointsForm();
        $this->showPointsModal = true;
    }

    /**
     * Adjust guest loyalty points
     */
    public function adjustPoints()
    {
        $this->validate($this->pointsRules());

        try {
            if (! $this->selectedGuest) {
                throw new \Exception('No guest selected.');
            }

            $guest = Guest::findOrFail($this->selectedGuest->id);
            $newPoints = max(0, $guest->loyalty_points + $this->pointsAdjustment);

            $guest->update(['loyalty_points' => $newPoints]);

            // Refresh selected guest
            $this->selectedGuest = $guest;

            session()->flash('success', 'Loyalty points adjusted successfully.');
            $this->resetPointsForm();
            $this->showPointsModal = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to adjust points: '.$e->getMessage());
        }
    }

    /**
     * Reset add guest form
     */
    private function resetAddGuestForm()
    {
        $this->guestPhoneNumber = '';
        $this->guestName = '';
        $this->resetValidation();
    }

    /**
     * Reset points adjustment form
     */
    private function resetPointsForm()
    {
        $this->pointsAdjustment = '';
        $this->pointsReason = '';
        $this->resetValidation();
    }

    /**
     * Close add guest modal
     */
    public function closeAddGuestModal()
    {
        $this->showAddGuestModal = false;
        $this->resetAddGuestForm();
    }

    /**
     * Close guest details modal
     */
    public function closeGuestDetailsModal()
    {
        $this->showGuestDetailsModal = false;
        $this->selectedGuest = null;
        $this->guestOrders = [];
    }

    /**
     * Close points modal
     */
    public function closePointsModal()
    {
        $this->showPointsModal = false;
        $this->resetPointsForm();
    }

    /**
     * Render the component
     */
    public function render()
    {
        // Build query with search functionality
        $query = Guest::query()
            ->withCount('orders')
            ->with(['orders' => function ($query) {
                $query->latest()->limit(1);
            }]);

        // Apply search filter
        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('phone_number', 'like', '%'.$this->search.'%')
                    ->orWhere('name', 'like', '%'.$this->search.'%');
            });
        }

        $guests = $query->get()->map(function ($guest) {
            return [
                'id' => $guest->id,
                'phone_number' => $guest->phone_number,
                'name' => $guest->name ?? 'N/A',
                'loyalty_points' => $guest->loyalty_points,
                'total_orders' => $guest->orders_count,
                'last_order_date' => $guest->orders->first()
                    ? $guest->orders->first()->created_at->format('Y-m-d H:i')
                    : 'Never',
            ];
        });

        return view('livewire.guest-management', [
            'guests' => $guests,
        ])->layout('layouts.app-layout');
    }
}
