<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Staff;
use App\Models\Table;
use Livewire\Component;
use Livewire\WithPagination;

class OrdersList extends Component
{
    use WithPagination;

    // Search and filter properties
    public $search = '';

    public $statusFilter = '';

    public $dateFrom = '';

    public $dateTo = '';

    public $waiterFilter = '';

    public $tableFilter = '';

    public $sourceFilter = '';

    public $perPage = 10;

    // Status workflow transitions
    protected $allowedTransitions = [
        'pending' => ['preparing', 'cancelled'],
        'preparing' => ['ready', 'cancelled'],
        'ready' => ['delivered'],
        'delivered' => ['paid'],
        'paid' => [],
        'cancelled' => [],
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    /**
     * Reset pagination when search or filters change
     */
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function updatingWaiterFilter()
    {
        $this->resetPage();
    }

    public function updatingTableFilter()
    {
        $this->resetPage();
    }

    public function updatingSourceFilter()
    {
        $this->resetPage();
    }

    /**
     * View order details
     */
    public function viewOrder($orderId)
    {
        return redirect()->route('orders.show', $orderId);
    }

    /**
     * Update order status with workflow validation
     */
    public function updateOrderStatus($orderId, $newStatus)
    {
        $order = Order::findOrFail($orderId);
        $currentStatus = $order->status;

        // Check if transition is allowed
        if (! isset($this->allowedTransitions[$currentStatus]) ||
            ! in_array($newStatus, $this->allowedTransitions[$currentStatus])) {
            session()->flash('error', 'Invalid status transition from '.$currentStatus.' to '.$newStatus);

            return;
        }

        // Update the status
        $order->updateStatus($newStatus);

        session()->flash('message', 'Order status updated to '.$newStatus);
    }

    /**
     * Get allowed status transitions for an order
     */
    public function getAllowedTransitions($currentStatus)
    {
        return $this->allowedTransitions[$currentStatus] ?? [];
    }

    /**
     * Reset all filters
     */
    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter', 'dateFrom', 'dateTo', 'waiterFilter', 'tableFilter', 'sourceFilter']);
        $this->resetPage();
    }

    /**
     * Render the component
     */
    public function render()
    {
        // Build query with filters and eager loading
        $query = Order::with(['orderItems.menuItem', 'table', 'waiter', 'guest'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('order_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('guest', function ($guestQuery) {
                            $guestQuery->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('created_at', '<=', $this->dateTo);
            })
            ->when($this->waiterFilter, function ($q) {
                $q->where('waiter_id', $this->waiterFilter);
            })
            ->when($this->tableFilter, function ($q) {
                $q->where('table_id', $this->tableFilter);
            })
            ->when($this->sourceFilter, function ($q) {
                $q->where('order_source', $this->sourceFilter);
            })
            ->orderBy('created_at', 'desc');

        $orders = $query->paginate($this->perPage);

        // Get filter options with limited columns
        $waiters = Staff::select('id', 'name')
            ->where('role', 'waiter')
            ->orderBy('name')
            ->get();
        $tables = Table::select('id', 'name')
            ->orderBy('name')
            ->get();
        $statuses = ['pending', 'preparing', 'ready', 'delivered', 'paid', 'cancelled'];
        $sources = ['qr_code', 'waiter', 'phone', 'online'];

        return view('livewire.orders-list', [
            'orders' => $orders,
            'waiters' => $waiters,
            'tables' => $tables,
            'statuses' => $statuses,
            'sources' => $sources,
        ])->layout('layouts.app-layout');
    }
}
