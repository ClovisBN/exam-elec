<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckUserRole
{
    public function handle($request, Closure $next, $role)
    {
        $user = Auth::user();

        if ($user && $user->role && $user->role->name === $role) {
            return $next($request);
        }

        return redirect('/home');
    }
}
