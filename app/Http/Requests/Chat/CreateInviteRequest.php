<?php
namespace App\Http\Requests\Chat;

use App\Http\Requests\BaseRequest;

class CreateInviteRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expires_at' => ['nullable', 'date', 'after:now'],
            'max_uses'   => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'expires_at.after' => 'Expiration time must be in the future.',
            'max_uses.min'     => 'Minimum usage limit must be at least 1.',
        ];
    }
}
