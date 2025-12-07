<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{conversation}', function ($user, Conversation $conversation) {
    if (! auth()->check()) {
        return false;
    }

    $isMember = $conversation->users()->whereKey($user->id)->exists();

    // Return user info to others in presence channel if authorized
    return $isMember ? [
        'id'     => $user->id,
        'name'   => $user->name,
        'avatar' => method_exists($user, 'avatarUrl') ? $user->avatarUrl() : null,
    ] : false;
});
