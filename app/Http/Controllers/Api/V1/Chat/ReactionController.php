<?php
namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Services\Chat\ChatService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReactionController extends Controller
{
    use ApiResponse;
    protected ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function index(int $messageId)
    {
        $reaction = $this->chatService->listReaction($messageId);
        return $this->success($reaction, 'Fetched All Reaction Successfully');
    }
    public function toggleReaction(Request $request, int $messageId)
    {
        $request->validate(['reaction' => 'required|string']); // ❤️ 😂 👍 😡 😢 etc.
        $reaction = $this->chatService->toggleReaction(Auth::user(), $messageId, $request->reaction);

        return $this->success($reaction, 'Fetched Successfully');
    }
}
