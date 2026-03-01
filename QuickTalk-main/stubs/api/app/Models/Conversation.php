<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function users()
    {
        return $this->morphedByMany(User::class, 'participant')
            ->using(Participant::class)
            ->withPivot(['role', 'is_muted', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    public function group()
    {
        return $this->hasOne(Group::class);
    }
}
