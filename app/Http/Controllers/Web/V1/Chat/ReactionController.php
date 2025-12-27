<?php
namespace App\Http\Controllers\Web\V1\Chat;

use App\Http\Controllers\Controller;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReactionController extends Controller
{
    protected ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }
    public function index($messageId)
    {
        $reactions = $this->chatService->listReaction($messageId);

        return Inertia::render('Chat/Reactions', [
            'message_id' => $messageId,
            'reactions'  => $reactions,
        ]);
    }

    public function toggleReaction(Request $request, $messageId)
    {
        $request->validate(['reaction' => 'required|string']);
        $this->chatService->toggleReaction(Auth::user()->id, $messageId, $request->reaction);

        return redirect()->back();
    }
}
