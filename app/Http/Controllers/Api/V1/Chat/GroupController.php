<?php
namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ManageGroupAdminRequest;
use App\Http\Requests\Chat\UpdateGroupInfoRequest;
use App\Services\Chat\ChatService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    use ApiResponse;
    public function __construct(protected ChatService $chatService)
    {}

    public function addMembers(ManageGroupAdminRequest $request, $conversationId)
    {
        $request->validated();
        $result = $this->chatService->addMembers(Auth::user(), $conversationId, $request->member_ids);
        return $this->success($result, 'Members added successfully');
    }

    public function getMembers(Request $request, $conversationId)
    {
        $result = $this->chatService->getMembers(Auth::user(), $conversationId);
        return $this->success($result, 'Members fetched successfully');
    }

    public function removeMember(ManageGroupAdminRequest $request, $conversationId)
    {
        $request->validated();
        $result = $this->chatService->removeMember(Auth::user(), $conversationId, $request->member_ids);
        return $this->success($result, 'Members removed successfully');
    }

    public function addAdmins(ManageGroupAdminRequest $request, $conversationId)
    {
        $request->validated();
        $this->chatService->addGroupAdmins(Auth::user(), $conversationId, $request->member_ids);
        return $this->success(null, 'Admins added successfully');
    }

    public function removeAdmins(ManageGroupAdminRequest $request, $conversationId)
    {
        $request->validated();
        $result = $this->chatService->removeGroupAdmins(Auth::user(), $conversationId, $request->member_ids);
        return $this->success($result, 'Admins removed successfully');
    }

    public function muteToggleGroup(Request $request, $conversationId)
    {
        $this->chatService->muteGroup(Auth::user(), $conversationId, $request->minutes ?? 0);
        return $this->success(null, 'Group muted successfully');
    }

    public function leaveGroup($conversationId)
    {
        $this->chatService->leaveGroup(Auth::user(), $conversationId);
        return $this->success(null, 'Group left successfully');
    }
    public function deleteGroup($conversationId)
    {
        $this->chatService->deleteGroup(Auth::user(), $conversationId);
        return $this->success(null, 'Group delete successfully');
    }

    public function update(UpdateGroupInfoRequest $request, $conversation)
    {
        $group = $this->chatService->updateGroupInfo(Auth::user(), $conversation, $request->validated());
        return $this->success($group, 'Group updated successfully');
    }

}
