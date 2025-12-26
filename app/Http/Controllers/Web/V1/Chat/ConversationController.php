<?php
namespace App\Http\Controllers\Web\V1\Chat;

use App\Actions\Chat\CreateConversationAction;
use App\Http\Controllers\Controller;
use App\Repositories\Chat\ConversationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    protected $conversationRepo;

    public function __construct(ConversationRepository $conversationRepo)
    {
        $this->conversationRepo = $conversationRepo;
    }

    public function start(Request $request, CreateConversationAction $action)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
        ]);

        $conversation = $action->execute(Auth::user(), $request->receiver_id);

        return redirect()->route('chat.index');
    }

    public function delete($conversationId)
    {
        $this->conversationRepo->deleteForUser(Auth::id(), $conversationId);

        return redirect()->back();
    }
}
