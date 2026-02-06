<?php

namespace App\Livewire;

use App\Models\InventoryTransaction;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class InventoryManagement extends Component
{
    // Modal state
    public $showRestockModal = false;

    public $showAdjustmentModal = false;

    public $activeTab = 'inventory'; // 'inventory' or 'history'

    // Restock form fields
    public $restockItemId = null;

    public $restockQuantity = '';

    public $restockUnit = '';

    public $restockNotes = '';

    // Adjustment form fields
    public $adjustmentItemId = null;

    public $adjustmentQuantity = '';

    public $adjustmentUnit = '';

    public $adjustmentType = 'waste'; // 'waste', 'loss', or 'correction'

    public $adjustmentNotes = '';

    // Filters for transaction history
    public $filterMenuItemId = '';

    public $filterTransactionType = '';

    public $filterDateFrom = '';

    public $filterDateTo = '';

    /**
     * Validation rules for restock form
     */
    protected function restockRules()
    {
        return [
            'restockQuantity' => 'required|integer|min:1',
            'restockUnit' => 'required|string|max:50',
            'restockNotes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Validation rules for adjustment form
     */
    protected function adjustmentRules()
    {
        return [
            'adjustmentQuantity' => 'required|integer|min:1',
            'adjustmentUnit' => 'required|string|max:50',
            'adjustmentType' => 'required|in:waste,loss,correction',
            'adjustmentNotes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Open restock modal for a specific menu item
     */
    public function openRestockModal($itemId)
    {
        $item = MenuItem::findOrFail($itemId);

        $this->restockItemId = $item->id;
        $this->restockQuantity = '';
        $this->restockUnit = 'units'; // Default unit
        $this->restockNotes = '';

        $this->showRestockModal = true;
        $this->resetValidation();
    }

    /**
     * Open adjustment modal for a specific menu item
     */
    public function openAdjustmentModal($itemId)
    {
        $item = MenuItem::findOrFail($itemId);

        $this->adjustmentItemId = $item->id;
        $this->adjustmentQuantity = '';
        $this->adjustmentUnit = 'units'; // Default unit
        $this->adjustmentType = 'waste';
        $this->adjustmentNotes = '';

        $this->showAdjustmentModal = true;
        $this->resetValidation();
    }

    /**
     * Process restock operation
     */
    public function processRestock()
    {
        $this->validate($this->restockRules());

        try {
            DB::beginTransaction();

            $menuItem = MenuItem::findOrFail($this->restockItemId);

            // Create inventory transaction
            InventoryTransaction::create([
                'menu_item_id' => $menuItem->id,
                'transaction_type' => 'restock',
                'quantity' => $this->restockQuantity,
                'unit' => $this->restockUnit,
                'notes' => $this->restockNotes,
                'created_by' => Auth::id(),
            ]);

            // Update menu item stock quantity
            $menuItem->stock_quantity = ($menuItem->stock_quantity ?? 0) + $this->restockQuantity;
            $menuItem->save();

            DB::commit();

            session()->flash('success', 'Stock restocked successfully.');
            $this->closeRestockModal();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to restock: '.$e->getMessage());
        }
    }

    /**
     * Process adjustment operation (waste, loss, or manual correction)
     */
    public function processAdjustment()
    {
        $this->validate($this->adjustmentRules());

        try {
            DB::beginTransaction();

            $menuItem = MenuItem::findOrFail($this->adjustmentItemId);

            // Create inventory transaction
            InventoryTransaction::create([
                'menu_item_id' => $menuItem->id,
                'transaction_type' => $this->adjustmentType,
                'quantity' => -$this->adjustmentQuantity, // Negative for reductions
                'unit' => $this->adjustmentUnit,
                'notes' => $this->adjustmentNotes,
                'created_by' => Auth::id(),
            ]);

            // Update menu item stock quantity
            $menuItem->stock_quantity = max(0, ($menuItem->stock_quantity ?? 0) - $this->adjustmentQuantity);
            $menuItem->save();

            DB::commit();

            session()->flash('success', 'Stock adjustment recorded successfully.');
            $this->closeAdjustmentModal();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to record adjustment: '.$e->getMessage());
        }
    }

    /**
     * Close restock modal
     */
    public function closeRestockModal()
    {
        $this->showRestockModal = false;
        $this->restockItemId = null;
        $this->restockQuantity = '';
        $this->restockUnit = '';
        $this->restockNotes = '';
        $this->resetValidation();
    }

    /**
     * Close adjustment modal
     */
    public function closeAdjustmentModal()
    {
        $this->showAdjustmentModal = false;
        $this->adjustmentItemId = null;
        $this->adjustmentQuantity = '';
        $this->adjustmentUnit = '';
        $this->adjustmentType = 'waste';
        $this->adjustmentNotes = '';
        $this->resetValidation();
    }

    /**
     * Switch between tabs
     */
    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    /**
     * Reset filters
     */
    public function resetFilters()
    {
        $this->filterMenuItemId = '';
        $this->filterTransactionType = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
    }

    /**
     * Check if item is low on stock
     */
    public function isLowStock($item)
    {
        return $item->stock_quantity < $item->low_stock_threshold;
    }

    /**
     * Render the component
     */
    public function render()
    {
        // Get all menu items with category relationship
        $menuItems = MenuItem::with('menuCategory')
            ->orderBy('name')
            ->get();

        // Build query for transaction history
        $transactionsQuery = InventoryTransaction::with(['menuItem.menuCategory', 'createdBy']);

        // Apply filters
        if ($this->filterMenuItemId) {
            $transactionsQuery->where('menu_item_id', $this->filterMenuItemId);
        }

        if ($this->filterTransactionType) {
            $transactionsQuery->where('transaction_type', $this->filterTransactionType);
        }

        if ($this->filterDateFrom) {
            $transactionsQuery->whereDate('created_at', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo) {
            $transactionsQuery->whereDate('created_at', '<=', $this->filterDateTo);
        }

        $transactions = $transactionsQuery->orderBy('created_at', 'desc')->get();

        return view('livewire.inventory-management', [
            'menuItems' => $menuItems,
            'transactions' => $transactions,
        ])->layout('layouts.app-layout');
    }
}
