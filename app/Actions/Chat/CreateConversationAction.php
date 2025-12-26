<?php
namespace App\Actions\Chat;

use App\Models\User;
use App\Repositories\Chat\ConversationRepository;

class CreateConversationAction
{
    public function __construct(protected ConversationRepository $conversationRepo) {}

    public function execute(User $user, int $receiverId)
    {
        $existing = $this->conversationRepo->findPrivateBetween($user->id, $receiverId);
        if ($existing) return $existing;

        return $this->conversationRepo->createPrivateConversation($user->id, $receiverId);
    }
}
