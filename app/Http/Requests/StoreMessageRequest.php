<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'conversation_id' => 'required|exists:conversations,id',
            'body' => 'nullable|string|max:5000',
            'type' => 'required|in:text,image,file',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'file|max:10240', // 10MB max
        ];
    }

    public function messages(): array
    {
        return [
            'conversation_id.required' => 'Conversation ID is required.',
            'conversation_id.exists' => 'The selected conversation does not exist.',
            'body.string' => 'Message must be a string.',
            'body.max' => 'Message may not be greater than 5000 characters.',
            'type.required' => 'Message type is required.',
            'type.in' => 'Message type must be text, image, or file.',
            'attachments.max' => 'You can upload up to 5 files at once.',
            'attachments.*.max' => 'Each file must be less than 10MB.',
        ];
    }

    protected function prepareForValidation()
    {
        // Ensure body is not null
        if (is_null($this->body)) {
            $this->merge([
                'body' => '',
            ]);
        }
    }

    public function attributes(): array
    {
        return [
            'attachments.*' => 'attachment',
        ];
    }
}
