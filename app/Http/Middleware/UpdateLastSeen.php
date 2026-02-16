<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UpdateLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            $user->updateQuietly(['last_seen_at' => now()]);
        }

        return $next($request);
    }
}
