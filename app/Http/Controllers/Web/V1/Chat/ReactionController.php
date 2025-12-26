<?php
namespace App\Http\Controllers\Web\V1\Chat;

use App\Http\Controllers\Controller;
use App\Repositories\Chat\ReactionRepository; // assuming you have this
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReactionController extends Controller
{
    protected $reactionRepo;

    public function __construct(ReactionRepository $reactionRepo)
    {
        $this->reactionRepo = $reactionRepo;
    }

    public function index($messageId)
    {
        $reactions = $this->reactionRepo->listReactions($messageId);

        return Inertia::render('Chat/Reactions', [
            'message_id' => $messageId,
            'reactions'  => $reactions,
        ]);
    }

    public function toggleReaction(Request $request, $messageId)
    {
        $request->validate(['reaction' => 'required|string']);
        $this->reactionRepo->toggleReaction(Auth::user()->id, $messageId, $request->reaction);

        return redirect()->back();
    }
}
