<?php
namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Services\Chat\ChatService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    use ApiResponse;
    public function __construct(protected ChatService $chatService)
    {}

    // list conversations
    public function index(Request $request)
    {
        $perPage       = (int) $request->get('per_page', 30);
        $conversations = $this->chatService->listConversations(Auth::user(), $perPage, $request->query('q'));
        return $this->success($conversations, 'Conversations list Fetched Successfully', 200, true);
    }
    public function mediaLibrary(Request $request, int $conversationId)
    {
        $perPage       = (int) $request->get('per_page', 30);
        $meadiaLibrary = $this->chatService->mediaLibrary($request->user(), $conversationId, $perPage);
        return $this->success($meadiaLibrary, 'Media Library Fetched Successfully');
    }

    public function startPrivateConversation(Request $request)
    {
        $conversation = $this->chatService->startConversation(Auth::user(), $request->receiver_id);
        return $this->success($conversation, 'Conversation Created Successfully', 201);
    }

    // create group
    public function store(Request $request)
    {
        $group = $this->chatService->createGroup(Auth::user(), $request->all());
        return $this->success($group, 'Group Created Successfully', 201);
    }

    public function destroy(Request $request, int $conversation)
    {
        $this->chatService->deleteConversationForUser(Auth::id(), $conversation);
        return $this->success(null, 'Conversation removed from your list', 200);
    }

}
