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

    public function store(SendMessageRequest $request)
    {
        $message = $this->chatService->sendMessage(Auth::user(), $request->validated());
        // return response()->json(['status' => 'success', 'message' => $message]);
        return $this->success($message, 'Message Sent Successfully', 201);
    }
    public function update(SendMessageRequest $request, Message $message)
    {
        $message = $this->chatService->updateMessage(Auth::user(), $request->validated(), $message);
        // return response()->json(['status' => 'success', 'message' => $message]);
        return $this->success($message, 'Message Updated Successfully', 201);
    }

    public function typing(Request $request, int $conversationId)
    {
        $request->validate(['is_typing' => 'required|boolean']);
        $isTyping = $this->chatService->typing(Auth::user(), $conversationId, $request->is_typing);
        return response()->json(['status' => 'success', 'message' => $isTyping]);
    }

    //  {"message_ids": [12, 13, 14]}
    public function deleteForMe(DeleteMessageRequest $request)
    {
        $result = $this->chatService->deleteForMe(Auth::user(), $request->validated());
        return response()->json(['status' => 'success', 'message' => $result]);
    }

    //  {"message_ids": [12, 13, 14]}

    public function deleteForEveryone(DeleteMessageRequest $request)
    {
        $result = $this->chatService->deleteForEveryone(Auth::user(), $request->validated());
        return response()->json(['status' => 'success', 'message' => $result]);
    }
    public function markAsSeen($conversationId)
    {
        $this->chatService->markConversationAsRead(Auth::user(), $conversationId);
        return response()->json(['status' => 'success', 'message' => 'Conversation marked as seen.']);
    }

    public function markAsDelivered(int $conversationId)
    {
        $this->chatService->markDelivered(Auth::user(), $conversationId);
        return response()->json(['status' => 'success']);
    }

}
