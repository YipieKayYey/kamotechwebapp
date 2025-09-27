<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToFilamentLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If user is authenticated
        if (auth()->check()) {
            $user = auth()->user();

            // If user is admin or technician and trying to access main login
            if (in_array($user->role, ['admin', 'technician']) && $request->routeIs('login')) {
                // Redirect to their respective Filament panel
                return match ($user->role) {
                    'admin' => redirect('/admin'),
                    'technician' => redirect('/technician'),
                };
            }
        }

        return $next($request);
    }
}
