<?php
namespace App\Http\Requests\Chat;

use App\Http\Requests\BaseRequest;

class UpdateGroupInfoRequest extends BaseRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                                           => 'sometimes|string|max:255',
            'group'                                          => 'sometimes|array',
            'group.avatar'                                   => 'sometimes|nullable|file|mimes:jpg,jpeg,png,gif,svg|max:20480',
            'group.description'                              => 'sometimes|nullable|string|max:1000',
            'group.type'                                     => 'sometimes|in:public,private',
            'group.allow_members_to_send_messages'           => 'sometimes|boolean',
            'group.allow_members_to_add_remove_participants' => 'sometimes|boolean',
            'group.allow_members_to_change_group_info'       => 'sometimes|boolean',
            'group.admins_must_approve_new_members'          => 'sometimes|boolean',
            'group.allow_invite_users_via_link'              => 'sometimes|boolean',
        ];
    }

    // Custom attribute names for better error messages.

    public function attributes(): array
    {
        return [
            'group.avatar'      => 'group avatar',
            'group.description' => 'group description',
            'group.type'        => 'group type',
        ];
    }
}
