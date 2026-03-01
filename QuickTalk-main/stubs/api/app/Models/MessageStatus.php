<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageStatus extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'updated_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'message_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
