<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('/about', function () {
    return Inertia::render('about');
})->name('about');

Route::get('/contact', function () {
    return Inertia::render('contact');
})->name('contact');

Route::get('/reviews', function () {
    return Inertia::render('reviews');
})->name('reviews');

// Service Routes
Route::get('/services/cleaning', function () {
    return Inertia::render('services/cleaning');
})->name('services.cleaning');

Route::get('/services/repair', function () {
    return Inertia::render('services/repair');
})->name('services.repair');

Route::get('/services/installation', function () {
    return Inertia::render('services/installation');
})->name('services.installation');

Route::get('/services/freon-charging', function () {
    return Inertia::render('services/freon-charging');
})->name('services.freon-charging');

Route::get('/services/repiping', function () {
    return Inertia::render('services/repiping');
})->name('services.repiping');

Route::get('/services/troubleshooting', function () {
    return Inertia::render('services/troubleshooting');
})->name('services.troubleshooting');

Route::get('/services/relocation', function () {
    return Inertia::render('services/relocation');
})->name('services.relocation');

Route::get('/booking', function () {
    return Inertia::render('booking');
})->name('booking');

// Removed address autocomplete functionality

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        // Redirect users to their appropriate dashboard based on role
        $user = auth()->user();
        return match($user->role) {
            'admin' => redirect('/admin'),
            'technician' => redirect()->route('technician.dashboard'),
            'customer' => redirect()->route('customer-dashboard'),
            default => redirect()->route('customer-dashboard'),
        };
    })->name('dashboard');
    
    Route::get('customer-dashboard', function () {
        return Inertia::render('customer-dashboard');
    })->middleware('customer')->name('customer-dashboard');
    
    Route::get('evaluation-feedback', function () {
        return Inertia::render('evaluation-feedback');
    })->name('evaluation-feedback');

    Route::get('technician/dashboard', function () {
        return Inertia::render('technician-dashboard');
    })->middleware('technician')->name('technician.dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
