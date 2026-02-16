<?php
namespace App\Http\Resources\Chat;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    protected ?User $forUser = null;

    public function forUser(User $user): static
    {
        $this->forUser = $user;
        return $this;
    }

    public function toArray($request): array
    {
        $authUser    = $this->forUser ?? $request->user();
        $participant = $this->participants->firstWhere('user_id', $authUser->id);

        $receiver  = null;
        $isBlocked = $isOnline = false;

        if ($this->type === 'private') {
            $receiver = $this->otherParticipant($authUser);
            // dd($receiver);

            if ($receiver && $authUser) {
                $blockedByMe   = $authUser->hasBlocked($receiver); // I blocked them
                $blockedByThem = $receiver->hasBlocked($authUser); // They blocked me

                $isBlocked = $blockedByMe || $blockedByThem;
                $isOnline  = $receiver->isOnline();
            }
        }
        $inviteLink = null;

        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'type'             => $this->type,

            'last_message'     => $this->lastMessage ? [
                'id'          => $this->lastMessage->id,
                'message'     => $this->lastMessage->message,
                'attachments' => MessageAttachmentResource::collection($this->lastMessage->attachments),
                'sender'      => [
                    'id'          => $this->lastMessage->sender->id,
                    'name'        => $this->lastMessage->sender->name,
                    'avatar_path' => $this->lastMessage->sender->avatar_path,
                    'is_online'   => $this->lastMessage->sender->isOnline(),
                    'last_seen'   => $this->lastMessage->sender->last_seen_at?->diffForHumans(),
                ],
                'created_at'  => $this->lastMessage->created_at->toDateTimeString(),
            ] : null,

            // 'participants'     => $this->type === 'group'
            //     ? $this->participants
            //     ->take(3)
            //     ->map(fn($p) => [
            //         'id'          => $p->user_id,
            //         'name'        => $p->user->name,
            //         'role'        => $p->role,
            //         'avatar_path' => $p->user->avatar_path,
            //         'is_muted'    => $p->is_muted,
            //     ])
            //     : null,
            'participants'     => $this->participants
                ->take(3)
                ->map(fn($p) => [
                    'id'          => $p->user_id,
                    'name'        => $p->user->name,
                    'role'        => $p->role,
                    'avatar_path' => $p->user->avatar_path,
                    'is_muted'    => $p->is_muted,
                    'is_online'   => $p->user->isOnline(),
                ]),

            'receiver'         => $receiver ? [
                'id'          => $receiver->id,
                'name'        => $receiver->name,
                'avatar_path' => $receiver->avatar_path,
                'is_online'   => $isOnline,
                'last_seen'   => $receiver->last_seen_at?->diffForHumans(),
            ] : null,

            'is_online'        => $isOnline,
            'is_blocked'       => $isBlocked,
            'blocked'          => [
                'by_me'   => $blockedByMe ?? false,
                'by_them' => $blockedByThem ?? false,
            ],
            'unread_count'     => $this->unread_count ?? 0,
            'is_admin'         => $participant?->role === 'super_admin' || $participant?->role === 'admin',
            'role'             => $participant?->role,
            'is_muted'         => $participant?->is_muted,
            'group_setting'    => $this->groupSetting,
            'can_send_message' => $this->canUserSendMessage($participant),
            'invite_link'      => $this->inviteLink ? config("services.invite_url") . "/{$this->inviteLink->token}" : null,
            'updated_at' => $this->updated_at->toDateTimeString(),
            'created_by' => $this->creator->name ?? null,
            'created_at' => $this->created_at->toDateTimeString(),
            // 'created_at' => $this->created_at->format('Y/m/d h:i:s A'),
        ];
    }
}
