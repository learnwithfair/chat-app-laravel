<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Message extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $hidden = [
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'sender_id' => 'integer',
            'receiver_id' => 'integer',
            'conversation_id' => 'integer',
            'reply_to_message_id' => 'integer',
        ];
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function replies()
    {
        return $this->hasMany(Message::class, 'reply_to_message_id');
    }

    public function parentMessage()
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function reactionsSummary()
    {
        return $this->reactions()
                    ->select('emoji', DB::raw('COUNT(*) as count'))
                    ->groupBy('emoji');
    }

    public function statuses()
    {
        return $this->hasMany(MessageStatus::class);
    }

    public function messageDeleteForme()
    {
        return $this->belongsToMany(User::class, 'message_deleted_for_mes'); // or your pivot table name
    }

    protected $appends = ['deleted_for_me'];

    public function getDeletedForMeAttribute()
    {
        $userId = auth()->id(); // Or pass this dynamically if needed
        return $this->messageDeleteForme()->where('user_id', $userId)->exists();
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function firstAttachment()
    {
        return $this->hasOne(MessageAttachment::class)->latestOfMany();
    }
}
