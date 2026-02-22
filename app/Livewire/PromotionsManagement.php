<?php

namespace App\Livewire;

use App\Models\Promotion;
use Livewire\Component;

class PromotionsManagement extends Component
{
    public $search = '';

    public $filterType = '';

    public $filterStatus = '';

    // Modal state
    public $showModal = false;

    public $editMode = false;

    // Form fields
    public $promotionId = null;

    public $title = '';

    public $description = '';

    public $type = 'general';

    public $discount_type = '';

    public $discount_value = '';

    public $day_of_week = '';

    public $start_time = '';

    public $end_time = '';

    public $start_date = '';

    public $end_date = '';

    public $is_active = true;

    // Delete modal
    public $showDeleteModal = false;

    public $deleteId = null;

    protected function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:daily_special,happy_hour,discount,combo,general',
            'discount_type' => 'nullable|in:percentage,fixed_amount',
            'discount_value' => 'nullable|numeric|min:0',
            'day_of_week' => 'nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ];
    }

    public function addPromotion()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function editPromotion($id)
    {
        $promo = Promotion::findOrFail($id);
        $this->promotionId = $promo->id;
        $this->title = $promo->title;
        $this->description = $promo->description ?? '';
        $this->type = $promo->type;
        $this->discount_type = $promo->discount_type ?? '';
        $this->discount_value = $promo->discount_value ?? '';
        $this->day_of_week = $promo->day_of_week ?? '';
        $this->start_time = $promo->start_time ? substr($promo->start_time, 0, 5) : '';
        $this->end_time = $promo->end_time ? substr($promo->end_time, 0, 5) : '';
        $this->start_date = $promo->start_date ? $promo->start_date->format('Y-m-d') : '';
        $this->end_date = $promo->end_date ? $promo->end_date->format('Y-m-d') : '';
        $this->is_active = $promo->is_active;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function savePromotion()
    {
        $this->validate();

        try {
            $data = [
                'title' => $this->title,
                'description' => $this->description ?: null,
                'type' => $this->type,
                'discount_type' => $this->discount_type ?: null,
                'discount_value' => $this->discount_value ?: null,
                'day_of_week' => $this->day_of_week ?: null,
                'start_time' => $this->start_time ?: null,
                'end_time' => $this->end_time ?: null,
                'start_date' => $this->start_date ?: null,
                'end_date' => $this->end_date ?: null,
                'is_active' => $this->is_active,
            ];

            if ($this->editMode && $this->promotionId) {
                Promotion::findOrFail($this->promotionId)->update($data);
                session()->flash('success', 'Promotion updated successfully.');
            } else {
                Promotion::create($data);
                session()->flash('success', 'Promotion created successfully.');
            }

            $this->closeModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save promotion: ' . $e->getMessage());
        }
    }

    public function toggleActive($id)
    {
        $promo = Promotion::findOrFail($id);
        $promo->update(['is_active' => ! $promo->is_active]);
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function deletePromotion()
    {
        try {
            Promotion::findOrFail($this->deleteId)->delete();
            session()->flash('success', 'Promotion deleted.');
            $this->showDeleteModal = false;
            $this->deleteId = null;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete: ' . $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->promotionId = null;
        $this->title = '';
        $this->description = '';
        $this->type = 'general';
        $this->discount_type = '';
        $this->discount_value = '';
        $this->day_of_week = '';
        $this->start_time = '';
        $this->end_time = '';
        $this->start_date = '';
        $this->end_date = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        $query = Promotion::query()->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->filterStatus === 'active') {
            $query->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', today());
                });
        } elseif ($this->filterStatus === 'inactive') {
            $query->where('is_active', false);
        } elseif ($this->filterStatus === 'expired') {
            $query->where('end_date', '<', today());
        }

        $promotions = $query->get();

        $stats = [
            'total' => Promotion::count(),
            'active' => Promotion::active()->count(),
            'happy_hours' => Promotion::where('type', 'happy_hour')->where('is_active', true)->count(),
        ];

        return view('livewire.promotions-management', [
            'promotions' => $promotions,
            'stats' => $stats,
        ])->layout('layouts.app-layout');
    }
}
