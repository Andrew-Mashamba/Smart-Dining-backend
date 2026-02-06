<div>
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold text-gray-900">Menu Management</h1>
            <x-help-tooltip text="Create categories to organize your menu, then add items to each category. Set prices, descriptions, and mark items as available or unavailable." position="right" />
        </div>
        <p class="text-gray-600 mt-1">Manage menu categories and items</p>
    </div>

    <!-- Action Buttons -->
    <div class="mb-6 flex gap-3">
        <button
            wire:click="addCategory"
            class="bg-white text-gray-900 border border-gray-300 px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors font-medium"
        >
            Add Category
        </button>
        <button
            wire:click="addItem"
            class="bg-white text-gray-900 border border-gray-300 px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors font-medium"
        >
            Add Menu Item
        </button>
    </div>

    <!-- Categories and Items Display -->
    <div class="space-y-6">
        @forelse($categories as $category)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <!-- Category Header -->
                <div class="px-6 py-4 border-b border-gray-200 {{ $category->status === 'inactive' ? 'bg-gray-50' : '' }}">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h2 class="text-lg font-bold text-gray-900">{{ $category->name }}</h2>
                                <span class="text-sm text-gray-600">({{ $category->menu_items_count }} items)</span>
                                @if($category->status === 'inactive')
                                    <span class="px-2 py-1 text-xs font-medium text-gray-600 bg-gray-200 rounded">Inactive</span>
                                @endif
                            </div>
                            @if($category->description)
                                <p class="text-sm text-gray-600 mt-1">{{ $category->description }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                wire:click="editCategory({{ $category->id }})"
                                class="bg-white text-gray-900 border border-gray-300 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium"
                            >
                                Edit
                            </button>
                            <button
                                wire:click="deleteCategory({{ $category->id }})"
                                wire:confirm="Are you sure you want to delete this category?"
                                class="bg-white text-gray-900 border border-gray-300 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Category Items -->
                <div class="divide-y divide-gray-200">
                    @php
                        $categoryItems = $items->where('category_id', $category->id);
                    @endphp

                    @forelse($categoryItems as $item)
                        <div class="px-6 py-4 {{ $item->status === 'unavailable' ? 'bg-gray-50' : '' }}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <h3 class="font-semibold text-gray-900">{{ $item->name }}</h3>
                                        <span class="text-sm font-bold text-gray-900">${{ number_format($item->price, 2) }}</span>
                                        @if($item->status === 'unavailable')
                                            <span class="px-2 py-1 text-xs font-medium text-gray-600 bg-gray-200 rounded">Unavailable</span>
                                        @endif
                                    </div>
                                    @if($item->description)
                                        <p class="text-sm text-gray-600 mt-1">{{ $item->description }}</p>
                                    @endif
                                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-600">
                                        <span>Prep Area: <span class="font-medium">{{ ucfirst($item->prep_area) }}</span></span>
                                        @if($item->prep_time_minutes)
                                            <span>Prep Time: <span class="font-medium">{{ $item->prep_time_minutes }} min</span></span>
                                        @endif
                                        @if($item->stock_quantity !== null)
                                            <span>Stock: <span class="font-medium">{{ $item->stock_quantity }}</span></span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 ml-4">
                                    <button
                                        wire:click="toggleItemStatus({{ $item->id }})"
                                        class="bg-white text-gray-900 border border-gray-300 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium"
                                    >
                                        {{ $item->status === 'available' ? 'Mark Unavailable' : 'Mark Available' }}
                                    </button>
                                    <button
                                        wire:click="editItem({{ $item->id }})"
                                        class="bg-white text-gray-900 border border-gray-300 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        wire:click="deleteItem({{ $item->id }})"
                                        wire:confirm="Are you sure you want to delete this item?"
                                        class="bg-white text-gray-900 border border-gray-300 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-gray-600">
                            No items in this category yet.
                        </div>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-6 py-12 text-center">
                <p class="text-gray-600">No categories created yet. Click "Add Category" to get started.</p>
            </div>
        @endforelse
    </div>

    <!-- Category Modal -->
    @if($showCategoryModal)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-lg max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">
                        {{ $editMode ? 'Edit Category' : 'Add Category' }}
                    </h3>
                </div>

                <div class="px-6 py-4 space-y-4">
                    <!-- Category Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-1">
                            Name <span class="text-red-600">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="categoryName"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                            placeholder="Enter category name"
                        >
                        @error('categoryName')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Category Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-1">Description</label>
                        <textarea
                            wire:model="categoryDescription"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                            placeholder="Enter category description (optional)"
                        ></textarea>
                        @error('categoryDescription')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Category Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-1">
                            Status <span class="text-red-600">*</span>
                        </label>
                        <select
                            wire:model="categoryStatus"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        @error('categoryStatus')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button
                        wire:click="closeCategoryModal"
                        class="bg-white text-gray-900 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="saveCategory"
                        class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors font-medium"
                    >
                        {{ $editMode ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Item Modal -->
    @if($showItemModal)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-200 sticky top-0 bg-white">
                    <h3 class="text-lg font-bold text-gray-900">
                        {{ $editMode ? 'Edit Menu Item' : 'Add Menu Item' }}
                    </h3>
                </div>

                <div class="px-6 py-4 space-y-4">
                    <!-- Item Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-1">
                            Name <span class="text-red-600">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="itemName"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                            placeholder="Enter item name"
                        >
                        @error('itemName')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Item Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-1">Description</label>
                        <textarea
                            wire:model="itemDescription"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                            placeholder="Enter item description (optional)"
                        ></textarea>
                        @error('itemDescription')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Two Column Layout -->
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-1">
                                Category <span class="text-red-600">*</span>
                            </label>
                            <select
                                wire:model="itemCategoryId"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                            >
                                <option value="">Select category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('itemCategoryId')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Price -->
                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-1">
                                Price <span class="text-red-600">*</span>
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                wire:model="itemPrice"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                                placeholder="0.00"
                            >
                            @error('itemPrice')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Prep Area -->
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <label class="block text-sm font-medium text-gray-900">
                                    Prep Area <span class="text-red-600">*</span>
                                </label>
                                <x-help-tooltip text="Kitchen items appear on Kitchen Display. Bar items appear on Bar Display. This determines which staff prepares the item." position="right" />
                            </div>
                            <select
                                wire:model="itemPrepArea"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                            >
                                <option value="kitchen">Kitchen</option>
                                <option value="bar">Bar</option>
                            </select>
                            @error('itemPrepArea')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Prep Time -->
                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-1">Prep Time (minutes)</label>
                            <input
                                type="number"
                                wire:model="itemPrepTime"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                                placeholder="0"
                            >
                            @error('itemPrepTime')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-1">
                                Status <span class="text-red-600">*</span>
                            </label>
                            <select
                                wire:model="itemStatus"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                            >
                                <option value="available">Available</option>
                                <option value="unavailable">Unavailable</option>
                            </select>
                            @error('itemStatus')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Stock -->
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <label class="block text-sm font-medium text-gray-900">Stock Quantity</label>
                                <x-help-tooltip text="Track inventory for this item. Leave blank for unlimited stock. Item becomes unavailable when stock reaches zero." position="right" />
                            </div>
                            <input
                                type="number"
                                wire:model="itemStock"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                                placeholder="0"
                            >
                            @error('itemStock')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3 sticky bottom-0 bg-white">
                    <button
                        wire:click="closeItemModal"
                        class="bg-white text-gray-900 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="saveItem"
                        class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors font-medium"
                    >
                        {{ $editMode ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
