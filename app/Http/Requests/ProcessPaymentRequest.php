<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,mobile_money',
            'phone_number' => 'required_if:payment_method,mobile_money|string',
            'provider' => 'required_if:payment_method,mobile_money|in:mpesa,tigopesa,airtel',
            'card_last_four' => 'required_if:payment_method,card|string|size:4',
            'card_type' => 'required_if:payment_method,card|in:visa,mastercard,amex',
            'tendered' => 'required_if:payment_method,cash|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_id.required' => 'Order ID is required',
            'order_id.exists' => 'The selected order does not exist',
            'amount.required' => 'Payment amount is required',
            'amount.min' => 'Payment amount must be greater than or equal to 0',
            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Invalid payment method selected',
            'phone_number.required_if' => 'Phone number is required for mobile money payments',
            'provider.required_if' => 'Mobile money provider is required',
            'card_last_four.required_if' => 'Last 4 digits of card are required',
            'card_type.required_if' => 'Card type is required',
            'tendered.required_if' => 'Tendered amount is required for cash payments',
        ];
    }
}
