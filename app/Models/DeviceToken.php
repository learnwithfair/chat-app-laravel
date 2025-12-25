<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    public $timestamps = false;

    protected $table = 'device_tokens';

    protected $fillable = [
        'user_id',
        'platform',
        'token',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];
    protected $hidden = ['user_id', 'meta', 'created_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
