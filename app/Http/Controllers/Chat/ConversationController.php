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
        $conversations = Conversation::query()
            ->forUser($request->user()->id)
            ->with(['users:id,name', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->orderByDesc(
                Message::query()->select('created_at')->whereColumn('conversation_id', 'conversations.id')->latest()->take(1)
            )
            ->get()
            ->map(fn($c) => [
                'id'           => $c->id,
                'name'         => $c->is_group ? ($c->name ?: 'Group'): $c->users->where('id', '!=', $request->user()->id)->first()?->name,
                'is_group'     => (bool) $c->is_group,
                'last_message' => optional($c->messages->first())->body,
                'members'      => $c->users->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
            ]);

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

    // public function store(Request $request)
    // {
    //     // Accept either user_id (for 1:1) or member_ids[] (for future group support)
    //     $data = $request->validate([
    //         'user_id'      => ['nullable', 'integer', 'exists:users,id'],
    //         'member_ids'   => ['nullable', 'array'],
    //         'member_ids.*' => ['integer', 'exists:users,id'],
    //         'name'         => ['nullable', 'string', 'max:255'],
    //     ]);

    //     $authId = $request->user()->id;

    //     // Build the membership set
    //     $ids = collect($data['member_ids'] ?? [])
    //         ->when($data['user_id'] ?? null, fn($c) => $c->push($data['user_id']))
    //         ->push($authId)
    //         ->unique()
    //         ->values();

    //     // Must be at least two distinct users
    //     if ($ids->count() < 2) {
    //         return response()->json(['message' => 'At least one other user is required'], 422);
    //     }

    //     $isGroup = $ids->count() > 2;

    //     // Try to find an existing direct conversation with exactly these users
    //     $existing = Conversation::query()
    //         ->where('is_group', $isGroup)
    //         ->whereHas('users', fn($q) => $q->whereIn('users.id', $ids), '=', $ids->count())
    //         ->whereDoesntHave('users', fn($q) => $q->whereNotIn('users.id', $ids))
    //         ->first();

    //     if ($existing) {
    //         return response()->json(['id' => $existing->id], 200);
    //     }

    //     // Create new conversation
    //     $conversation = Conversation::create([
    //         'name'     => $isGroup ? ($data['name'] ?? null) : null,
    //         'is_group' => $isGroup,
    //     ]);

    //     $conversation->users()->attach($ids);

    //     return response()->json(['id' => $conversation->id], 201);
    // }

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

        if ($existing) {
            return response()->json(['id' => $existing->id], 200);
        }

        $conversation = Conversation::create([
            'name'     => $isGroup ? ($data['name'] ?? null) : null,
            'is_group' => $isGroup,
        ]);

        $conversation->users()->attach($ids);

        return response()->json(['id' => $conversation->id], 201);
    }

}
