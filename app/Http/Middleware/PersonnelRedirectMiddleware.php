<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PersonnelRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check authenticated users
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        $userRole = $user->role;

        // Allow admin and technician roles
        if (in_array($userRole, ['admin', 'technician'])) {
            // After successful login, redirect technicians to their panel
            if ($userRole === 'technician' && $request->is('admin') && !$request->is('admin/login')) {
                return redirect('/technician')->with('success', 'Welcome to your technician dashboard!');
            }
            
            // If technician tries to access admin areas (except login), redirect to technician panel
            if ($userRole === 'technician' && $request->is('admin/*') && !$request->is('admin/login')) {
                return redirect('/technician')->with('info', 'Redirected to technician dashboard.');
            }
            
            return $next($request);
        }

        // Deny access for customers and other roles
        abort(403, 'Access denied. Staff access only.');
    }
}
