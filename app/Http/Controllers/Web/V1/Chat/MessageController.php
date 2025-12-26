<?php
namespace App\Http\Controllers\Web\V1\Chat;

use App\Actions\Chat\SendMessageAction;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Repositories\Chat\MessageRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MessageController extends Controller
{
    protected $messageRepo;

    public function __construct(MessageRepository $messageRepo)
    {
        $this->messageRepo = $messageRepo;
    }

    // Show messages for a conversation
    public function show($conversationId)
    {
        $messages = $this->messageRepo->getByConversation(Auth::user(), $conversationId);

        return Inertia::render('Chat/Messages', [
            'conversation_id' => $conversationId,
            'messages'        => $messages,
        ]);
    }

    // Store a new message
    public function store(Request $request, SendMessageAction $action)
    {
        $request->validate([
            'conversation_id' => 'nullable|exists:conversations,id',
            'receiver_id'     => 'nullable|exists:users,id',
            'message'         => 'nullable|string',
            'attachments'     => 'nullable|array',
        ]);

        $action->execute(Auth::user(), $request->all());

        return redirect()->back();
    }

    // Update a message
    public function update(Request $request, Message $message, SendMessageAction $action)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $action->update(Auth::user(), $request->all(), $message);

        return redirect()->back();
    }

    // Delete message for me
    public function deleteForMe(Request $request)
    {
        $request->validate(['message_ids' => 'required|array']);
        $this->messageRepo->deleteMessagesForUser(Auth::id(), $request->message_ids);

        return redirect()->back();
    }

    // Mark as seen
    public function markAsSeen($conversationId)
    {
        app(\App\Actions\Chat\MarkMessageReadAction::class)->execute(Auth::user(), $conversationId);

        return redirect()->back();
    }

    public function markAsDelivered($conversationId)
    {
        // Optional for web
        return redirect()->back();
    }
}
