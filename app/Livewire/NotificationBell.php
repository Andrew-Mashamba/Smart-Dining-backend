<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public $showDropdown = false;

    public $unreadCount = 0;

    protected $listeners = ['notificationMarkedAsRead' => '$refresh'];

    /**
     * Mount the component
     */
    public function mount()
    {
        $this->updateUnreadCount();
    }

    /**
     * Toggle dropdown visibility
     */
    public function toggleDropdown()
    {
        $this->showDropdown = ! $this->showDropdown;

        if ($this->showDropdown) {
            $this->updateUnreadCount();
        }
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead($notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);

        if ($notification) {
            $notification->markAsRead();
            $this->updateUnreadCount();
            $this->dispatch('notificationMarkedAsRead');
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        $this->updateUnreadCount();
        $this->dispatch('notificationMarkedAsRead');
    }

    /**
     * Update unread count
     */
    public function updateUnreadCount()
    {
        $this->unreadCount = Auth::user()->unreadNotifications()->count();
    }

    /**
     * Render the component
     */
    public function render()
    {
        $notifications = Auth::user()
            ->notifications()
            ->whereNull('read_at')
            ->where('data->type', 'low_stock')
            ->latest()
            ->take(10)
            ->get();

        return view('livewire.notification-bell', [
            'notifications' => $notifications,
        ]);
    }
}
