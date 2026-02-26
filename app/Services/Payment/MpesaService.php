<?php

namespace App\Services\Payment;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaService
{
    protected string $environment;

    public function __construct()
    {
        $this->environment = Setting::get('mpesa_environment', config('mpesa.environment', 'sandbox'));
    }

    /**
     * Check if M-Pesa is enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) Setting::get('mpesa_enabled', config('mpesa.enabled', false));
    }

    /**
     * Get OAuth access token from Safaricom.
     */
    public function getAccessToken(): string
    {
        $cacheKey = 'mpesa_access_token';

        return Cache::remember($cacheKey, 3500, function () {
            $url = config("mpesa.urls.{$this->environment}.oauth");
            $consumerKey = Setting::get('mpesa_consumer_key', config('mpesa.consumer_key'));
            $consumerSecret = Setting::get('mpesa_consumer_secret', config('mpesa.consumer_secret'));

            $response = Http::withBasicAuth($consumerKey, $consumerSecret)
                ->get($url, ['grant_type' => 'client_credentials']);

            if (! $response->successful()) {
                Log::error('M-Pesa OAuth failed', ['response' => $response->body()]);
                throw new \Exception('Failed to get M-Pesa access token');
            }

            return $response->json('access_token');
        });
    }

    /**
     * Initiate STK Push (Lipa Na M-Pesa Online).
     */
    public function initiateSTKPush(string $phone, float $amount, string $reference): array
    {
        if (! $this->isEnabled()) {
            throw new \Exception('M-Pesa is not enabled');
        }

        $url = config("mpesa.urls.{$this->environment}.stk_push");
        $shortcode = Setting::get('mpesa_shortcode', config('mpesa.shortcode'));
        $timestamp = $this->getTimestamp();
        $password = $this->generatePassword($timestamp);
        $formattedPhone = $this->formatPhoneNumber($phone);

        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) ceil($amount),
            'PartyA' => $formattedPhone,
            'PartyB' => $shortcode,
            'PhoneNumber' => $formattedPhone,
            'CallBackURL' => Setting::get('mpesa_callback_url', config('mpesa.callback_url')),
            'AccountReference' => $reference,
            'TransactionDesc' => Setting::get('business_name', config('app.name', 'Smart Dining')) . ' Order Payment',
        ];

        $response = Http::withToken($this->getAccessToken())
            ->post($url, $payload);

        $result = $response->json();

        Log::info('M-Pesa STK Push initiated', [
            'phone' => $formattedPhone,
            'amount' => $amount,
            'reference' => $reference,
            'response_code' => $result['ResponseCode'] ?? null,
            'checkout_request_id' => $result['CheckoutRequestID'] ?? null,
        ]);

        return $result;
    }

    /**
     * Query STK Push transaction status.
     */
    public function querySTKStatus(string $checkoutRequestId): array
    {
        $url = config("mpesa.urls.{$this->environment}.stk_query");
        $shortcode = Setting::get('mpesa_shortcode', config('mpesa.shortcode'));
        $timestamp = $this->getTimestamp();
        $password = $this->generatePassword($timestamp);

        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        $response = Http::withToken($this->getAccessToken())
            ->post($url, $payload);

        return $response->json();
    }

    /**
     * Format phone number to 255XXXXXXXXX format.
     */
    public function formatPhoneNumber(string $phone): string
    {
        // Strip + before removing non-digits (handle +255 prefix)
        $phone = ltrim($phone, '+');
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Handle Tanzanian numbers
        if (str_starts_with($phone, '0')) {
            $phone = '255'.substr($phone, 1);
        } elseif (! str_starts_with($phone, '255')) {
            $phone = '255'.$phone;
        }

        return $phone;
    }

    /**
     * Generate the M-Pesa API password.
     */
    protected function generatePassword(string $timestamp): string
    {
        $shortcode = Setting::get('mpesa_shortcode', config('mpesa.shortcode'));
        $passkey = Setting::get('mpesa_passkey', config('mpesa.passkey'));

        return base64_encode($shortcode.$passkey.$timestamp);
    }

    /**
     * Get the current timestamp in M-Pesa format.
     */
    protected function getTimestamp(): string
    {
        return now()->format('YmdHis');
    }
}
