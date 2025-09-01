import axios from 'axios';

// Configure axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.withCredentials = true;

// Get CSRF token from meta tag
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
}

export interface DashboardStats {
    total_bookings: number;
    completed_bookings: number;
    pending_bookings: number;
    total_spent: string;
}

export interface BookingItem {
    id: number;
    booking_number: string;
    service: string;
    aircon_type: string;
    number_of_units: number;
    ac_brand?: string;
    scheduled_date: string;
    timeslot: string;
    status: string;
    total_amount: number;
    payment_status: string;
    technician_name: string;
    technician_phone?: string;
    service_location: string;
    has_review: boolean;
    can_review: boolean;
    review_rating?: number;
    created_at: string;
}

export interface UpcomingBooking {
    id: number;
    booking_number: string;
    service: string;
    scheduled_date: string;
    timeslot: string;
    status: string;
    technician_name: string;
    technician_phone?: string;
}

export interface DashboardData {
    stats: DashboardStats;
    recent_bookings: BookingItem[];
    upcoming_bookings: UpcomingBooking[];
}

export interface NotificationItem {
    id: number;
    type: string;
    title: string;
    message: string;
    data: any;
    is_read: boolean;
    read_at?: string;
    created_at: string;
    time_ago: string;
}

export interface ReviewCategory {
    id: number;
    name: string;
    description: string;
}

export interface CategoryScore {
    category_id: number;
    score: number;
}

export interface BookingForReview {
    id: number;
    booking_number: string;
    service: {
        name: string;
        description: string;
    };
    aircon_type: {
        name: string;
        description: string;
    };
    number_of_units: number;
    ac_brand?: string;
    scheduled_date: string;
    timeslot: string;
    total_amount: number;
    technician: {
        name: string;
        specialization: string;
        experience_years: number;
    };
    service_location: string;
    completed_date: string;
}

export interface ReviewSubmission {
    category_scores: CategoryScore[];
    review_text?: string;
}

// Customer Dashboard API
export const customerApi = {
    // Get dashboard data
    async getDashboardData(): Promise<DashboardData> {
        const response = await axios.get('/api/customer/dashboard');
        return response.data;
    },

    // Get booking history with pagination
    async getBookingHistory(params: {
        per_page?: number;
        status?: string;
        page?: number;
    } = {}): Promise<{
        data: BookingItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    }> {
        const response = await axios.get('/api/customer/bookings', { params });
        return response.data;
    },

    // Get specific booking details
    async getBookingDetails(bookingId: number): Promise<any> {
        const response = await axios.get(`/api/customer/bookings/${bookingId}`);
        return response.data;
    },

    // Request booking cancellation
    async requestCancellation(bookingNumber: string): Promise<{
        success: boolean;
        message: string;
        booking?: {
            id: number;
            booking_number: string;
            status: string;
            scheduled_date: string;
        };
        error?: string;
        current_status?: string;
        hours_remaining?: number;
    }> {
        const response = await axios.post(`/api/customer/bookings/${bookingNumber}/cancel-request`);
        return response.data;
    },
};

// Notifications API
export const notificationsApi = {
    // Get notifications
    async getNotifications(params: {
        per_page?: number;
        type?: string;
        unread_only?: boolean;
        page?: number;
    } = {}): Promise<{
        data: NotificationItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    }> {
        const response = await axios.get('/api/notifications', { params });
        return response.data;
    },

    // Get unread count
    async getUnreadCount(): Promise<{ unread_count: number }> {
        const response = await axios.get('/api/notifications/unread-count');
        return response.data;
    },

    // Get notification stats
    async getNotificationStats(): Promise<{
        total_notifications: number;
        unread_notifications: number;
        read_notifications: number;
        by_type: Record<string, number>;
    }> {
        const response = await axios.get('/api/notifications/stats');
        return response.data;
    },

    // Mark notification as read
    async markAsRead(notificationId: number): Promise<{ message: string }> {
        const response = await axios.post(`/api/notifications/${notificationId}/mark-read`);
        return response.data;
    },

    // Mark all notifications as read
    async markAllAsRead(): Promise<{ message: string }> {
        const response = await axios.post('/api/notifications/mark-all-read');
        return response.data;
    },

    // Delete notification
    async deleteNotification(notificationId: number): Promise<{ message: string }> {
        const response = await axios.delete(`/api/notifications/${notificationId}`);
        return response.data;
    },
};

// Rating & Review API
export const reviewApi = {
    // Get review categories
    async getReviewCategories(): Promise<ReviewCategory[]> {
        const response = await axios.get('/api/review-categories');
        return response.data;
    },

    // Get booking for review
    async getBookingForReview(bookingId: number): Promise<BookingForReview> {
        const response = await axios.get(`/api/bookings/${bookingId}/review-form`);
        return response.data;
    },

    // Submit review
    async submitReview(bookingId: number, reviewData: ReviewSubmission): Promise<{
        message: string;
        review: any;
    }> {
        const response = await axios.post(`/api/bookings/${bookingId}/review`, reviewData);
        return response.data;
    },

    // Get customer reviews
    async getCustomerReviews(params: {
        per_page?: number;
        page?: number;
    } = {}): Promise<{
        data: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    }> {
        const response = await axios.get('/api/customer/reviews', { params });
        return response.data;
    },

    // Get review details
    async getReviewDetails(reviewId: number): Promise<any> {
        const response = await axios.get(`/api/reviews/${reviewId}`);
        return response.data;
    },
};

// Error handling helper
export const handleApiError = (error: any) => {
    if (error.response) {
        // Server responded with error status
        console.error('API Error:', error.response.data);
        return error.response.data.error || 'An error occurred';
    } else if (error.request) {
        // Request was made but no response received
        console.error('Network Error:', error.request);
        return 'Network error. Please check your connection.';
    } else {
        // Something else happened
        console.error('Error:', error.message);
        return 'An unexpected error occurred';
    }
};