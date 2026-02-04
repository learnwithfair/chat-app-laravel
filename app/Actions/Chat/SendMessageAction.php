<?php
namespace App\Actions\Chat;

use App\Models\Message;
use App\Models\User;
use App\Repositories\Chat\MessageRepository;

class SendMessageAction
{
    public function __construct(protected MessageRepository $messageRepo)
    {}

    public function execute(User $user, array $data)
    {
        return $this->messageRepo->storeMessage($user, $data);
    }
    public function update(User $user, array $data, Message $message)
    {
        return $this->messageRepo->updateMessage($user, $data, $message);
    }
    // mediaLibrary
    public function mediaLibrary(User $user, $conversationId, int $perPage = 30)
    {
        return $this->messageRepo->mediaLibrary($user, $conversationId, $perPage);
    }
}
