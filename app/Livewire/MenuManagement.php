<?php

namespace App\Livewire;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MenuManagement extends Component
{
    // Category properties
    public $categoryId = null;

    public $categoryName = '';

    public $categoryDescription = '';

    public $categoryStatus = 'active';

    // Item properties
    public $itemId = null;

    public $itemName = '';

    public $itemDescription = '';

    public $itemCategoryId = '';

    public $itemPrice = '';

    public $itemPrepArea = 'kitchen';

    public $itemPrepTime = '';

    public $itemStatus = 'available';

    public $itemStock = '';

    // UI state
    public $showCategoryModal = false;

    public $showItemModal = false;

    public $editMode = false;

    protected $listeners = ['reorderCategories' => 'updateCategoryOrder'];

    /**
     * Validation rules for category form
     */
    protected function categoryRules()
    {
        return [
            'categoryName' => 'required|string|max:255',
            'categoryDescription' => 'nullable|string',
            'categoryStatus' => 'required|in:active,inactive',
        ];
    }

    /**
     * Validation rules for item form
     */
    protected function itemRules()
    {
        return [
            'itemName' => 'required|string|max:255',
            'itemDescription' => 'nullable|string',
            'itemCategoryId' => 'required|exists:menu_categories,id',
            'itemPrice' => 'required|numeric|min:0',
            'itemPrepArea' => 'required|in:kitchen,bar',
            'itemPrepTime' => 'nullable|integer|min:0',
            'itemStatus' => 'required|in:available,unavailable',
            'itemStock' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Open modal to add new category
     */
    public function addCategory()
    {
        $this->resetCategoryForm();
        $this->editMode = false;
        $this->showCategoryModal = true;
    }

    /**
     * Open modal to edit category
     */
    public function editCategory($id)
    {
        $category = MenuCategory::findOrFail($id);

        $this->categoryId = $category->id;
        $this->categoryName = $category->name;
        $this->categoryDescription = $category->description;
        $this->categoryStatus = $category->status;

        $this->editMode = true;
        $this->showCategoryModal = true;
    }

    /**
     * Save category (create or update)
     */
    public function saveCategory()
    {
        $this->validate($this->categoryRules());

        try {
            $data = [
                'name' => $this->categoryName,
                'description' => $this->categoryDescription,
                'status' => $this->categoryStatus,
            ];

            if ($this->categoryId) {
                // Update existing category
                $category = MenuCategory::findOrFail($this->categoryId);
                $category->update($data);
                session()->flash('success', 'Category updated successfully.');
            } else {
                // Create new category - set display order to last
                $maxOrder = MenuCategory::max('display_order') ?? 0;
                $data['display_order'] = $maxOrder + 1;

                MenuCategory::create($data);
                session()->flash('success', 'Category created successfully.');
            }

            $this->resetCategoryForm();
            $this->showCategoryModal = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save category: '.$e->getMessage());
        }
    }

    /**
     * Delete category
     */
    public function deleteCategory($id)
    {
        try {
            $category = MenuCategory::findOrFail($id);

            // Check if category has items
            if ($category->menuItems()->count() > 0) {
                session()->flash('error', 'Cannot delete category with existing menu items.');

                return;
            }

            $category->delete();
            session()->flash('success', 'Category deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete category: '.$e->getMessage());
        }
    }

    /**
     * Update category display order (for drag-drop)
     */
    public function updateCategoryOrder($orderedIds)
    {
        try {
            DB::transaction(function () use ($orderedIds) {
                foreach ($orderedIds as $index => $id) {
                    MenuCategory::where('id', $id)->update(['display_order' => $index + 1]);
                }
            });

            session()->flash('success', 'Category order updated successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update order: '.$e->getMessage());
        }
    }

    /**
     * Open modal to add new item
     */
    public function addItem()
    {
        $this->resetItemForm();
        $this->editMode = false;
        $this->showItemModal = true;
    }

    /**
     * Open modal to edit item
     */
    public function editItem($id)
    {
        $item = MenuItem::findOrFail($id);

        $this->itemId = $item->id;
        $this->itemName = $item->name;
        $this->itemDescription = $item->description;
        $this->itemCategoryId = $item->category_id;
        $this->itemPrice = $item->price;
        $this->itemPrepArea = $item->prep_area;
        $this->itemPrepTime = $item->prep_time_minutes;
        $this->itemStatus = $item->status;
        $this->itemStock = $item->stock_quantity;

        $this->editMode = true;
        $this->showItemModal = true;
    }

    /**
     * Save menu item (create or update)
     */
    public function saveItem()
    {
        $this->validate($this->itemRules());

        try {
            $data = [
                'name' => $this->itemName,
                'description' => $this->itemDescription,
                'category_id' => $this->itemCategoryId,
                'price' => $this->itemPrice,
                'prep_area' => $this->itemPrepArea,
                'prep_time_minutes' => $this->itemPrepTime,
                'status' => $this->itemStatus,
                'stock_quantity' => $this->itemStock,
            ];

            if ($this->itemId) {
                // Update existing item
                $item = MenuItem::findOrFail($this->itemId);
                $item->update($data);
                session()->flash('success', 'Menu item updated successfully.');
            } else {
                // Create new item
                MenuItem::create($data);
                session()->flash('success', 'Menu item created successfully.');
            }

            $this->resetItemForm();
            $this->showItemModal = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save menu item: '.$e->getMessage());
        }
    }

    /**
     * Delete menu item
     */
    public function deleteItem($id)
    {
        try {
            $item = MenuItem::findOrFail($id);
            $item->delete();
            session()->flash('success', 'Menu item deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete menu item: '.$e->getMessage());
        }
    }

    /**
     * Toggle item availability status
     */
    public function toggleItemStatus($id)
    {
        try {
            $item = MenuItem::findOrFail($id);
            $item->status = $item->status === 'available' ? 'unavailable' : 'available';
            $item->save();

            session()->flash('success', 'Item status updated successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update status: '.$e->getMessage());
        }
    }

    /**
     * Reset category form
     */
    private function resetCategoryForm()
    {
        $this->categoryId = null;
        $this->categoryName = '';
        $this->categoryDescription = '';
        $this->categoryStatus = 'active';
        $this->resetValidation();
    }

    /**
     * Reset item form
     */
    private function resetItemForm()
    {
        $this->itemId = null;
        $this->itemName = '';
        $this->itemDescription = '';
        $this->itemCategoryId = '';
        $this->itemPrice = '';
        $this->itemPrepArea = 'kitchen';
        $this->itemPrepTime = '';
        $this->itemStatus = 'available';
        $this->itemStock = '';
        $this->resetValidation();
    }

    /**
     * Close category modal
     */
    public function closeCategoryModal()
    {
        $this->showCategoryModal = false;
        $this->resetCategoryForm();
    }

    /**
     * Close item modal
     */
    public function closeItemModal()
    {
        $this->showItemModal = false;
        $this->resetItemForm();
    }

    /**
     * Render the component
     */
    public function render()
    {
        // Use select() to limit columns and eager loading for better performance
        $categories = MenuCategory::select('id', 'name', 'description', 'status', 'display_order')
            ->withCount('menuItems')
            ->get();
        $items = MenuItem::select('id', 'name', 'description', 'category_id', 'price', 'prep_area', 'prep_time_minutes', 'status', 'stock_quantity')
            ->with('menuCategory:id,name')
            ->get();

        return view('livewire.menu-management', [
            'categories' => $categories,
            'items' => $items,
        ])->layout('layouts.app-layout');
    }
}
