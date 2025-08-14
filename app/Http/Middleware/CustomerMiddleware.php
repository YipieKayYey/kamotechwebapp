<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please log in to access this area.');
        }

        // Check if user has customer role
        if (auth()->user()->role !== 'customer') {
            // Redirect to appropriate dashboard based on user role
            return $this->redirectToUserDashboard(auth()->user()->role);
        }

        return $next($request);
    }

    /**
     * Redirect user to their appropriate dashboard
     */
    private function redirectToUserDashboard(string $role): Response
    {
        switch ($role) {
            case 'admin':
                return redirect('/admin')
                    ->with('error', 'Access denied. This area is for customers only.');
            case 'technician':
                return redirect()->route('technician.dashboard')
                    ->with('error', 'Access denied. This area is for customers only.');
            default:
                return redirect()->route('customer-dashboard')
                    ->with('error', 'Access denied. This area is for customers only.');
        }
    }
}