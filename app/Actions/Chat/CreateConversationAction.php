<?php
namespace App\Actions\Chat;

use App\Http\Resources\Chat\ConversationResource;
use App\Models\User;
use App\Repositories\Chat\ConversationRepository;

class CreateConversationAction
{
    public function __construct(protected ConversationRepository $conversationRepo)
    {}    

    public function execute(User $user, int $receiverId)
    {
        $conversation = $this->conversationRepo->findPrivateBetween($user->id, $receiverId);

        if ($conversation) {
            $conversation->load([
                'participants' => function ($q) {
                    $q->where('is_active', true)->with('user');
                },
                'lastMessage.sender',
                'groupSetting',
            ]);

            $conversation->setRelation('unread_count', 0);

            return new ConversationResource($conversation);
        }

        return $this->conversationRepo->createPrivateConversation($user->id, $receiverId);
    }

}
