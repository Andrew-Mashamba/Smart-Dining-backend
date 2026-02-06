<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Component;

class SettingsManagement extends Component
{
    // Business Info
    public $business_name;

    public $business_address;

    public $business_phone;

    public $business_email;

    // Tax & Pricing
    public $tax_rate;

    // Operations
    public $opening_hours;

    public $closing_hours;

    public function mount()
    {
        // Load current settings
        $this->business_name = Setting::get('business_name', '');
        $this->business_address = Setting::get('business_address', '');
        $this->business_phone = Setting::get('business_phone', '');
        $this->business_email = Setting::get('business_email', '');
        $this->tax_rate = Setting::get('tax_rate', 0);
        $this->opening_hours = Setting::get('opening_hours', '09:00');
        $this->closing_hours = Setting::get('closing_hours', '22:00');
    }

    protected function rules()
    {
        return [
            'business_name' => 'required|string|max:255',
            'business_address' => 'required|string|max:500',
            'business_phone' => 'nullable|string|max:50',
            'business_email' => 'nullable|email|max:255',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'opening_hours' => 'nullable|string',
            'closing_hours' => 'nullable|string',
        ];
    }

    public function save()
    {
        $this->validate();

        // Save all settings
        Setting::set('business_name', $this->business_name);
        Setting::set('business_address', $this->business_address);
        Setting::set('business_phone', $this->business_phone);
        Setting::set('business_email', $this->business_email);
        Setting::set('tax_rate', $this->tax_rate, 'integer');
        Setting::set('opening_hours', $this->opening_hours);
        Setting::set('closing_hours', $this->closing_hours);

        session()->flash('message', 'Settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.settings-management')
            ->layout('layouts.app');
    }
}
