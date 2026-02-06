<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StaffManagement extends Component
{
    // Search property
    public $search = '';

    // Add staff modal properties
    public $showAddStaffModal = false;

    public $staffName = '';

    public $staffEmail = '';

    public $staffRole = '';

    public $staffPhoneNumber = '';

    public $staffPassword = '';

    // Edit staff modal properties
    public $showEditStaffModal = false;

    public $editStaffId = null;

    public $editStaffName = '';

    public $editStaffEmail = '';

    public $editStaffRole = '';

    public $editStaffPhoneNumber = '';

    public $editStaffPassword = '';

    // Delete confirmation modal properties
    public $showDeleteModal = false;

    public $deleteStaffId = null;

    /**
     * Get available roles based on current user role
     */
    public function getAvailableRoles()
    {
        $roles = ['waiter', 'chef', 'bartender', 'manager'];

        // Only admins can assign admin role
        if (auth()->user()->role === 'admin') {
            $roles[] = 'admin';
        }

        return $roles;
    }

    /**
     * Validation rules for adding staff
     */
    protected function addStaffRules()
    {
        return [
            'staffName' => 'required|string|max:255',
            'staffEmail' => 'required|email|max:255|unique:users,email',
            'staffRole' => ['required', Rule::in($this->getAvailableRoles())],
            'staffPhoneNumber' => 'nullable|string|max:20',
            'staffPassword' => 'required|string|min:8',
        ];
    }

    /**
     * Validation rules for editing staff
     */
    protected function editStaffRules()
    {
        return [
            'editStaffName' => 'required|string|max:255',
            'editStaffEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editStaffId)],
            'editStaffRole' => ['required', Rule::in($this->getAvailableRoles())],
            'editStaffPhoneNumber' => 'nullable|string|max:20',
            'editStaffPassword' => 'nullable|string|min:8',
        ];
    }

    /**
     * Open modal to add new staff
     */
    public function addStaff()
    {
        $this->resetAddStaffForm();
        $this->showAddStaffModal = true;
    }

    /**
     * Save new staff member
     */
    public function saveStaff()
    {
        $this->validate($this->addStaffRules());

        try {
            User::create([
                'name' => $this->staffName,
                'email' => $this->staffEmail,
                'role' => $this->staffRole,
                'phone_number' => $this->staffPhoneNumber,
                'password' => Hash::make($this->staffPassword),
                'status' => 'active',
            ]);

            session()->flash('success', 'Staff member added successfully.');
            $this->resetAddStaffForm();
            $this->showAddStaffModal = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add staff member: '.$e->getMessage());
        }
    }

    /**
     * Open edit staff modal
     */
    public function editStaff($staffId)
    {
        try {
            $staff = User::findOrFail($staffId);

            $this->editStaffId = $staff->id;
            $this->editStaffName = $staff->name;
            $this->editStaffEmail = $staff->email;
            $this->editStaffRole = $staff->role;
            $this->editStaffPhoneNumber = $staff->phone_number;
            $this->editStaffPassword = '';

            $this->showEditStaffModal = true;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load staff details: '.$e->getMessage());
        }
    }

    /**
     * Update staff member
     */
    public function updateStaff()
    {
        $this->validate($this->editStaffRules());

        try {
            $staff = User::findOrFail($this->editStaffId);

            $updateData = [
                'name' => $this->editStaffName,
                'email' => $this->editStaffEmail,
                'role' => $this->editStaffRole,
                'phone_number' => $this->editStaffPhoneNumber,
            ];

            // Only update password if provided
            if (! empty($this->editStaffPassword)) {
                $updateData['password'] = Hash::make($this->editStaffPassword);
            }

            $staff->update($updateData);

            session()->flash('success', 'Staff member updated successfully.');
            $this->resetEditStaffForm();
            $this->showEditStaffModal = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update staff member: '.$e->getMessage());
        }
    }

    /**
     * Toggle staff status between active and inactive
     */
    public function toggleStatus($staffId)
    {
        try {
            $staff = User::findOrFail($staffId);
            $newStatus = $staff->status === 'active' ? 'inactive' : 'active';

            $staff->update(['status' => $newStatus]);

            session()->flash('success', 'Staff status updated successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update status: '.$e->getMessage());
        }
    }

    /**
     * Open delete confirmation modal
     */
    public function confirmDelete($staffId)
    {
        $this->deleteStaffId = $staffId;
        $this->showDeleteModal = true;
    }

    /**
     * Delete staff member (set status to inactive as soft delete)
     */
    public function deleteStaff()
    {
        try {
            $staff = User::findOrFail($this->deleteStaffId);

            // Set status to inactive instead of hard delete
            $staff->update(['status' => 'inactive']);

            session()->flash('success', 'Staff member deactivated successfully.');
            $this->showDeleteModal = false;
            $this->deleteStaffId = null;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete staff member: '.$e->getMessage());
        }
    }

    /**
     * Reset add staff form
     */
    private function resetAddStaffForm()
    {
        $this->staffName = '';
        $this->staffEmail = '';
        $this->staffRole = '';
        $this->staffPhoneNumber = '';
        $this->staffPassword = '';
        $this->resetValidation();
    }

    /**
     * Reset edit staff form
     */
    private function resetEditStaffForm()
    {
        $this->editStaffId = null;
        $this->editStaffName = '';
        $this->editStaffEmail = '';
        $this->editStaffRole = '';
        $this->editStaffPhoneNumber = '';
        $this->editStaffPassword = '';
        $this->resetValidation();
    }

    /**
     * Close add staff modal
     */
    public function closeAddStaffModal()
    {
        $this->showAddStaffModal = false;
        $this->resetAddStaffForm();
    }

    /**
     * Close edit staff modal
     */
    public function closeEditStaffModal()
    {
        $this->showEditStaffModal = false;
        $this->resetEditStaffForm();
    }

    /**
     * Close delete modal
     */
    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deleteStaffId = null;
    }

    /**
     * Render the component
     */
    public function render()
    {
        // Build query with search functionality
        $query = User::query();

        // Apply search filter
        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('role', 'like', '%'.$this->search.'%')
                    ->orWhere('phone_number', 'like', '%'.$this->search.'%');
            });
        }

        $staff = $query->orderBy('created_at', 'desc')->get();

        return view('livewire.staff-management', [
            'staff' => $staff,
        ])->layout('layouts.app-layout');
    }
}
