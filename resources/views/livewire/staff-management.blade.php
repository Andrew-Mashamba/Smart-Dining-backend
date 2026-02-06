<div>
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold text-gray-900">Staff Management</h1>
            <x-help-tooltip text="Add and manage staff members. Assign roles (admin, manager, waiter, chef, bartender) to control access permissions. You can activate/deactivate staff accounts and edit their details." position="right" />
        </div>
        <p class="text-gray-600 mt-1">Manage employees with role assignment and status controls</p>
    </div>

    <!-- Success/Error Messages -->
    @if (session()->has('success'))
        <div class="mb-4 bg-white border border-gray-300 text-gray-900 px-4 py-3 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 bg-white border border-gray-300 text-gray-900 px-4 py-3 rounded-xl">
            {{ session('error') }}
        </div>
    @endif

    <!-- Search and Add Staff -->
    <div class="mb-6 flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
            <input
                type="text"
                wire:model.live="search"
                placeholder="Search by name, email, role, or phone..."
                class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-gray-400 text-gray-900"
            />
        </div>
        <button
            wire:click="addStaff"
            class="bg-white text-gray-900 border border-gray-300 px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors font-medium whitespace-nowrap"
        >
            Add Staff
        </button>
    </div>

    <!-- Staff Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                            Name
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                            Email
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                            Role
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                            Phone Number
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                            Created At
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($staff as $member)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $member->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $member->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded
                                    {{ $member->role === 'admin' ? 'bg-gray-800 text-white' : '' }}
                                    {{ $member->role === 'manager' ? 'bg-gray-600 text-white' : '' }}
                                    {{ $member->role === 'chef' ? 'bg-gray-500 text-white' : '' }}
                                    {{ $member->role === 'bartender' ? 'bg-gray-400 text-white' : '' }}
                                    {{ $member->role === 'waiter' ? 'bg-gray-300 text-gray-900' : '' }}
                                ">
                                    {{ ucfirst($member->role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $member->phone_number ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    wire:click="toggleStatus({{ $member->id }})"
                                    class="px-2 py-1 text-xs font-medium rounded cursor-pointer hover:opacity-80 transition-opacity
                                        {{ $member->status === 'active' ? 'bg-gray-700 text-white' : 'bg-gray-200 text-gray-600' }}
                                    "
                                >
                                    {{ ucfirst($member->status) }}
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600">{{ $member->created_at->format('Y-m-d H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <button
                                        wire:click="editStaff({{ $member->id }})"
                                        class="text-gray-600 hover:text-gray-900 transition-colors"
                                        title="Edit"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button
                                        wire:click="confirmDelete({{ $member->id }})"
                                        class="text-gray-600 hover:text-gray-900 transition-colors"
                                        title="Delete"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-600">
                                No staff members found. {{ $search ? 'Try a different search term.' : 'Add your first staff member to get started.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Staff Modal -->
    @if($showAddStaffModal)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">Add New Staff</h3>
                        <button
                            wire:click="closeAddStaffModal"
                            class="text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="staffName"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-400 text-gray-900"
                            placeholder="Enter staff name"
                        />
                        @error('staffName')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="email"
                            wire:model="staffEmail"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-400 text-gray-900"
                            placeholder="Enter email address"
                        />
                        @error('staffEmail')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select
                            wire:model="staffRole"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-400 text-gray-900"
                        >
                            <option value="">Select a role</option>
                            <option value="waiter">Waiter</option>
                            <option value="chef">Chef</option>
                            <option value="bartender">Bartender</option>
                            <option value="manager">Manager</option>
                            @if(auth()->user()->role === 'admin')
                                <option value="admin">Admin</option>
                            @endif
                        </select>
                        @error('staffRole')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">
                            Phone Number
                        </label>
                        <input
                            type="text"
                            wire:model="staffPhoneNumber"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-400 text-gray-900"
                            placeholder="Enter phone number"
                        />
                        @error('staffPhoneNumber')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="password"
                            wire:model="staffPassword"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-400 text-gray-900"
                            placeholder="Enter password (min 8 characters)"
                        />
                        @error('staffPassword')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button
                        wire:click="closeAddStaffModal"
                        class="bg-white text-gray-900 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="saveStaff"
                        class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors font-medium"
                    >
                        Add Staff
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Staff Modal -->
    @if($showEditStaffModal)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">Edit Staff</h3>
                        <button
                            wire:click="closeEditStaffModal"
                            class="text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="editStaffName"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-400 text-gray-900"
                            placeholder="Enter staff name"
                        />
                        @error('editStaffName')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="email"
                            wire:model="editStaffEmail"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-400 text-gray-900"
                            placeholder="Enter email address"
                        />
                        @error('editStaffEmail')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select
                            wire:model="editStaffRole"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-400 text-gray-900"
                        >
                            <option value="">Select a role</option>
                            <option value="waiter">Waiter</option>
                            <option value="chef">Chef</option>
                            <option value="bartender">Bartender</option>
                            <option value="manager">Manager</option>
                            @if(auth()->user()->role === 'admin')
                                <option value="admin">Admin</option>
                            @endif
                        </select>
                        @error('editStaffRole')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">
                            Phone Number
                        </label>
                        <input
                            type="text"
                            wire:model="editStaffPhoneNumber"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-400 text-gray-900"
                            placeholder="Enter phone number"
                        />
                        @error('editStaffPhoneNumber')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">
                            Password <span class="text-gray-400">(leave blank to keep current)</span>
                        </label>
                        <input
                            type="password"
                            wire:model="editStaffPassword"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-400 text-gray-900"
                            placeholder="Enter new password (optional)"
                        />
                        @error('editStaffPassword')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button
                        wire:click="closeEditStaffModal"
                        class="bg-white text-gray-900 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="updateStaff"
                        class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors font-medium"
                    >
                        Update Staff
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">Confirm Delete</h3>
                        <button
                            wire:click="closeDeleteModal"
                            class="text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4">
                    <p class="text-gray-900">Are you sure you want to deactivate this staff member? This action will set their status to inactive.</p>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button
                        wire:click="closeDeleteModal"
                        class="bg-white text-gray-900 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="deleteStaff"
                        class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors font-medium"
                    >
                        Confirm Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
