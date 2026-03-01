<?php

namespace App\Http\Controllers\Api\Chat;

use App\Events\ConversationEvent;
use App\Events\MessageSentEvent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\Message;
use App\Models\MessageStatus;
use App\Models\Participant;
use App\Models\User;
use App\Services\FCMService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SendMessageController extends Controller
{
    use ApiResponse;

    /**
     * Send a message to a specific user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        // Validate the request
        if ($this->validate($request) !== true) {
            return $this->validate($request);
        }

        $user = auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $receiver_id = null;
        if ($request->receiver_id) {
            $receiver = User::find($request->receiver_id);
            if (!$receiver) {
                return $this->error([], 'Receiver not found', 404);
            }
            $receiver_id = $receiver->id;
        }

        $conversation_id = $request->get('conversation_id');
        $message = $request->get('message');
        $file = $request->file('file');
        $reply_to_message_id = $request->get('reply_to_message_id');
        $conversation = null;
        $messageType = null;

        if (!$message && $file) {
            $messageType = $this->getMessageType($file);
            if (!$messageType) {
                return $this->error([], 'Only images, videos, audios, or documents of the same type are allowed in one message.', 422);
            }
        } elseif ($message && !$file) {
            $messageType = 'text';
        } elseif ($message && $file) {
            $messageType = 'multiple';
        }
        // Conversation logic
        $conversation = $this->getConversation($user, $receiver_id, $conversation_id);

        if (!$conversation) {
            return $this->error([], 'Conversation not found', 404);
        } else {
            if ($conversation->type == 'private' && !$receiver_id) {
                return $this->error([], 'Receiver ID is required for private conversations', 422);
            }
        }

        // Create the message
        if ($message && !$file) {
            $messageData = [
                'sender_id' => $user->id,
                'receiver_id' => $receiver_id,
                'conversation_id' => $conversation->id,
                'message' => $message,
                'message_type' => $messageType,
                'reply_to_message_id' => $reply_to_message_id ? $reply_to_message_id : null,
            ];
        } elseif ($file && !$message) {
            $messageData = [
                'sender_id' => $user->id,
                'receiver_id' => $receiver_id,
                'conversation_id' => $conversation->id,
                'message_type' => $messageType,
                'reply_to_message_id' => $reply_to_message_id ? $reply_to_message_id : null,
            ];
        } elseif ($message && $file) {
            $messageData = [
                'sender_id' => $user->id,
                'receiver_id' => $receiver_id,
                'conversation_id' => $conversation->id,
                'message' => $message,
                'message_type' => $messageType,
                'reply_to_message_id' => $reply_to_message_id ? $reply_to_message_id : null,
            ];
        } else {
            return $this->error([], 'Message or file is required', 422);
        }

        $messageSend = Message::create($messageData);
        $conversation->touch();


        if ($messageSend && $messageSend->message_type !== 'text') {
            $files = $request->file('file');
            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {
                $path = $file->store("ChatFiles/$user->name", 'public');
                $messageSend->attachments()->create([
                    'file_name'      => $file->hashName(),
                    'original_name'  => $file->getClientOriginalName(),
                    'file_path'      => "storage" . "/" . $path,
                    'file_type' => $file->getMimeType(),
                ]);
            }
        }

        $messageSend->load(['parentMessage', 'attachments', 'sender:id,name,avatar', 'receiver:id,name,avatar']);

        # Broadcast the message
        broadcast(new MessageSentEvent('message_send', $messageSend));

        foreach ($conversation->participants as $participant) {

            $lastReadMessageId = MessageStatus::where('user_id', $participant->participant->id)
                ->where('conversation_id', $conversation->id)
                ->value('message_id');

            $unreadMessageCount = Message::where('conversation_id', $conversation->id)
                ->where('receiver_id', $participant->participant->id)
                ->when($lastReadMessageId, function ($q) use ($lastReadMessageId) {
                    $q->where('id', '>', $lastReadMessageId);
                })
                ->count();

            # Broadcast the Conversation and Unread Message Count
            broadcast(new ConversationEvent('message_send', $messageSend, $participant->participant_id, $unreadMessageCount));

            if ($participant->is_muted == 1) {
                $fcmService = new FCMService();
                $fcmService->sendMessage(
                    $participant->participant->firebaseTokens->token,
                    $user->name . ' sent a message',
                    $message,
                    $messageSend->attachments,
                    [
                        'type'       => 'chat',
                        'conversation_id' => (string)$conversation->id,
                        'message_id' => (string)$messageSend->id,
                    ]
                );
            }
        }

        return $this->success([
            'message' => $messageSend,
            'conversation' => $conversation,
        ], 'Message sent successfully', 201);
    }

    /**
     * Validate the request data.
     *
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     */
    private function validate($request)
    {
        $maxSizeKB = config('chat.attachmentSizeLimit') * 1024;
        $size = "max:{$maxSizeKB}";

        $validator = Validator::make($request->all(), [
            'receiver_id' => ['nullable', 'required_without:conversation_id', 'integer'],
            'conversation_id' => ['nullable', 'required_without:receiver_id', 'integer'],
            'message' => ['string', 'required_without:file', 'max:1000'],
            'file' => ['required_without:message', 'array', 'max:5'],
            'file.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,wmv,flv,mkv,webm,mp3,wav,aac,ogg,m4a,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar', $size],
            'reply_to_message_id' => ['nullable', 'exists:messages,id'],
        ], [
            'file.*.mimes' => 'Each uploaded file must be a valid image, video, audio, or document.',
            'file.*.max' => "Each uploaded file must not exceed " . config('chat.attachmentSizeLimit') . " MB.",
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        } else {
            return true;
        }
    }

    /**
     * Determine the type of message based on the uploaded files.
     *
     * @param mixed $files
     * @return string|null
     */
    private function getMessageType($files)
    {
        if (!is_array($files)) {
            $files = [$files]; // normalize single file to array
        }

        $types = collect($files)->map(function ($file) {
            $mimeType = $file->getMimeType();

            return match (true) {
                str_starts_with($mimeType, 'image/') => 'image',
                str_starts_with($mimeType, 'video/') => 'video',
                str_starts_with($mimeType, 'audio/') => 'audio',
                default => 'file',
            };
        })->unique();

        if ($types->count() > 1) {
            return $this->error([], 'All uploaded files must be of the same type (e.g., all images or all videos or all audios or all files)', 422);
        }

        return $messageType = $types->first();
    }

    /**
     * Get or create a conversation based on the provided parameters.
     *
     * @param User $user
     * @param int|null $receiver_id
     * @param int|null $conversation_id
     * @return Conversation|null
     */
    private function getConversation(User $user, $receiver_id = null, $conversation_id = null)
    {
        if ($conversation_id) {
            $conversation = Conversation::where('id', $conversation_id)
                ->whereHas('participants', function ($query) use ($user) {
                    $query->where('participant_id', $user->id)
                        ->where('participant_type', User::class);
                })
                ->where('type', 'group')
                ->first();

            if (!$conversation) {
                return false;
            } else {
                $group = Group::where('conversation_id', $conversation->id)->first();
                if ($group->allow_members_to_send_messages == 0) {
                    $isLeader = Participant::where('conversation_id', $group->conversation_id)
                        ->where('participant_id', $user->id)
                        ->whereIn('role', ['admin', 'super_admin'])
                        ->exists();

                    if (!$isLeader) {
                        return false;
                    } else {
                        return $conversation;
                    }
                } else {
                    return $conversation;
                }
            }
        } elseif ($receiver_id) {
            $receiver = User::find($receiver_id);
            if (!$receiver) {
                return $this->error([], 'Receiver not found', 404);
            }

            if ($receiver->id === $user->id) {
                $conversation = Conversation::whereHas('participants', function ($q) use ($user) {
                    $q->where('participant_id', $user->id)
                        ->where('participant_type', User::class);
                })
                    ->where('type', 'self')
                    ->first();

                if (!$conversation) {
                    $conversation = Conversation::create([
                        'type' => 'self',
                    ]);

                    $conversation->participants()->createMany([
                        [
                            'participant_id' => $user->id,
                            'participant_type' => User::class,
                            'joined_at' => Carbon::now(),
                        ],
                    ]);
                    return $conversation;
                } else {
                    return $conversation;
                }
            }

            $conversation = Conversation::whereHas('participants', function ($q) use ($user) {
                $q->where('participant_id', $user->id)
                    ->where('participant_type', User::class);
            })
                ->whereHas('participants', function ($q) use ($receiver) {
                    $q->where('participant_id', $receiver->id)
                        ->where('participant_type', User::class);
                })
                ->where('type', 'private')
                ->first();

            if (!$conversation) {
                $conversation = Conversation::create([
                    'type' => 'private',
                ]);

                $conversation->participants()->createMany([
                    [
                        'participant_id' => $receiver->id,
                        'participant_type' => User::class,
                        'joined_at' => Carbon::now(),
                    ],
                    [
                        'participant_id' => $user->id,
                        'participant_type' => User::class,
                        'joined_at' => Carbon::now(),
                    ],
                ]);
                return $conversation;
            } else {
                return $conversation;
            }
        } else {
            return $this->error([], 'Either receiver_id or conversation_id is required', 422);
        }
    }
}
