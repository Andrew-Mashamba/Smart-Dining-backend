<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Component;

class SettingsManagement extends Component
{
    // Active tab
    public string $activeTab = 'general';

    // ── General / Business Info ──
    public $business_name;
    public $business_address;
    public $business_phone;
    public $business_email;
    public $tax_rate;
    public $opening_hours;
    public $closing_hours;

    // ── WhatsApp Configuration ──
    public $whatsapp_api_url;
    public $whatsapp_access_token;
    public $whatsapp_phone_number_id;
    public $whatsapp_business_account_id;
    public $whatsapp_webhook_secret;
    public $whatsapp_session_timeout;
    public bool $whatsapp_ai_enabled = false;

    // ── MNO Payment Configuration ──
    public $vodacom_enabled = false;
    public $vodacom_lipa_namba;
    public $vodacom_qr_code;

    public $yas_enabled = false;
    public $yas_lipa_namba;
    public $yas_qr_code;

    public $airtel_enabled = false;
    public $airtel_lipa_namba;
    public $airtel_qr_code;

    // ── Bank Payment Configuration ──
    public $bank_enabled = false;
    public $bank_name;
    public $bank_account_name;
    public $bank_account_number;
    public $bank_lipa_namba;
    public $bank_qr_code;

    // ── Card Processor (VISA / MasterCard) ──
    public $card_enabled = false;
    public $card_processor_name;
    public $card_merchant_id;
    public $card_api_key;
    public $card_api_secret;

    // ── Zima PayByLink Configuration ──
    public $zima_enabled = false;
    public $zima_api_key;
    public $zima_merchant_id;

    // ── Firebase / FCM Configuration ──
    public $firebase_project_id;
    public $firebase_credentials_json;

    /**
     * Get auto-generated WhatsApp webhook callback URL.
     */
    public function getWhatsappCallbackUrlProperty(): string
    {
        return rtrim(config('app.url'), '/').'/api/webhooks/whatsapp';
    }

    /**
     * Get auto-generated Zima PayByLink callback URL.
     */
    public function getZimaCallbackUrlProperty(): string
    {
        return rtrim(config('app.url'), '/').'/api/webhooks/zima';
    }

    public function mount()
    {
        // General
        $this->business_name = Setting::get('business_name', '');
        $this->business_address = Setting::get('business_address', '');
        $this->business_phone = Setting::get('business_phone', '');
        $this->business_email = Setting::get('business_email', '');
        $this->tax_rate = Setting::get('tax_rate', 0);
        $this->opening_hours = Setting::get('opening_hours', '09:00');
        $this->closing_hours = Setting::get('closing_hours', '22:00');

        // WhatsApp
        $this->whatsapp_api_url = Setting::get('whatsapp_api_url', config('whatsapp.api_url', 'https://graph.facebook.com/v18.0'));
        $this->whatsapp_access_token = Setting::get('whatsapp_access_token', config('whatsapp.access_token', ''));
        $this->whatsapp_phone_number_id = Setting::get('whatsapp_phone_number_id', config('whatsapp.phone_number_id', ''));
        $this->whatsapp_business_account_id = Setting::get('whatsapp_business_account_id', config('whatsapp.business_account_id', ''));
        $this->whatsapp_webhook_secret = Setting::get('whatsapp_webhook_secret', config('whatsapp.webhook_secret', ''));
        $this->whatsapp_session_timeout = Setting::get('whatsapp_session_timeout', config('whatsapp.session_timeout', 3600));
        $this->whatsapp_ai_enabled = (bool) Setting::get('whatsapp_ai_enabled', false);

        // MNO — VodaCom
        $this->vodacom_enabled = (bool) Setting::get('vodacom_enabled', false);
        $this->vodacom_lipa_namba = Setting::get('vodacom_lipa_namba', '');
        $this->vodacom_qr_code = Setting::get('vodacom_qr_code', '');

        // MNO — Yas
        $this->yas_enabled = (bool) Setting::get('yas_enabled', false);
        $this->yas_lipa_namba = Setting::get('yas_lipa_namba', '');
        $this->yas_qr_code = Setting::get('yas_qr_code', '');

        // MNO — AirTel
        $this->airtel_enabled = (bool) Setting::get('airtel_enabled', false);
        $this->airtel_lipa_namba = Setting::get('airtel_lipa_namba', '');
        $this->airtel_qr_code = Setting::get('airtel_qr_code', '');

        // Bank
        $this->bank_enabled = (bool) Setting::get('bank_enabled', false);
        $this->bank_name = Setting::get('bank_name', '');
        $this->bank_account_name = Setting::get('bank_account_name', '');
        $this->bank_account_number = Setting::get('bank_account_number', '');
        $this->bank_lipa_namba = Setting::get('bank_lipa_namba', '');
        $this->bank_qr_code = Setting::get('bank_qr_code', '');

        // Card Processor
        $this->card_enabled = (bool) Setting::get('card_enabled', false);
        $this->card_processor_name = Setting::get('card_processor_name', '');
        $this->card_merchant_id = Setting::get('card_merchant_id', '');
        $this->card_api_key = Setting::get('card_api_key', '');
        $this->card_api_secret = Setting::get('card_api_secret', '');

        // Zima PayByLink
        $this->zima_enabled = (bool) Setting::get('zima_enabled', false);
        $this->zima_api_key = Setting::get('zima_api_key', '');
        $this->zima_merchant_id = Setting::get('zima_merchant_id', '');

        // Firebase
        $this->firebase_project_id = Setting::get('firebase_project_id', config('firebase.projects.app.project_id', ''));
        $this->firebase_credentials_json = Setting::get('firebase_credentials_json', '');
    }

