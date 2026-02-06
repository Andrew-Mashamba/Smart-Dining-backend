<?php

namespace App\Livewire;

use App\Events\OrderCreated;
use App\Models\Guest;
use App\Models\GuestSession;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GuestOrder extends Component
{
    // Session management
    public $session_token = '';

    public $guestSession = null;

    public $sessionError = '';

    // Guest info
    public $phone_number = '';

    public $guest_name = '';

    // Cart management
    public $cart = [];

    public $selectedCategoryId = '';

    // Totals
    public $subtotal = 0;

    public $tax = 0;

    public $total = 0;

    public $taxRate = 0.18; // 18% VAT

    // UI state
    public $showCart = false;

    public $orderPlaced = false;

    public $orderNumber = '';

    /**
     * Mount the component with session token from query parameter
     */
    public function mount()
    {
        $this->session_token = request()->query('token', '');

        if (! $this->session_token) {
            $this->sessionError = 'Invalid QR code. Please scan again or contact staff.';

            return;
        }

        // Validate session token and check if session is active
        $this->guestSession = GuestSession::where('session_token', $this->session_token)
            ->with(['table', 'guest'])
            ->first();

        if (! $this->guestSession) {
            $this->sessionError = 'Session not found. Please scan a valid QR code or contact staff.';

            return;
        }

        if (! $this->guestSession->isActive()) {
            $this->sessionError = 'This session has ended. Please request a new QR code from staff.';

            return;
        }

        // Pre-fill guest info if guest exists
        if ($this->guestSession->guest) {
            $this->phone_number = $this->guestSession->guest->phone_number;
            $this->guest_name = $this->guestSession->guest->name ?? '';
        }
    }

    /**
     * Validation rules
     */
    protected function rules()
    {
        return [
            'phone_number' => 'required|string|min:10|max:20',
            'guest_name' => 'nullable|string|max:255',
            'cart' => 'required|array|min:1',
        ];
    }

    /**
     * Custom validation messages
     */
    protected function messages()
    {
        return [
            'phone_number.required' => 'Please enter your phone number.',
            'phone_number.min' => 'Phone number must be at least 10 characters.',
            'cart.required' => 'Please add at least one item to your cart.',
            'cart.min' => 'Please add at least one item to your cart.',
        ];
    }

    /**
     * Add item to cart
     */
    public function addItem($menuItemId)
    {
        $menuItem = MenuItem::findOrFail($menuItemId);

        // Check if item already exists in cart
        $existingIndex = $this->findCartItemIndex($menuItemId);

        if ($existingIndex !== null) {
            // Check stock availability before incrementing
            $newQuantity = $this->cart[$existingIndex]['quantity'] + 1;
            if ($menuItem->stock_quantity < $newQuantity) {
                session()->flash('error', "Cannot add more {$menuItem->name}. Only {$menuItem->stock_quantity} {$menuItem->unit} available.");

                return;
            }
            // Increment quantity if already in cart
            $this->cart[$existingIndex]['quantity']++;
        } else {
            // Check stock availability before adding to cart
            if ($menuItem->stock_quantity < 1) {
                session()->flash('error', "{$menuItem->name} is out of stock.");

                return;
            }
            // Add new item to cart
            $this->cart[] = [
                'menu_item_id' => $menuItem->id,
                'name' => $menuItem->name,
                'unit_price' => $menuItem->price,
                'quantity' => 1,
                'special_instructions' => '',
            ];
        }

        $this->calculateTotals();
        $this->showCart = true;
    }

    /**
     * Remove item from cart
     */
    public function removeItem($index)
    {
        if (isset($this->cart[$index])) {
            unset($this->cart[$index]);
            $this->cart = array_values($this->cart); // Re-index array
            $this->calculateTotals();
        }
    }

    /**
     * Update item quantity in cart
     */
    public function updateQuantity($index, $quantity)
    {
        if (isset($this->cart[$index])) {
            $quantity = max(1, intval($quantity)); // Ensure minimum quantity of 1

            // Check stock availability
            $menuItem = MenuItem::find($this->cart[$index]['menu_item_id']);
            if ($menuItem && $menuItem->stock_quantity < $quantity) {
                session()->flash('error', "Cannot add more {$menuItem->name}. Only {$menuItem->stock_quantity} {$menuItem->unit} available.");

                return;
            }

            $this->cart[$index]['quantity'] = $quantity;
            $this->calculateTotals();
        }
    }

    /**
     * Update special instructions for cart item
     */
    public function updateInstructions($index, $instructions)
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['special_instructions'] = $instructions;
        }
    }

    /**
     * Calculate order totals (subtotal, tax, total)
     */
    public function calculateTotals()
    {
        $this->subtotal = 0;

        foreach ($this->cart as $item) {
            $this->subtotal += $item['unit_price'] * $item['quantity'];
        }

        $this->tax = $this->subtotal * $this->taxRate;
        $this->total = $this->subtotal + $this->tax;
    }

    /**
     * Find cart item index by menu item ID
     */
    private function findCartItemIndex($menuItemId)
    {
        foreach ($this->cart as $index => $item) {
            if ($item['menu_item_id'] == $menuItemId) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Toggle cart visibility
     */
    public function toggleCart()
    {
        $this->showCart = ! $this->showCart;
    }

    /**
     * Place order - create Guest if needed, create Order and OrderItems
     */
    public function placeOrder()
    {
        // Validate
        $this->validate();

        try {
            DB::beginTransaction();

            // Validate stock availability for all items in cart
            foreach ($this->cart as $cartItem) {
                $menuItem = MenuItem::find($cartItem['menu_item_id']);

                if (! $menuItem) {
                    throw new \Exception('Menu item not found.');
                }

                if ($menuItem->stock_quantity < $cartItem['quantity']) {
                    throw new \Exception("Sorry, {$menuItem->name} is out of stock. Only {$menuItem->stock_quantity} {$menuItem->unit} available.");
                }
            }

            // Find or create guest
            $guest = Guest::firstOrCreate(
                ['phone_number' => $this->phone_number],
                ['name' => $this->guest_name]
            );

            // Update guest name if provided and different
            if ($this->guest_name && $guest->name !== $this->guest_name) {
                $guest->update(['name' => $this->guest_name]);
            }

            // Update guest session with guest_id if not set
            if (! $this->guestSession->guest_id) {
                $this->guestSession->update(['guest_id' => $guest->id]);
            }

            // Create order
            $order = Order::create([
                'table_id' => $this->guestSession->table_id,
                'guest_id' => $guest->id,
                'order_source' => 'web',
                'status' => 'pending',
                'subtotal' => $this->subtotal,
                'tax' => $this->tax,
                'total' => $this->total,
            ]);

            // Create order items
            foreach ($this->cart as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $cartItem['menu_item_id'],
                    'quantity' => $cartItem['quantity'],
                    'unit_price' => $cartItem['unit_price'],
                    'subtotal' => $cartItem['unit_price'] * $cartItem['quantity'],
                    'special_instructions' => $cartItem['special_instructions'],
                    'prep_status' => 'pending',
                ]);
            }

            // Broadcast OrderCreated event for real-time updates
            event(new OrderCreated($order->load(['table', 'orderItems'])));

            DB::commit();

            // Clear cart and show confirmation
            $this->cart = [];
            $this->calculateTotals();
            $this->orderPlaced = true;
            $this->orderNumber = $order->order_number;

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to place order: '.$e->getMessage());
        }
    }

    /**
     * Reset order confirmation and allow placing another order
     */
    public function orderAnother()
    {
        $this->orderPlaced = false;
        $this->orderNumber = '';
        $this->showCart = false;
    }

    /**
     * Render the component
     */
    public function render()
    {
        // If there's a session error, return error view
        if ($this->sessionError) {
            return view('livewire.guest-order-error')
                ->layout('layouts.guest');
        }

        // Get all active categories with available menu items
        $categories = MenuCategory::where('status', 'active')
            ->with(['menuItems' => function ($query) {
                $query->available();
            }])
            ->get()
            ->filter(function ($category) {
                return $category->menuItems->count() > 0;
            });

        // Get menu items for selected category or all if none selected
        $menuItems = MenuItem::with('menuCategory')
            ->available()
            ->when($this->selectedCategoryId, function ($query) {
                $query->where('category_id', $this->selectedCategoryId);
            })
            ->orderBy('name')
            ->get();

        return view('livewire.guest-order', [
            'categories' => $categories,
            'menuItems' => $menuItems,
        ])->layout('layouts.guest');
    }
}
