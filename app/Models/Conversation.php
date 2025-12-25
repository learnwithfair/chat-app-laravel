<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $guarded = [];

    public function participants()
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->latest();
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function groupSetting()
    {
        return $this->hasOne(GroupSettings::class);
    }

    public function unreadMessages()
    {
        return $this->hasMany(Message::class);
    }
    public function otherParticipant(User $currentUser)
    {
        if ($this->type !== 'private') {
            return null;
        }
        // return the participant that is NOT the current user
        return $this->participants->where('user_id', '!=', $currentUser->id)->first()?->user;
    }

}
