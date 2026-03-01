<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\MessageStatus;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GetMessageController extends Controller
{
    use ApiResponse;

    /**
     * Get chat messages based on receiver ID or conversation ID.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'receiver_id' => ['nullable', 'required_without:conversation_id', 'integer'],
            'conversation_id' => ['nullable', 'required_without:receiver_id', 'integer'],
            'message' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $user = auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $message = $request->query('message');

        $receiver_id = null;
        if ($request->query('receiver_id')) {
            $receiver = User::find($request->query('receiver_id'));
            if (!$receiver) {
                return $this->error([], 'Receiver not found', 404);
            }
            $receiver_id = $receiver->id;
        }

        $conversation_id = $request->query('conversation_id');

        // Conversation logic
        $conversation = $this->getConversation($user, $receiver_id, $conversation_id);

        if (!$conversation) {
            return $this->error([], 'Conversation not found', 404);
        } else {
            if ($conversation->type == 'private' && !$receiver_id) {
                return $this->error([], 'Receiver ID is required for private conversations', 422);
            }
        }

        if (!$conversation_id) {
            $conversation->load([
                'participants' => function ($query) use ($user) {
                    $query->where('participant_id', '!=', $user->id)
                        ->where('participant_type', get_class($user))
                        ->with(['participant' => function ($q) {
                            $q->select('id', 'name', 'avatar');
                        }])
                        ->take(3);
                },
            ]);
        } else {
            $conversation->load([
                'participants' => function ($query) use ($user) {
                    $query->where('participant_id', '!=', $user->id)
                        ->where('participant_type', get_class($user))
                        ->with(['participant' => function ($q) {
                            $q->select('id', 'name', 'avatar');
                        }])
                        ->take(3);
                }
            ], 'group');
        }

        $messagesQuery = $conversation->messages()
            ->with([
                'sender:id,name,avatar',
                'parentMessage',
                'statuses.user:id,name,avatar',
                'attachments'
            ])
            ->withCount([
                'reactions as like'     => function ($q) {
                    $q->where('emoji', 'like');
                },
                'reactions as love'     => function ($q) {
                    $q->where('emoji', 'love');
                },
                'reactions as laugh'    => function ($q) {
                    $q->where('emoji', 'laugh');
                },
                'reactions as surprised'      => function ($q) {
                    $q->where('emoji', 'surprised');
                },
                'reactions as sad'      => function ($q) {
                    $q->where('emoji', 'sad');
                },
                'reactions as angry'    => function ($q) {
                    $q->where('emoji', 'angry');
                },
            ])
            ->withTrashed()
            ->orderBy('created_at', 'desc');

        if (!empty($message)) {
            $messagesQuery->where('message', 'like', "%{$message}%");
        }

        $messages = $messagesQuery->paginate(100);

        if ($conversation->type !== 'self') {
            $latestMessageId = $conversation->messages()
                ->latest('id')
                ->value('id');

            $messageStatus = MessageStatus::updateOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'user_id' => $user->id,
                ],
                [
                    'message_id' => $latestMessageId,
                    'updated_at' => Carbon::now(),
                ]
            );
        }


        return $this->success([
            'conversation' => $conversation,
            'messages' => $messages,
        ], 'Chat messages retrieved successfully', 200);
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
            $group = Group::where('conversation_id', $conversation_id)->first();
            if ($group && $group->type == 'private') {
                $conversation = Conversation::where('id', $conversation_id)
                    ->whereHas('participants', function ($query) use ($user) {
                        $query->where('participant_id', $user->id)
                            ->where('participant_type', User::class);
                    })
                    ->where('type', 'group')
                    ->first();

                if (!$conversation) {
                    return false;
                }
            } else {
                $conversation = Conversation::where('id', $conversation_id)->where('type', 'group')->first();

                if (!$conversation) {
                    return false;
                }
            }

            if (!$conversation) {
                return false;
            } else {
                return $conversation;
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
