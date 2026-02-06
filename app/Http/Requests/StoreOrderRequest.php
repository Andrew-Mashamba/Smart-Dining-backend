<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('items')) {
            $items = $this->input('items', []);
            foreach ($items as $index => $item) {
                if (isset($item['special_instructions'])) {
                    $items[$index]['special_instructions'] = strip_tags($item['special_instructions']);
                }
            }
            $this->merge(['items' => $items]);
        }

        if ($this->has('notes')) {
            $this->merge(['notes' => strip_tags($this->input('notes'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'guest_id' => 'required|exists:guests,id',
            'table_id' => 'required|exists:tables,id',
            'waiter_id' => 'nullable|exists:staff,id',
            'session_id' => 'nullable|exists:guest_sessions,id',
            'order_source' => 'nullable|in:whatsapp,pos,web',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'items.*.special_instructions' => 'nullable|string|max:200',
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
            'guest_id.required' => 'Guest information is required',
            'guest_id.exists' => 'The selected guest does not exist',
            'table_id.required' => 'Table selection is required',
            'table_id.exists' => 'The selected table does not exist',
            'items.required' => 'At least one item must be added to the order',
            'items.min' => 'At least one item must be added to the order',
            'items.*.menu_item_id.required' => 'Menu item is required for each order item',
            'items.*.menu_item_id.exists' => 'One or more selected menu items do not exist',
            'items.*.quantity.required' => 'Quantity is required for each item',
            'items.*.quantity.min' => 'Quantity must be at least 1',
        ];
    }
}
