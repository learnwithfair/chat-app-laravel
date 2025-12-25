<?php
namespace App\Http\Controllers\Api\Chat\V1;

use App\Http\Controllers\Controller;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    public function __construct(protected ChatService $chatService)
    {}

    // list conversations
    public function index(Request $request)
    {
        $perPage       = (int) $request->get('per_page', 30);
        $conversations = $this->chatService->listConversations(Auth::user(), $perPage, $request->query('q'));
        return response()->json(['conversations' => $conversations]);
    }

    public function startPrivateConversation(Request $request)
    {
        $conversation = $this->chatService->startConversation(Auth::user(), $request->receiver_id);
        return response()->json(['conversation' => $conversation]);
    }

    // create group
    public function store(Request $request)
    {
        $group = $this->chatService->createGroup(Auth::user(), $request->all());
        return response()->json(['group' => $group]);
    }

    public function destroy(Request $request, int $conversation)
    {
        $this->chatService->deleteConversationForUser(Auth::id(), $conversation);
        return response()->json(['status' => 'success', 'message' => 'Conversation removed from your list']);
    }

}
