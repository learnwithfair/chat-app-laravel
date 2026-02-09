<?php
namespace App\Http\Requests\Chat;

use App\Http\Requests\BaseRequest;

class ManageGroupAdminRequest extends BaseRequest
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
