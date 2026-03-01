<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'conversation_id' => 'integer',
            'allow_members_to_send_messages' => 'boolean',
            'allow_members_to_add_remove_participants' => 'boolean',
            'allow_members_to_change_group_info' => 'boolean',
            'admins_must_approve_new_members' => 'boolean',
        ];
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
