<?php
namespace App\Http\Controllers\Api\Chat\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;

class UserBlockController extends Controller
{
    public function __construct(protected ChatService $chatService)
    {}
    // SHOW IN USER MODEL RELATIONS AS WELL
    // Toggle block/unblock for a user.
    public function toggleBlock(Request $request, User $user)
    {
        $isBlocked = $this->chatService->toggleBlock($request->user(), $user->id);
        return response()->json(['status' => 'success', 'blocked' => $isBlocked]);
    }

    // Toggle restrict/unrestrict for a user.
    public function toggleRestrict(Request $request, User $user)
    {
        $isRestricted = $this->chatService->toggleRestrict($request->user(), $user->id);
        return response()->json(['status' => 'success', 'restricted' => $isRestricted]);
    }
}