    public function switchTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    /**
     * Save General / Business settings.
     */
    public function saveGeneral()
    {
        $this->validate([
            'business_name' => 'required|string|max:255',
            'business_address' => 'required|string|max:500',
            'business_phone' => 'nullable|string|max:50',
            'business_email' => 'nullable|email|max:255',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'opening_hours' => 'nullable|string',
            'closing_hours' => 'nullable|string',
        ]);

        Setting::set('business_name', $this->business_name);
        Setting::set('business_address', $this->business_address);
        Setting::set('business_phone', $this->business_phone);
        Setting::set('business_email', $this->business_email);
        Setting::set('tax_rate', $this->tax_rate, 'integer');
        Setting::set('opening_hours', $this->opening_hours);
        Setting::set('closing_hours', $this->closing_hours);

        session()->flash('message', 'General settings saved successfully.');
    }

    /**
     * Save WhatsApp configuration.
     */
    public function saveWhatsApp()
    {
        $this->validate([
            'whatsapp_api_url' => 'required|url|max:500',
            'whatsapp_access_token' => 'nullable|string|max:500',
            'whatsapp_phone_number_id' => 'nullable|string|max:100',
            'whatsapp_business_account_id' => 'nullable|string|max:100',
            'whatsapp_webhook_secret' => 'nullable|string|max:255',
            'whatsapp_session_timeout' => 'required|integer|min:300|max:86400',
        ]);

        Setting::set('whatsapp_api_url', $this->whatsapp_api_url);
        Setting::set('whatsapp_access_token', $this->whatsapp_access_token);
        Setting::set('whatsapp_phone_number_id', $this->whatsapp_phone_number_id);
        Setting::set('whatsapp_business_account_id', $this->whatsapp_business_account_id);
        Setting::set('whatsapp_webhook_secret', $this->whatsapp_webhook_secret);
        Setting::set('whatsapp_session_timeout', $this->whatsapp_session_timeout, 'integer');
        Setting::set('whatsapp_ai_enabled', $this->whatsapp_ai_enabled ? '1' : '0', 'boolean');

        session()->flash('message', 'WhatsApp settings saved successfully.');
    }

