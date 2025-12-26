<?php
namespace App\Http\Controllers\Web\V1\Chat;

use App\Http\Controllers\Controller;
use App\Repositories\Chat\ConversationRepository;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ChatController extends Controller
{
    protected $conversationRepo;

    public function __construct(ConversationRepository $conversationRepo)
    {
        $this->conversationRepo = $conversationRepo;
    }

    // List all conversations
    public function index()
    {
        $conversations = $this->conversationRepo->listFor(Auth::user(), 20);

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations
        ]);
    }
}
