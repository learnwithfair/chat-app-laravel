<?php
namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ManageGroupAdminRequest;
use App\Http\Requests\Chat\UpdateGroupInfoRequest;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    public function __construct(protected ChatService $chatService)
    {}

    public function addMembers(ManageGroupAdminRequest $request, $conversationId)
    {
        $request->validated();
        $result = $this->chatService->addMembers(Auth::user(), $conversationId, $request->member_ids);
        return response()->json(['result' => $result]);
    }

    public function getMembers(Request $request, $conversationId)
    {
        $result = $this->chatService->getMembers(Auth::user(), $conversationId);
        return response()->json(['result' => $result]);
    }

    public function removeMember(ManageGroupAdminRequest $request, $conversationId)
    {
        $request->validated();
        $result = $this->chatService->removeMember(Auth::user(), $conversationId, $request->member_ids);
        return response()->json(['result' => $result]);
    }

    public function addAdmins(ManageGroupAdminRequest $request, $conversationId)
    {
        $request->validated();
        $this->chatService->addGroupAdmins(Auth::user(), $conversationId, $request->member_ids);
        return response()->json(['status' => 'success', 'message' => 'Admins added successfully']);
    }

    public function removeAdmins(ManageGroupAdminRequest $request, $conversationId)
    {
        $request->validated();
        $this->chatService->removeGroupAdmins(Auth::user(), $conversationId, $request->member_ids);
        return response()->json(['status' => 'success', 'message' => 'Admins removed successfully']);
    }

    public function muteToggleGroup(Request $request, $conversationId)
    {
        $this->chatService->muteGroup(Auth::user(), $conversationId, $request->minutes ?? 0);
        return response()->json(['status' => 'success']);
    }

    public function leaveGroup($conversationId)
    {
        $this->chatService->leaveGroup(Auth::user(), $conversationId);
        return response()->json(['status' => 'success']);
    }

    public function update(UpdateGroupInfoRequest $request, $conversation)
    {
        $group = $this->chatService->updateGroupInfo(Auth::user(), $conversation, $request->validated());
        return response()->json(['group' => $group]);
    }

}
