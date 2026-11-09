<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        abort_if($request->user()?->blocked, 403, 'Аккаунт заблокирован');

        return $next($request);
    }
}
