<?php
namespace App\Models;

use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'last_seen',

    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen'         => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $settings = DB::table('settings')->pluck('value', 'key');
        $url      = config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($this->email);
        $this->notify(new CustomResetPasswordNotification($token, $settings, $url));
    }

    // Scopes
    public function scopeVerified($q)
    {return $q->whereNotNull('email_verified_at');}

    public function scopeActive($q)
    {return $q->where('is_active', true);}

    // Chat relations
    // I blocked them
    public function blockedUsers()
    {return $this->belongsToMany(User::class, 'user_blocks', 'user_id', 'blocked_id')->withTimestamps();}

    // They blocked me
    public function blockedByUsers()
    {return $this->belongsToMany(User::class, 'user_blocks', 'blocked_id', 'user_id')->withTimestamps();}

    // I restricted them
    public function restrictedUsers()
    {return $this->belongsToMany(User::class, 'user_restricts', 'user_id', 'restricted_id')->withTimestamps();}

    // They restricted me
    public function restrictedByUsers()
    {return $this->belongsToMany(User::class, 'user_restricts', 'restricted_id', 'user_id')->withTimestamps();}

    // Blocks check
    public function hasBlocked(User $user): bool
    {return $this->blockedUsers()->where('users.id', $user->id)->exists();}

    public function isBlockedBy(User $user): bool
    {return $this->blockedByUsers()->where('users.id', $user->id)->exists();}

    public function hasRestricted(User $user): bool
    {return $this->restrictedUsers()->where('users.id', $user->id)->exists();}

    // public function isRestrictedBy(User $user): bool
    // {
    //     return $this->restrictedByUsers()->where('users.id', $user->id)->exists();
    // }

    public function isOnline(): bool
    {
        return $this->last_seen_at &&
        $this->last_seen_at->greaterThan(now()->subMinutes(2));
    }
    public function tokens()
    {return $this->hasMany(DeviceToken::class);}
}
