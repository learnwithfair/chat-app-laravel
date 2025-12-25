<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationParticipant extends Model
{
    protected $guarded = [];
    protected $casts   = [
        'is_muted'    => 'boolean',
        'is_active'    => 'boolean',
        'muted_until' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