    /**
     * Save Payment configuration (MNO + Bank + Zima).
     */
    public function savePayments()
    {
        $rules = [
            // MNO — VodaCom
            'vodacom_enabled' => 'boolean',
            'vodacom_lipa_namba' => 'nullable|string|max:50',
            'vodacom_qr_code' => 'nullable|string|max:2000',
            // MNO — Yas
            'yas_enabled' => 'boolean',
            'yas_lipa_namba' => 'nullable|string|max:50',
            'yas_qr_code' => 'nullable|string|max:2000',
            // MNO — AirTel
            'airtel_enabled' => 'boolean',
            'airtel_lipa_namba' => 'nullable|string|max:50',
            'airtel_qr_code' => 'nullable|string|max:2000',
            // Bank
            'bank_enabled' => 'boolean',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_lipa_namba' => 'nullable|string|max:50',
            'bank_qr_code' => 'nullable|string|max:2000',
            // Card Processor
            'card_enabled' => 'boolean',
            'card_processor_name' => 'nullable|string|max:255',
            'card_merchant_id' => 'nullable|string|max:100',
            'card_api_key' => 'nullable|string|max:500',
            'card_api_secret' => 'nullable|string|max:500',
            // Zima
            'zima_enabled' => 'boolean',
            'zima_api_key' => 'nullable|string|max:500',
            'zima_merchant_id' => 'nullable|string|max:100',
        ];

        $this->validate($rules);

        // MNO — VodaCom
        Setting::set('vodacom_enabled', $this->vodacom_enabled ? '1' : '0', 'boolean');
        Setting::set('vodacom_lipa_namba', $this->vodacom_lipa_namba);
        Setting::set('vodacom_qr_code', $this->vodacom_qr_code);

        // MNO — Yas
        Setting::set('yas_enabled', $this->yas_enabled ? '1' : '0', 'boolean');
        Setting::set('yas_lipa_namba', $this->yas_lipa_namba);
        Setting::set('yas_qr_code', $this->yas_qr_code);

        // MNO — AirTel
        Setting::set('airtel_enabled', $this->airtel_enabled ? '1' : '0', 'boolean');
        Setting::set('airtel_lipa_namba', $this->airtel_lipa_namba);
        Setting::set('airtel_qr_code', $this->airtel_qr_code);

        // Bank
        Setting::set('bank_enabled', $this->bank_enabled ? '1' : '0', 'boolean');
        Setting::set('bank_name', $this->bank_name);
        Setting::set('bank_account_name', $this->bank_account_name);
        Setting::set('bank_account_number', $this->bank_account_number);
        Setting::set('bank_lipa_namba', $this->bank_lipa_namba);
        Setting::set('bank_qr_code', $this->bank_qr_code);

        // Card Processor
        Setting::set('card_enabled', $this->card_enabled ? '1' : '0', 'boolean');
        Setting::set('card_processor_name', $this->card_processor_name);
        Setting::set('card_merchant_id', $this->card_merchant_id);
        Setting::set('card_api_key', $this->card_api_key);
        Setting::set('card_api_secret', $this->card_api_secret);

        // Zima PayByLink
        Setting::set('zima_enabled', $this->zima_enabled ? '1' : '0', 'boolean');
        Setting::set('zima_api_key', $this->zima_api_key);
        Setting::set('zima_merchant_id', $this->zima_merchant_id);

        session()->flash('message', 'Payment settings saved successfully.');
    }

    /**
     * Save Notifications / Firebase configuration.
     */
    public function saveNotifications()
    {
        $this->validate([
            'firebase_project_id' => 'nullable|string|max:255',
            'firebase_credentials_json' => 'nullable|string',
        ]);

        Setting::set('firebase_project_id', $this->firebase_project_id);
        Setting::set('firebase_credentials_json', $this->firebase_credentials_json);

        session()->flash('message', 'Notification settings saved successfully.');
    }

    /**
     * Legacy save method — redirects to saveGeneral for backward compatibility.
     */
    public function save()
    {
        $this->saveGeneral();
    }

    public function render()
    {
        return view('livewire.settings-management')
            ->layout('layouts.app');
    }
}
