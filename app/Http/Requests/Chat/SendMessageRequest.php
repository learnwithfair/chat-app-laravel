<?php
namespace App\Http\Requests\Chat;

use App\Http\Requests\BaseRequest;

class SendMessageRequest extends BaseRequest
{
    public function authorize(): bool
    {return true;}

    public function rules(): array
    {
        return [
            // For private chat, conversation_id is optional
            'conversation_id'     => 'nullable|exists:conversations,id',

            // For private messaging; optional for group
            'receiver_id'         => 'nullable|exists:users,id',

            'message'             => 'nullable|string',
            'message_type'        => 'nullable|in:text,image,video,audio,file,multiple,system',

            'reply_to_message_id' => 'nullable|exists:messages,id',

            'attachments'         => 'nullable|array',
            'attachments.*.path'  => 'required|file',
        ];
    }

    public function messages(): array
    {
        return [
            'conversation_id.exists'      => 'Invalid conversation.',
            'receiver_id.exists'          => 'Invalid receiver.',
            'reply_to_message_id.exists'  => 'Replied message not found.',
            'attachments.*.path.required' => 'Attachment path is required.',
        ];
    }
}
