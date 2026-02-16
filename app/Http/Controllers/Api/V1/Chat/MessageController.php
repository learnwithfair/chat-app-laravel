<?php
namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\DeleteMessageRequest;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Models\Message;
use App\Services\Chat\ChatService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    use ApiResponse;
    public function __construct(protected ChatService $chatService)
    {}

    public function show(Request $request, int $message)
    {
        // message = Conversation id
        $perPage  = $request->query('per_page', 20);
        $messages = $this->chatService->getMessages(Auth::user(), $message, $request->query("q"), $perPage);
        return $this->success($messages, 'Messages Fetched Successfully', 200, true);
    }

    public function getAllPinedMessages(Request $request, int $conversation)
    {
        $perPage       = (int) $request->get('per_page', 40);
        $pinedMessages = $this->chatService->pinedMessages($request->user(), $conversation, $request->query("q"), $perPage);
        return $this->success($pinedMessages, 'Pined Messages Fetched Successfully', 200, true);
    }

    public function store(SendMessageRequest $request)
    {
        $message = $this->chatService->sendMessage(Auth::user(), $request->validated());
        return $this->success($message, 'Message Sent Successfully', 201);
    }
    public function update(SendMessageRequest $request, Message $message)
    {
        $message = $this->chatService->updateMessage(Auth::user(), $request->validated(), $message);
        return $this->success($message, 'Message Updated Successfully', 201);
    }
    public function pinToggleMessage(Request $request, Message $message)
    {
        $result = $this->chatService->pinToggleMessage($request->user(), $message);
        return $this->success($result, $result['message']->is_pinned ? 'Message Pinned Successfully' : 'Message Unpinned Successfully', 201);
    }

    //  {"message_ids": [12, 13, 14]}
    public function deleteForMe(DeleteMessageRequest $request)
    {
        $result = $this->chatService->deleteForMe(Auth::user(), $request->validated());
        return $this->success($result, 'Message Deleted Successfully', 201);
    }

    //  {"message_ids": [12, 13, 14]}

    public function deleteForEveryone(DeleteMessageRequest $request)
    {
        $result = $this->chatService->deleteForEveryone(Auth::user(), $request->validated());
        return $this->success($result, 'Message Deleted Successfully', 201);
    }

    // When open conversation
    public function markAsSeen($conversationId)
    {
        $this->chatService->markConversationAsRead(Auth::user(), $conversationId);
        return $this->success(null, 'Conversation marked as seen.');
    }

    // When already open conversation
    public function markSeen(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|integer|exists:conversations,id',
            'message_ids'     => 'required|array',
            'message_ids.*'   => 'integer|exists:messages,id',
        ]);
        $result = $this->chatService->markMessagesAsRead(Auth::user(), $request->all());
        return $this->success($result, 'Messages marked as seen.');
    }

    public function markAsDelivered(int $conversationId)
    {
        $this->chatService->markDelivered(Auth::user(), $conversationId);
        return $this->success(null, 'Conversation marked as delivered.');
    }

    public function forward(Request $request, Message $message)
    {
        $data = $request->validate([
            'conversation_ids'   => ['required', 'array', 'min:1'],
            'conversation_ids.*' => ['integer', 'exists:conversations,id'],
        ]);

        $user    = $request->user();
        $results = [];

        foreach ($data['conversation_ids'] as $conversationId) {

            $payload = [
                'conversation_id'       => $conversationId,
                'message'               => $message->message,
                'message_type'          => $message->message_type,
                'forward_to_message_id' => $message->id,
            ];

            $sent = $this->chatService->sendMessage($user, $payload);

            $results[] = $sent; // MessageResource already
        }

        return $this->success($results, 'Message forwarded successfully', 201);
    }
}
