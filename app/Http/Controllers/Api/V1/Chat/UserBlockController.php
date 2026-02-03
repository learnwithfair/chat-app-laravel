<?php
namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Chat\ChatService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class UserBlockController extends Controller
{
    use ApiResponse;
    public function __construct(protected ChatService $chatService)
    {}

    public function index(Request $request)
    {
        $authUser = $request->user();

        // Dynamic inputs
        $perPage = (int) $request->get('per_page', 20);
        $perPage = min(max($perPage, 5), 50); // safety: 5–50

        $search = $request->get('search');

        $users = User::query()->where('id', '!=', $authUser->id)

        // I blocked them
            ->whereNotIn('id', function ($q) use ($authUser) {
                $q->select('blocked_id')
                    ->from('user_blocks')
                    ->where('user_id', $authUser->id);
            })

        // They blocked me
            ->whereNotIn('id', function ($q) use ($authUser) {
                $q->select('user_id')
                    ->from('user_blocks')
                    ->where('blocked_id', $authUser->id);
            })

        // Optional search
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })

            ->select('id', 'name', 'email', 'avatar_path')
            ->orderBy('name')
            ->paginate($perPage);

        return $this->success($users, 'Users Fetched Successfully', 200, true);
    }

    // SHOW IN USER MODEL RELATIONS AS WELL
    // Toggle block/unblock for a user.
    public function toggleBlock(Request $request, User $user)
    {
        $isBlocked = $this->chatService->toggleBlock($request->user(), $user->id);

        return $this->success($isBlocked, 'User ' . $isBlocked ? 'blocked' : 'unblocked', 200);
    }

    // Toggle restrict/unrestrict for a user.
    public function toggleRestrict(Request $request, User $user)
    {
        $isRestricted = $this->chatService->toggleRestrict($request->user(), $user->id);
        return $this->success($isRestricted, 'User ' . $isRestricted ? 'restricted' : 'unrestricted', 200);
    }
}
