<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageAttachment extends Model
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
            'duration' => 'integer',
        ];
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
