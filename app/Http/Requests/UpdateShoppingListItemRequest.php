<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateShoppingListItemRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['sometimes', 'numeric', 'gt:0', 'decimal:0,3'],
            'checked' => ['sometimes', 'boolean'],
            'unit_price' => ['sometimes', 'nullable', 'numeric', 'decimal:0,2', 'min:0'],
        ];
    }
}
