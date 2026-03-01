<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailOtp extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'verification_code', 'expires_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public $timestamps = false;
}
