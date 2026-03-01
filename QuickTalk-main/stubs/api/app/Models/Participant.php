<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
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
            'conversation_id' => 'integer',
            'participant_id' => 'integer',
            'participant_type' => 'string',
            'is_muted' => 'boolean',
            'left_at' => 'datetime',
            'joined_at' => 'datetime',
        ];
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function participant()
    {
        return $this->morphTo();
    }
}
