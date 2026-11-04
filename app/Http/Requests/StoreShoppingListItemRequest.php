<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShoppingListItemRequest extends FormRequest
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
        $workspaceId = $this->user()->currentWorkspace()->id;

        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where(fn ($query) => $query->where('workspace_id', $workspaceId)),
            ],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'checked' => ['sometimes', 'boolean'],
        ];
    }
}
