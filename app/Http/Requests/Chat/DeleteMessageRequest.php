<?php
namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class DeleteMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message_id'    => 'nullable|integer|exists:messages,id',
            'message_ids'   => 'nullable|array',
            'message_ids.*' => 'integer|exists:messages,id',
        ];
    }
}
