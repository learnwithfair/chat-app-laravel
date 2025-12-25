<?php
namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class ManageGroupAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_ids'   => 'required|array|min:1',
            'member_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }
}
