<?php
namespace App\Http\Controllers\Web\V1\Chat;

use App\Http\Controllers\Controller;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ChatController extends Controller
{

    public function __construct(protected ChatService $chatService)
    {}

    // List all conversations

    public function index(Request $request)
    {
        $perPage       = (int) $request->get('per_page', 30);
        $conversations = $this->chatService->listConversations(Auth::user(), $perPage, $request->query('q'));
        // dd($conversations);
        return Inertia::render('Chat/Index', [
            'conversations' => $conversations->toArray($request),
        ]);
    }

}
