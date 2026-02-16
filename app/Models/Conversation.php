<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getInviteLinkAttribute()
    {return $this->activeInvites->sortByDesc('created_at')->first();}

    public function participants()
    {return $this->hasMany(ConversationParticipant::class);}

    public function messages()
    {return $this->hasMany(Message::class)->latest();}

    public function creator()
    {return $this->belongsTo(User::class, 'created_by');}

    public function lastMessage()
    {return $this->hasOne(Message::class)->latestOfMany();}

    public function groupSetting()
    {return $this->hasOne(GroupSettings::class);}

    public function unreadMessages()
    {return $this->hasMany(Message::class);}

    public function invites()
    {return $this->hasMany(ConversationInvite::class);}

    public function activeInvites()
    {return $this->hasMany(ConversationInvite::class)->where('is_active', true);}

    public function otherParticipant(User $currentUser)
    {
        // if ($this->type !== 'private') {
        //     return null;
        // }
        // return the participant that is NOT the current user
        return $this->participants->where('user_id', '!=', $currentUser->id)->first()?->user;
    }

    // check allow_members_to_send_messages

    public function canUserSendMessage(?ConversationParticipant $participant = null): bool
    {
        // Private chat → always allowed
        if ($this->type === 'private') {
            return true;
        }

        // Admins can always send
        if ($participant && in_array($participant->role, ['admin', 'super_admin'])) {
            return true;
        }

        $settings = $this->groupSetting;

        // No settings → allowed
        if (! $settings) {
            return true;
        }

        return (bool) $settings->allow_members_to_send_messages;
    }

}
