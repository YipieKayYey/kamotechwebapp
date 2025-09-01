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

// Booking Routes - New Dynamic Implementation
Route::get('/booking', [App\Http\Controllers\BookingController::class, 'create'])->name('booking');
Route::post('/booking', [App\Http\Controllers\BookingController::class, 'store'])->name('booking.store');

// AJAX endpoints for real-time booking features
Route::get('/api/booking/availability', [App\Http\Controllers\BookingController::class, 'checkAvailability'])->name('booking.availability');
Route::get('/api/booking/technicians', [App\Http\Controllers\BookingController::class, 'getTechnicianRanking'])->name('booking.technicians');
Route::get('/api/booking/pricing', [App\Http\Controllers\BookingController::class, 'calculatePricing'])->name('booking.pricing');

// Customer API Routes (moved from api.php for session access)
Route::middleware(['auth', 'customer'])->group(function () {
    // Customer Dashboard
    Route::get('/api/customer/dashboard', [App\Http\Controllers\CustomerController::class, 'getDashboardData']);
    Route::get('/api/customer/bookings', [App\Http\Controllers\CustomerController::class, 'getBookingHistory']);
    Route::get('/api/customer/bookings/{bookingId}', [App\Http\Controllers\CustomerController::class, 'getBookingDetails']);
    Route::post('/api/bookings/{booking}/request-cancellation', [App\Http\Controllers\BookingController::class, 'requestCancellation']);
    
    // Notifications
    Route::get('/api/notifications', [App\Http\Controllers\NotificationController::class, 'getNotifications']);
    Route::get('/api/notifications/unread-count', [App\Http\Controllers\NotificationController::class, 'getUnreadCount']);
    Route::get('/api/notifications/stats', [App\Http\Controllers\NotificationController::class, 'getNotificationStats']);
    Route::post('/api/notifications/{notificationId}/mark-read', [App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/api/notifications/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
    Route::delete('/api/notifications/{notificationId}', [App\Http\Controllers\NotificationController::class, 'deleteNotification']);
    
    // Rating & Reviews
    Route::get('/api/review-categories', [App\Http\Controllers\RatingReviewController::class, 'getReviewCategories']);
    Route::get('/api/bookings/{bookingId}/review-form', [App\Http\Controllers\RatingReviewController::class, 'getBookingForReview']);
    Route::post('/api/bookings/{bookingId}/review', [App\Http\Controllers\RatingReviewController::class, 'submitReview']);
    Route::get('/api/customer/reviews', [App\Http\Controllers\RatingReviewController::class, 'getCustomerReviews']);
    Route::get('/api/reviews/{reviewId}', [App\Http\Controllers\RatingReviewController::class, 'getReviewDetails']);
    
    // Internal notification creation (for admin/system use)
    Route::post('/api/notifications', [App\Http\Controllers\NotificationController::class, 'createNotification']);
});

// Debug authentication endpoint
Route::middleware(['auth'])->get('/api/debug-auth', function () {
    $user = auth()->user();
    return response()->json([
        'authenticated' => auth()->check(),
        'user_id' => $user ? $user->id : null,
        'user_name' => $user ? $user->name : null,
        'user_role' => $user ? $user->role : null,
        'session_id' => session()->getId(),
        'has_session' => session()->has('login_web_' . sha1('web')),
    ]);
});

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
