<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageReaction extends Model
{
    protected $guarded = [];
    protected $casts   = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function message()
    {return $this->belongsTo(Message::class);}

    public function user()
    {return $this->belongsTo(User::class);}
}
