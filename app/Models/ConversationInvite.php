<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationInvite extends Model
{
    protected $fillable = [
        'conversation_id',
        'token',
        'created_by',
        'expires_at',
        'max_uses',
        'used_count',
        'is_active',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active'  => 'boolean',
    ];

    public function conversation()
    {return $this->belongsTo(Conversation::class);}

    public function creator()
    {return $this->belongsTo(User::class, 'created_by');}
}
