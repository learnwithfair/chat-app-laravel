<?php
namespace App\Http\Controllers\Chat;

use App\Events\MessageCreated;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function index(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->latest('id')
            ->paginate(30);

        return response()->json($messages);
    }

    public function store(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = DB::transaction(function () use ($conversation, $request, $validated) {
            $msg = $conversation->messages()->create([
                'user_id' => $request->user()->id,
                'body'    => $validated['body'],
            ]);

            // mark sender's read
            MessageRead::updateOrCreate(
                ['message_id' => $msg->id, 'user_id' => $request->user()->id],
                ['read_at' => now()]
            );

            return $msg->load('sender:id,name');
        });

        broadcast(new MessageCreated($message))->toOthers();

        return response()->json([
            'message' => [
                'id'         => $message->id,
                'body'       => $message->body,
                'sender'     => [
                    'id'   => $message->sender->id,
                    'name' => $message->sender->name,
                ],
                'created_at' => $message->created_at?->toIso8601String(),
            ],
        ], 201);

    }

    public function read(Request $request, Conversation $conversation, Message $message)
    {
        $this->authorize('view', $conversation);

        abort_unless($message->conversation_id === $conversation->id, 404);

        MessageRead::updateOrCreate(
            ['message_id' => $message->id, 'user_id' => $request->user()->id],
            ['read_at' => now()]
        );

        return response()->noContent();
    }
}
