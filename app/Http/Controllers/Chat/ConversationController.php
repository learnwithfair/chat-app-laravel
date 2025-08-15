<?php
namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationController extends Controller
{

    public function index(Request $request)
    {
        $uid = $request->user()->id;

        $conversations = Conversation::query()
            ->forUser($uid)
            ->with(['users:id,name,email', 'lastMessage.sender:id,name'])
            ->withCount([
                'messages as unread_count' => function ($q) use ($uid) {
                    $q->whereDoesntHave('reads', fn($r) => $r->where('user_id', $uid));
                },
            ])
            ->orderByDesc(
                Message::query()->select('created_at')->whereColumn('conversation_id', 'conversations.id')->latest()->take(1)
            )
            ->get()
            ->map(function ($c) use ($uid) {
                $other = $c->is_group ? null : $c->users->firstWhere('id', '!=', $uid);
                return [
                    'id'           => $c->id,
                    'name'         => $c->is_group ? ($c->name ?: 'Group'): ($other->name ?? 'Conversation'),
                    'is_group'     => (bool) $c->is_group,
                    'last_message' => optional($c->lastMessage)->body,
                    'last_at'      => optional($c->lastMessage?->created_at)->toIso8601String(),
                    'unread'       => $c->unread_count,
                    'members'      => $c->users->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
                ];
            });

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->latest('id')
            ->paginate(30); // infinite scroll friendly (load older)

        return Inertia::render('Chat/Show', [
            'conversation'    => [
                'id'       => $conversation->id,
                'name'     => $conversation->name,
                'is_group' => (bool) $conversation->is_group,
            ],
            'initialMessages' => $messages,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'      => ['nullable', 'integer', 'exists:users,id'],
            'email'        => ['nullable', 'string', 'email', 'max:255'],
            'member_ids'   => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
            'name'         => ['nullable', 'string', 'max:255'],
        ]);

        $authId = $request->user()->id;

        // Resolve email -> user_id (when provided)
        if (! empty($data['email']) && empty($data['user_id'])) {
            $other = User::query()
                ->where('email', $data['email'])
                ->first(['id']);

            if (! $other) {
                return response()->json(['message' => 'User not found for the given email'], 404);
            }
            if ($other->id === $authId) {
                return response()->json(['message' => 'Cannot start a chat with yourself'], 422);
            }
            $data['user_id'] = $other->id;
        }

        // Build member set
        $ids = collect($data['member_ids'] ?? [])
            ->when($data['user_id'] ?? null, fn($c) => $c->push($data['user_id']))
            ->push($authId)
            ->unique()
            ->values();

        if ($ids->count() < 2) {
            return response()->json(['message' => 'At least one other user is required'], 422);
        }

        $isGroup = $ids->count() > 2;

        // Find existing conversation with exactly these members
        $existing = Conversation::query()
            ->where('is_group', $isGroup)
            ->whereHas('users', fn($q) => $q->whereIn('users.id', $ids), '=', $ids->count())
            ->whereDoesntHave('users', fn($q) => $q->whereNotIn('users.id', $ids))
            ->first();

        // if ($existing) {
        //     return response()->json(['id' => $existing->id], 200);
        // }

        $conversation = Conversation::create([
            'name'     => $isGroup ? ($data['name'] ?? null) : null,
            'is_group' => $isGroup,
        ]);

        $conversation->users()->attach($ids);

        // after $existing found:
        if ($existing) {
            $other = $existing->is_group ? null : $existing->users()->where('users.id', '!=', $authId)->first();
            return response()->json([
                'conversation' => [
                    'id'           => $existing->id,
                    'name'         => $existing->is_group ? ($existing->name ?: 'Group'): ($other->name ?? 'Conversation'),
                    'is_group'     => (bool) $existing->is_group,
                    'last_message' => null,
                    'last_at'      => null,
                    'unread'       => 0,
                ],
            ], 200);
        }

        // after creating $conversation:
        $other = $conversation->is_group ? null : $conversation->users()->where('users.id', '!=', $authId)->first();
        return response()->json([
            'conversation' => [
                'id'           => $conversation->id,
                'name'         => $conversation->is_group ? ($conversation->name ?: 'Group'): ($other->name ?? 'Conversation'),
                'is_group'     => (bool) $conversation->is_group,
                'last_message' => null,
                'last_at'      => null,
                'unread'       => 0,
            ],
        ], 201);

        // return response()->json(['id' => $conversation->id], 201);
    }
    // public function store(Request $request)
    // {
    //     $data = $request->validate([
    //         'user_id'      => ['nullable', 'integer', 'exists:users,id'],
    //         'email'        => ['nullable', 'string', 'email', 'max:255'],
    //         'member_ids'   => ['nullable', 'array'],
    //         'member_ids.*' => ['integer', 'exists:users,id'],
    //         'name'         => ['nullable', 'string', 'max:255'],
    //     ]);

    //     $authId = $request->user()->id;

    //     // Resolve email -> user_id (when provided)
    //     if (! empty($data['email']) && empty($data['user_id'])) {
    //         $other = User::query()
    //             ->where('email', $data['email'])
    //             ->first(['id']);

    //         if (! $other) {
    //             return response()->json(['message' => 'User not found for the given email'], 404);
    //         }
    //         if ($other->id === $authId) {
    //             return response()->json(['message' => 'Cannot start a chat with yourself'], 422);
    //         }
    //         $data['user_id'] = $other->id;
    //     }

    //     // Build member set
    //     $ids = collect($data['member_ids'] ?? [])
    //         ->when($data['user_id'] ?? null, fn($c) => $c->push($data['user_id']))
    //         ->push($authId)
    //         ->unique()
    //         ->values();

    //     if ($ids->count() < 2) {
    //         return response()->json(['message' => 'At least one other user is required'], 422);
    //     }

    //     $isGroup = $ids->count() > 2;

    //     // Find existing conversation with exactly these members
    //     $existing = Conversation::query()
    //         ->where('is_group', $isGroup)
    //         ->whereHas('users', fn($q) => $q->whereIn('users.id', $ids), '=', $ids->count())
    //         ->whereDoesntHave('users', fn($q) => $q->whereNotIn('users.id', $ids))
    //         ->first();

    //     if ($existing) {
    //         return response()->json(['id' => $existing->id], 200);
    //     }

    //     $conversation = Conversation::create([
    //         'name'     => $isGroup ? ($data['name'] ?? null) : null,
    //         'is_group' => $isGroup,
    //     ]);

    //     $conversation->users()->attach($ids);

    //     return response()->json(['id' => $conversation->id], 201);
    // }

}
