<?php

namespace App\Livewire;

use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FeedbackManagement extends Component
{
    public $search = '';

    public $filterRating = '';

    public $filterStatus = '';

    // Detail + respond modal
    public $showDetailModal = false;

    public $selectedFeedback = null;

    public $staffResponse = '';

    protected function respondRules()
    {
        return [
            'staffResponse' => 'required|string|max:1000',
        ];
    }

    public function viewFeedback($id)
    {
        $this->selectedFeedback = Feedback::with(['guest', 'order', 'responder'])->findOrFail($id);
        $this->staffResponse = $this->selectedFeedback->staff_response ?? '';
        $this->showDetailModal = true;
    }

    public function respondToFeedback()
    {
        $this->validate($this->respondRules());

        try {
            $this->selectedFeedback->update([
                'staff_response' => $this->staffResponse,
                'responded_by' => Auth::user()->id,
                'responded_at' => now(),
            ]);

            $this->selectedFeedback = Feedback::with(['guest', 'order', 'responder'])
                ->find($this->selectedFeedback->id);

            session()->flash('success', 'Response saved successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save response: ' . $e->getMessage());
        }
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedFeedback = null;
        $this->staffResponse = '';
    }

    public function render()
    {
        $query = Feedback::with(['guest', 'order'])->latest();

        if ($this->search) {
            $query->whereHas('guest', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('phone_number', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterRating) {
            $query->where('rating', $this->filterRating);
        }

        if ($this->filterStatus === 'responded') {
            $query->whereNotNull('staff_response');
        } elseif ($this->filterStatus === 'unresponded') {
            $query->whereNull('staff_response');
        }

        $feedbackList = $query->get();

        // Stats
        $totalCount = Feedback::count();
        $avgRating = $totalCount > 0 ? round(Feedback::avg('rating'), 1) : 0;
        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = Feedback::where('rating', $i)->count();
            $distribution[$i] = [
                'count' => $count,
                'percent' => $totalCount > 0 ? round(($count / $totalCount) * 100) : 0,
            ];
        }
        $unrespondedCount = Feedback::unresponded()->count();

        $stats = [
            'total' => $totalCount,
            'avg_rating' => $avgRating,
            'distribution' => $distribution,
            'unresponded' => $unrespondedCount,
        ];

        return view('livewire.feedback-management', [
            'feedbackList' => $feedbackList,
            'stats' => $stats,
        ])->layout('layouts.app-layout');
    }
}
