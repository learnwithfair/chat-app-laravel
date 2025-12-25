<?php
namespace App\Http\Resources\Chat;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray($request): array
    {
        $authUser    = $request->user();
        $participant = $this->participants->firstWhere('user_id', $authUser->id);

        $receiver  = null;
        $isBlocked = $isOnline = false;

        if ($this->type === 'private') {
            $receiver = $this->otherParticipant($authUser);

            if ($receiver) {
                $isBlocked = $receiver->hasBlocked($authUser);
                $isOnline  = $receiver->isOnline();
            }
        }

        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'type'          => $this->type,

            'last_message'  => $this->lastMessage ? [
                'id'         => $this->lastMessage->id,
                'message'    => $this->lastMessage->message,
                'sender'     => [
                    'id'          => $this->lastMessage->sender->id,
                    'name'        => $this->lastMessage->sender->name,
                    'avatar_path' => $this->lastMessage->sender->avatar_path,
                ],
                'created_at' => $this->lastMessage->created_at->toDateTimeString(),
            ] : null,

            'participants'  => $this->type === 'group'
                ? $this->participants
                ->take(3)
                ->map(fn($p) => [
                    'id'          => $p->user_id,
                    'name'        => $p->user->name,
                    'role'        => $p->role,
                    'avatar_path' => $p->user->avatar_path,
                    'is_muted'    => $p->is_muted,
                ])
                : null,

            'receiver'      => $receiver ? [
                'id'          => $receiver->id,
                'name'        => $receiver->name,
                'avatar_path' => $receiver->avatar_path,
                'is_online'   => $isOnline,
                'last_seen'   => $receiver->last_seen_at?->diffForHumans(),
            ] : null,

            'is_blocked'    => $isBlocked,
            'unread_count'  => $this->unread_count ?? 0,
            'is_admin'      => $participant?->role === 'super_admin',
            'is_muted'      => $participant?->is_muted,
            'group_setting' => $this->groupSetting,
            'updated_at'    => $this->updated_at->toDateTimeString(),
        ];
    }
}
