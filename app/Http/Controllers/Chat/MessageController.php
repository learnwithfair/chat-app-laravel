<?php
namespace App\Http\Controllers\Chat;

use App\Events\MessageCreated;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            'body'          => ['nullable', 'string', 'max:5000'],
            'attachments.*' => ['file', 'max:15360'], // 15 MB each; tune as needed
        ]);

        abort_if(
            empty($validated['body']) && ! $request->hasFile('attachments'),
            422, 'Message body or attachments required'
        );

        $message = DB::transaction(function () use ($conversation, $request, $validated) {
            $msg = $conversation->messages()->create([
                'user_id' => $request->user()->id,
                'body'    => $validated['body'] ?? '',
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $mime = $file->getClientMimeType();
                    $type = str_starts_with($mime, 'image/') ? 'image' :
                    (str_starts_with($mime, 'video/') ? 'video' : 'file');

                    $path = $file->store("chat/{$conversation->id}", 'public');

                    $msg->attachments()->create([
                        'path'          => $path,
                        'mime'          => $mime,
                        'type'          => $type,
                        'size'          => $file->getSize(),
                        'original_name' => $file->getClientOriginalName(),
                    ]);
                }
            }

            MessageRead::updateOrCreate(
                ['message_id' => $msg->id, 'user_id' => $request->user()->id],
                ['read_at' => now()]
            );

            return $msg->load(['sender:id,name', 'attachments']);
        });

        broadcast(new MessageCreated($message))->toOthers();

        return response()->json([
            'message' => [
                'id'          => $message->id,
                'body'        => $message->body,
                'sender'      => [
                    'id'   => $message->sender->id,
                    'name' => $message->sender->name,
                ],
                'created_at'  => $message->created_at?->toIso8601String(),
                'attachments' => $message->attachments->map(fn($a) => [
                    'url'           => Storage::url($a->path),
                    'mime'          => $a->mime,
                    'type'          => $a->type,
                    'original_name' => $a->original_name,
                    'size'          => $a->size,
                ]),
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

    public function readAll(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $uid = $request->user()->id;

        $ids = $conversation->messages()
            ->whereDoesntHave('reads', fn($q) => $q->where('user_id', $uid))
            ->pluck('id');

        if ($ids->isEmpty()) {
            return response()->noContent();
        }

        $now    = now();
        $insert = $ids->map(fn($id) => [
            'message_id' => $id,
            'user_id'    => $uid,
            'read_at'    => $now,
        ])->all();

        MessageRead::query()->insert($insert);

        return response()->noContent();
    }

}
