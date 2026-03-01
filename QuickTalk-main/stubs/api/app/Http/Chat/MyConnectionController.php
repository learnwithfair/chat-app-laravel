<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class MyConnectionController extends Controller
{
    use ApiResponse;

    /**
     * Get user's connections.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function __invoke(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $connections = Conversation::whereHas('participants', function ($query) use ($user) {
            $query->where('participant_id', $user->id);
        })->with(['participants' => function ($query) use ($user) {
            $query->where('participant_id', '!=', $user->id)->with('participant:id,name');
        }])->get();

        if ($connections->isEmpty()) {
            return $this->error([], 'No connections found.', 404);
        }

        return $this->success($connections, 'Connections retrieved successfully.');
    }
}
