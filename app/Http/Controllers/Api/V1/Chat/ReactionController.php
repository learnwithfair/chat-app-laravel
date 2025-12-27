<?php
namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReactionController extends Controller
{
    protected ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function index(int $messageId)
    {
        $reaction = $this->chatService->listReaction($messageId);
        return response()->json(['reaction' => $reaction, 'status' => 'success']);
    }
    public function toggleReaction(Request $request, int $messageId)
    {
        $request->validate(['reaction' => 'required|string']); // ❤️ 😂 👍 😡 😢 etc.
        $reaction = $this->chatService->toggleReaction(Auth::user(), $messageId, $request->reaction);
        return response()->json(['reaction' => $reaction, 'status' => 'success']);
    }
}
