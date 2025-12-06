<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
                 'name' => 'nullable|string|max:255',
            'is_group' => 'boolean',
        ];
    }
       public function messages(): array
    {
        return [
            'user_ids.required' => 'At least one user must be selected.',
            'user_ids.array' => 'User IDs must be an array.',
            'user_ids.min' => 'At least one user must be selected.',
            'user_ids.*.exists' => 'One or more selected users do not exist.',
        ];
    }
       protected function prepareForValidation()
    {
        // Convert null name to empty string
        if (is_null($this->name)) {
            $this->merge([
                'name' => ''
            ]);
        }

        // Ensure is_group is a boolean
        $this->merge([
            'is_group' => filter_var($this->is_group, FILTER_VALIDATE_BOOLEAN)
        ]);
    }
}