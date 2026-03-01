<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageAttachmentResource;
use App\Models\Group;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class GroupMediaController extends Controller
{
    use ApiResponse;
    /**
     * Handle the incoming request to get group media.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, int $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $group = Group::query()
            ->whereHas('conversation.participants', function ($query) use ($user) {
                $query->where('participant_id', $user->id);
            })
            ->find($id);

        if (!$group) {
            return $this->error([], 'Group not found', 404);
        }

        $mediaMessages = $group->conversation
            ->messages()
            ->select(['id'])
            ->whereHas('attachments')
            ->with('attachments')
            ->latest()
            ->paginate(50);

        return $this->success($mediaMessages, 'Group media retrieved successfully', 200);
    }
}
