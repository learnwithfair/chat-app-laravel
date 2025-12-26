<?php
namespace App\Http\Controllers\Web\V1\Chat;

use App\Http\Controllers\Controller;
use App\Repositories\Chat\ConversationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GroupController extends Controller
{
    protected $conversationRepo;

    public function __construct(ConversationRepository $conversationRepo)
    {
        $this->conversationRepo = $conversationRepo;
    }

    public function update(Request $request, $conversationId)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'group.avatar'      => 'sometimes|nullable|file',
            'group.description' => 'sometimes|nullable|string|max:1000',
            'group.type'        => 'sometimes|in:public,private',
        ]);

        $this->conversationRepo->updateGroupInfo(Auth::id(), $conversationId, $request->all());

        return redirect()->back();
    }

    public function getMembers($conversationId)
    {
        $members = $this->conversationRepo->getMembers(Auth::id(), $conversationId);

        return Inertia::render('Chat/GroupMembers', [
            'conversation_id' => $conversationId,
            'members'         => $members,
        ]);
    }

    public function addMembers(Request $request, $conversationId)
    {
        $request->validate(['member_ids' => 'required|array']);
        $this->conversationRepo->addMembers(Auth::user(), $conversationId, $request->member_ids);

        return redirect()->back();
    }

    public function removeMember(Request $request, $conversationId)
    {
        $request->validate(['member_ids' => 'required|array']);
        $this->conversationRepo->removeMember(Auth::id(), $conversationId, $request->member_ids);

        return redirect()->back();
    }

    public function addAdmins(Request $request, $conversationId)
    {
        $request->validate(['member_ids' => 'required|array']);
        $this->conversationRepo->addGroupAdmins(Auth::user(), $conversationId, $request->member_ids);

        return redirect()->back();
    }

    public function removeAdmins(Request $request, $conversationId)
    {
        $request->validate(['member_ids' => 'required|array']);
        $this->conversationRepo->removeGroupAdmins(Auth::user(), $conversationId, $request->member_ids);

        return redirect()->back();
    }

    public function muteToggleGroup(Request $request, $conversationId)
    {
        $minutes = $request->input('minutes', 0);
        $this->conversationRepo->muteGroup(Auth::id(), $conversationId, $minutes);

        return redirect()->back();
    }

    public function leaveGroup($conversationId)
    {
        $this->conversationRepo->leaveGroup(Auth::user(), $conversationId);

        return redirect()->route('chat.index');
    }
}
