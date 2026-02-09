<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupSettings extends Model
{
    protected $guarded = [];

    protected $casts = [
        'created_at'                               => 'datetime',
        'updated_at'                               => 'datetime',
        'allow_members_to_send_messages'           => 'boolean',
        'allow_members_to_add_remove_participants' => 'boolean',
        'allow_members_to_change_group_info'       => 'boolean',
        'admins_must_approve_new_members'          => 'boolean',
        'allow_invite_users_via_link'              => 'boolean',
    ];
    protected $hidden = ['created_at', 'updated_at', 'type'];

    public function conversation()
    {return $this->belongsTo(Conversation::class);}

    public function participants()
    {return $this->conversation->participants();}
}
