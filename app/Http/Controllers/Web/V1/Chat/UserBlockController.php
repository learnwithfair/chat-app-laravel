<?php
namespace App\Http\Controllers\Web\V1\Chat;

use App\Http\Controllers\Controller;
use App\Repositories\Chat\ConversationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserBlockController extends Controller
{
    protected $conversationRepo;

    public function __construct(ConversationRepository $conversationRepo)
    {
        $this->conversationRepo = $conversationRepo;
    }

    public function toggleBlock($userId)
    {
        $this->conversationRepo->toggleBlock(Auth::user(), $userId);

        return redirect()->back();
    }

    public function toggleRestrict($userId)
    {
        $this->conversationRepo->toggleRestrict(Auth::user(), $userId);

        return redirect()->back();
    }
}
