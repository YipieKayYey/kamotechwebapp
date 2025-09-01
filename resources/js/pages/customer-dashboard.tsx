import { useState, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { 
  Bell, 
  Calendar, 
  Clock, 
  MapPin, 
  Plus, 
  Search, 
  Settings, 
  Star, 
  User, 
  LogOut,
  Phone,
  Mail,
  Home,
  CheckCircle,
  XCircle,
  AlertCircle,
  MessageCircle,
  Filter,
  Eye,
  MessageSquare,
  CreditCard,
  Wrench,
  X
} from 'lucide-react';
import { 
  customerApi, 
  notificationsApi, 
  handleApiError,
  type DashboardData,
  type BookingItem,
  type NotificationItem
} from '@/services/customerApi';
import { SharedData } from '@/types';

interface CustomerPageProps {
  auth: {
    user: {
      id: number;
      name: string;
      email: string;
      phone?: string;
      province?: string;
      city_municipality?: string;
      barangay?: string;
      house_no_street?: string;
      created_at: string;
      [key: string]: any;
    };
  };
  [key: string]: any;
}


export default function CustomerDashboard() {
  const { auth } = usePage<CustomerPageProps>().props;
  const [activeTab, setActiveTab] = useState('dashboard');
  const [bookingFilter, setBookingFilter] = useState('all');
  const [notificationFilter, setNotificationFilter] = useState('all');
  const [showAccountSettings, setShowAccountSettings] = useState(false);
  const [editingProfile, setEditingProfile] = useState(false);
  const [showChatbot, setShowChatbot] = useState(false);
  
  // API Data State
  const [dashboardData, setDashboardData] = useState<DashboardData | null>(null);
  const [allBookings, setAllBookings] = useState<BookingItem[]>([]);
  const [notifications, setNotifications] = useState<NotificationItem[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [loading, setLoading] = useState(true);
  const [bookingsLoading, setBookingsLoading] = useState(false);
  const [notificationsLoading, setNotificationsLoading] = useState(false);
  
  // Load initial data
  useEffect(() => {
    loadDashboardData();
    loadNotifications();
  }, []);
  
  const loadDashboardData = async () => {
    try {
      setLoading(true);
      const data = await customerApi.getDashboardData();
      setDashboardData(data);
      
      // Also load full booking history for the bookings tab
      const bookingsData = await customerApi.getBookingHistory({ per_page: 50 });
      setAllBookings(bookingsData.data);
    } catch (error) {
      console.error('Error loading dashboard data:', handleApiError(error));
    } finally {
      setLoading(false);
    }
  };
  
  const loadNotifications = async () => {
    try {
      setNotificationsLoading(true);
      const [notificationsData, unreadData] = await Promise.all([
        notificationsApi.getNotifications({ per_page: 20 }),
        notificationsApi.getUnreadCount()
      ]);
      setNotifications(notificationsData.data);
      setUnreadCount(unreadData.unread_count);
    } catch (error) {
      console.error('Error loading notifications:', handleApiError(error));
    } finally {
      setNotificationsLoading(false);
    }
  };
  
  const refreshBookings = async () => {
    try {
      setBookingsLoading(true);
      const [dashboardData, bookingsData] = await Promise.all([
        customerApi.getDashboardData(),
        customerApi.getBookingHistory({ per_page: 50 })
      ]);
      setDashboardData(dashboardData);
      setAllBookings(bookingsData.data);
    } catch (error) {
      console.error('Error refreshing bookings:', handleApiError(error));
    } finally {
      setBookingsLoading(false);
    }
  };
  
  const markNotificationAsRead = async (notificationId: number) => {
    try {
      await notificationsApi.markAsRead(notificationId);
      setNotifications(prev => 
        prev.map(notif => 
          notif.id === notificationId 
            ? { ...notif, is_read: true, read_at: new Date().toISOString() }
            : notif
        )
      );
      setUnreadCount(prev => Math.max(0, prev - 1));
    } catch (error) {
      console.error('Error marking notification as read:', handleApiError(error));
    }
  };
  
  const markAllNotificationsAsRead = async () => {
    try {
      await notificationsApi.markAllAsRead();
      setNotifications(prev => 
        prev.map(notif => ({ ...notif, is_read: true, read_at: new Date().toISOString() }))
      );
      setUnreadCount(0);
    } catch (error) {
      console.error('Error marking all notifications as read:', handleApiError(error));
    }
  };

  // Filter bookings based on selected filter
  const filteredBookings = allBookings.filter(booking => {
    if (bookingFilter === 'all') return true;
    if (bookingFilter === 'your-bookings') return ['pending', 'confirmed', 'in_progress'].includes(booking.status);
    if (bookingFilter === 'completed') return booking.status === 'completed';
    if (bookingFilter === 'cancelled') return ['cancelled', 'cancel_requested'].includes(booking.status);
    return true;
  });

  // Filter notifications based on selected filter
  const filteredNotifications = notifications.filter(notification => {
    if (notificationFilter === 'all') return true;
    return notification.type === notificationFilter;
  });

  // Calculate stats from real data
  const recentBookings = dashboardData?.recent_bookings || [];
  
  // Helper function to get upcoming bookings count
  const getUpcomingBookingsCount = () => {
    const upcoming = dashboardData?.upcoming_bookings;
    if (!upcoming) return 0;
    
    // If it's an array, return length
    if (Array.isArray(upcoming)) {
      return upcoming.length;
    }
    
    // If it's an object, count the keys
    if (typeof upcoming === 'object') {
      return Object.keys(upcoming).length;
    }
    
    return 0;
  };
  
  const upcomingBookingsCount = getUpcomingBookingsCount();

  const handleSignOut = () => {
    router.post('/logout');
  };

  const handleRequestCancellation = async (bookingNumber: string) => {
    try {
      // Show confirmation dialog
      const confirmed = window.confirm(
        'Are you sure you want to request cancellation for this booking? '
        + 'Please note that cancellation requests must be made at least 24 hours before the scheduled service time.'
      );
      
      if (!confirmed) {
        return;
      }

      const result = await customerApi.requestCancellation(bookingNumber);
      
      if (result.success) {
        // Success message
        alert(`✅ ${result.message}`);
        
        // Refresh bookings to show updated status
        await refreshBookings();
      } else {
        // Handle API errors
        alert(`❌ ${result.error || 'Failed to submit cancellation request. Please try again.'}`);
      }
    } catch (error: any) {
      console.error('Error requesting cancellation:', handleApiError(error));
      
      // Handle different error scenarios
      if (error.response?.status === 400) {
        const errorData = error.response.data;
        let errorMessage = errorData.error || 'Cannot cancel this booking.';
        
        if (errorData.hours_remaining !== undefined) {
          errorMessage += ` Hours remaining: ${Math.round(errorData.hours_remaining)}`;
        }
        
        alert(`❌ ${errorMessage}`);
      } else if (error.response?.status === 404) {
        alert('❌ Booking not found.');
      } else {
        alert('❌ An error occurred while processing your cancellation request. Please try again later.');
      }
    }
  };

  const handleRateService = (bookingId: string) => {
    router.visit('/evaluation-feedback', {
      data: { bookingId }
    });
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending':
        return 'status-pending';
      case 'confirmed':
        return 'status-confirmed';
      case 'in_progress':
        return 'status-in-progress';
      case 'completed':
        return 'status-completed';
      case 'cancelled':
        return 'status-cancelled';
      case 'cancel_requested':
        return 'status-cancel-requested';
      default:
        return 'status-default';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'pending':
        return <Clock className="w-4 h-4" />;
      case 'confirmed':
        return <CheckCircle className="w-4 h-4" />;
      case 'in_progress':
        return <AlertCircle className="w-4 h-4" />;
      case 'completed':
        return <CheckCircle className="w-4 h-4" />;
      case 'cancelled':
        return <XCircle className="w-4 h-4" />;
      case 'cancel_requested':
        return <AlertCircle className="w-4 h-4" />;
      default:
        return <AlertCircle className="w-4 h-4" />;
    }
  };

  const getNotificationIcon = (type: string) => {
    switch (type) {
      case 'booking_confirmation':
        return <Calendar className="w-5 h-5 text-blue-500" />;
      case 'reminder':
        return <Clock className="w-5 h-5 text-orange-500" />;
      case 'status_update':
        return <MessageSquare className="w-5 h-5 text-green-500" />;
      case 'promotion':
        return <CreditCard className="w-5 h-5 text-purple-500" />;
      default:
        return <Bell className="w-5 h-5 text-gray-500" />;
    }
  };

  const renderDashboard = () => {
    if (loading) {
      return (
        <div className="customer-dashboard-content">
          <div className="dashboard-overview">
            <div className="loading-state">
              <div className="loading-spinner"></div>
              <p>Loading dashboard...</p>
            </div>
          </div>
        </div>
      );
    }
    
    return (
      <div className="customer-dashboard-content">
        {/* Dashboard Overview Section */}
        <div className="dashboard-overview">
          {/* Left Side - Customer Information */}
          <div className="dashboard-left">
            <div className="info-card customer-info-compact">
              <div className="card-header">
                <h2 className="card-title">
                  <User className="w-5 h-5" />
                  Customer Information
                </h2>
                <button 
                  className="edit-btn"
                  onClick={() => setShowAccountSettings(true)}
                >
                  <Settings className="w-4 h-4" />
                  Edit
                </button>
              </div>
              <div className="customer-info-compact-grid">
                <div className="info-item-compact">
                  <div className="info-icon">
                    <User className="w-4 h-4" />
                  </div>
                  <div className="info-details">
                    <span className="info-label">Full Name</span>
                    <span className="info-value">{auth.user.name}</span>
                  </div>
                </div>
                <div className="info-item-compact">
                  <div className="info-icon">
                    <Phone className="w-4 h-4" />
                  </div>
                  <div className="info-details">
                    <span className="info-label">Contact Number</span>
                    <span className="info-value">{auth.user.phone || 'Not provided'}</span>
                  </div>
                </div>
                <div className="info-item-compact">
                  <div className="info-icon">
                    <Mail className="w-4 h-4" />
                  </div>
                  <div className="info-details">
                    <span className="info-label">Email Address</span>
                    <span className="info-value">{auth.user.email}</span>
                  </div>
                </div>
                <div className="info-item-compact">
                  <div className="info-icon">
                    <Home className="w-4 h-4" />
                  </div>
                  <div className="info-details">
                    <span className="info-label">Home Address</span>
                    <span className="info-value">
                      {[auth.user.house_no_street, auth.user.barangay, auth.user.city_municipality, auth.user.province]
                        .filter(Boolean)
                        .join(', ') || 'Not provided'}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Right Side - Dashboard Stats */}
          <div className="dashboard-right">
            <div className="dashboard-stats-compact">
              <div className="stat-card compact">
                <div className="stat-icon upcoming">
                  <Calendar className="w-5 h-5" />
                </div>
                <div className="stat-info">
                  <span className="stat-number">{upcomingBookingsCount}</span>
                  <span className="stat-label">Upcoming Bookings</span>
                </div>
              </div>
              <div className="stat-card compact">
                <div className="stat-icon completed">
                  <CheckCircle className="w-5 h-5" />
                </div>
                <div className="stat-info">
                  <span className="stat-number">{dashboardData?.stats?.completed_bookings || 0}</span>
                  <span className="stat-label">Completed Services</span>
                </div>
              </div>
              <div className="stat-card compact">
                <div className="stat-icon notifications">
                  <Bell className="w-5 h-5" />
                </div>
                <div className="stat-info">
                  <span className="stat-number">{unreadCount}</span>
                  <span className="stat-label">New Notifications</span>
                </div>
              </div>
            </div>

            {/* Quick Actions */}
            <div className="quick-actions-compact">
              <div className="action-buttons-compact">
                <button 
                  className="action-btn compact primary"
                  onClick={() => setActiveTab('bookings')}
                >
                  <Eye className="w-4 h-4" />
                  View All Bookings
                </button>
                <Link href="/booking" className="action-btn compact secondary">
                  <Plus className="w-4 h-4" />
                  Book a Service
                </Link>
              </div>
            </div>
          </div>
        </div>

        {/* Recent Bookings */}
        <div className="info-card recent-bookings-card">
          <div className="card-header">
            <h2 className="card-title">
              <Clock className="w-5 h-5" />
              Recent Booking History
            </h2>
            <button 
              className="view-all-btn"
              onClick={() => setActiveTab('bookings')}
            >
              View All
            </button>
          </div>
          <div className="recent-bookings-list">
            {recentBookings.length > 0 ? (
              recentBookings.map((booking) => (
                <div key={booking.id} className="recent-booking-item">
                  <div className="booking-service">
                    <span className="service-type">{booking.service}</span>
                    <div className="booking-details">
                      <span className="booking-date">{new Date(booking.scheduled_date).toLocaleDateString()}</span>
                      <span className="booking-timeslot">{booking.timeslot}</span>
                      {booking.technician_name && (
                        <span className="technician-name">Technician: {booking.technician_name}</span>
                      )}
                      {booking.technician_phone && (
                        <span className="technician-contact">Contact: {booking.technician_phone}</span>
                      )}
                    </div>
                  </div>
                  <div className="booking-status">
                    <span className={`status-badge ${getStatusColor(booking.status)}`}>
                      {getStatusIcon(booking.status)}
                      {booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                    </span>
                  </div>
                </div>
              ))
            ) : (
              <div className="no-bookings-message">
                <p>No recent bookings found.</p>
                <Link href="/booking" className="book-now-link">
                  <Plus className="w-4 h-4" />
                  Book your first service
                </Link>
              </div>
            )}
          </div>
        </div>
      </div>
    );
  };

  const renderBookings = () => {
    if (bookingsLoading && allBookings.length === 0) {
      return (
        <div className="customer-dashboard-content">
          <div className="loading-state">
            <div className="loading-spinner"></div>
            <p>Loading bookings...</p>
          </div>
        </div>
      );
    }
    
    return (
      <div className="customer-dashboard-content">
        <div className="bookings-header">
          <div className="bookings-title-section">
            <h1 className="page-title">My Bookings</h1>
            <Link href="/booking" className="book-service-btn">
              <Plus className="w-5 h-5" />
              Book a Service
            </Link>
          </div>
          
          {/* Booking Filters */}
          <div className="booking-filters">
            <button 
              className={`filter-btn ${bookingFilter === 'all' ? 'active' : ''}`}
              onClick={() => setBookingFilter('all')}
              disabled={bookingsLoading}
            >
              All Bookings
            </button>
            <button 
              className={`filter-btn ${bookingFilter === 'your-bookings' ? 'active' : ''}`}
              onClick={() => setBookingFilter('your-bookings')}
              disabled={bookingsLoading}
            >
              Upcoming
            </button>
            <button 
              className={`filter-btn ${bookingFilter === 'completed' ? 'active' : ''}`}
              onClick={() => setBookingFilter('completed')}
              disabled={bookingsLoading}
            >
              Completed
            </button>
            <button 
              className={`filter-btn ${bookingFilter === 'cancelled' ? 'active' : ''}`}
              onClick={() => setBookingFilter('cancelled')}
              disabled={bookingsLoading}
            >
              Cancelled
            </button>
          </div>
        </div>

        {/* Bookings List */}
        <div className="bookings-list">
          {filteredBookings.length > 0 ? (
            filteredBookings.map((booking) => (
              <div key={booking.id} className="booking-card">
                <div className="booking-card-header">
                  <div className="booking-id-section">
                    <span className="booking-id">#{booking.booking_number}</span>
                    <span className={`status-badge ${getStatusColor(booking.status)}`}>
                      {getStatusIcon(booking.status)}
                      {booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                    </span>
                  </div>
                  <div className="booking-price">₱{booking.total_amount.toLocaleString()}</div>
                </div>
                
                <div className="booking-card-content">
                  <div className="booking-main-info">
                    <h3 className="service-type">{booking.service}</h3>
                    <div className="booking-details-grid">
                      <div className="detail-item">
                        <Calendar className="w-4 h-4" />
                        <span>{new Date(booking.scheduled_date).toLocaleDateString()}</span>
                      </div>
                      <div className="detail-item">
                        <Clock className="w-4 h-4" />
                        <span>{booking.timeslot}</span>
                      </div>
                      <div className="detail-item">
                        <MapPin className="w-4 h-4" />
                        <span>{booking.service_location}</span>
                      </div>
                      <div className="detail-item">
                        <Wrench className="w-4 h-4" />
                        <span>{booking.aircon_type} - {booking.number_of_units} unit{booking.number_of_units > 1 ? 's' : ''}</span>
                      </div>
                      {booking.ac_brand && (
                        <div className="detail-item">
                          <span className="brand-label">Brand:</span>
                          <span>{booking.ac_brand}</span>
                        </div>
                      )}
                      <div className="detail-item">
                        <User className="w-4 h-4" />
                        <span>Technician: {booking.technician_name}</span>
                      </div>
                      {booking.technician_phone && (
                        <div className="detail-item">
                          <Phone className="w-4 h-4" />
                          <span>Contact: {booking.technician_phone}</span>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
                
                <div className="booking-card-actions">
                  {['pending', 'confirmed'].includes(booking.status) && (
                    <button 
                      className="action-btn cancel"
                      onClick={() => handleRequestCancellation(booking.booking_number)}
                    >
                      Request Cancellation
                    </button>
                  )}
                  {booking.status === 'cancel_requested' && (
                    <div className="cancellation-status">
                      <AlertCircle className="w-4 h-4 text-orange-500" />
                      <span className="text-orange-600 font-medium">Cancellation Pending</span>
                    </div>
                  )}
                  {booking.status === 'completed' && booking.can_review && (
                    <button 
                      className="action-btn rate"
                      onClick={() => handleRateService(booking.id.toString())}
                    >
                      <Star className="w-4 h-4" />
                      Rate Service
                    </button>
                  )}
                  {booking.status === 'completed' && booking.has_review && (
                    <div className="review-status">
                      <Star className="w-4 h-4 text-yellow-500" />
                      {booking.review_rating && (
                        <span>Rated: {booking.review_rating}/5</span>
                      )}
                    </div>
                  )}
                </div>
              </div>
            ))
          ) : (
            <div className="no-bookings-message">
              <p>No bookings found for the selected filter.</p>
              <Link href="/booking" className="book-now-link">
                <Plus className="w-4 h-4" />
                Book a service
              </Link>
            </div>
          )}
        </div>
      </div>
    );
  };

  const renderNotifications = () => {
    if (notificationsLoading && notifications.length === 0) {
      return (
        <div className="customer-dashboard-content">
          <div className="loading-state">
            <div className="loading-spinner"></div>
            <p>Loading notifications...</p>
          </div>
        </div>
      );
    }
    
    return (
      <div className="customer-dashboard-content">
        <div className="notifications-header">
          <h1 className="page-title">Notifications</h1>
          <div className="notifications-actions">
            <button 
              className="mark-all-read-btn"
              onClick={markAllNotificationsAsRead}
              disabled={unreadCount === 0 || notificationsLoading}
            >
              Mark all as read ({unreadCount})
            </button>
          </div>
        </div>

        {/* Notification Filters */}
        <div className="notification-filters">
          <button 
            className={`filter-btn ${notificationFilter === 'all' ? 'active' : ''}`}
            onClick={() => setNotificationFilter('all')}
            disabled={notificationsLoading}
          >
            <Filter className="w-4 h-4" />
            All
          </button>
          <button 
            className={`filter-btn ${notificationFilter === 'booking_confirmation' ? 'active' : ''}`}
            onClick={() => setNotificationFilter('booking_confirmation')}
            disabled={notificationsLoading}
          >
            <Calendar className="w-4 h-4" />
            Booking
          </button>
          <button 
            className={`filter-btn ${notificationFilter === 'status_update' ? 'active' : ''}`}
            onClick={() => setNotificationFilter('status_update')}
            disabled={notificationsLoading}
          >
            <MessageSquare className="w-4 h-4" />
            Updates
          </button>
          <button 
            className={`filter-btn ${notificationFilter === 'promotion' ? 'active' : ''}`}
            onClick={() => setNotificationFilter('promotion')}
            disabled={notificationsLoading}
          >
            <CreditCard className="w-4 h-4" />
            Promo
          </button>
        </div>

        {/* Notifications List */}
        <div className="notifications-list">
          {filteredNotifications.length > 0 ? (
            filteredNotifications.map((notification) => (
              <div 
                key={notification.id} 
                className={`notification-card ${!notification.is_read ? 'unread' : ''}`}
                onClick={() => {
                  if (!notification.is_read) {
                    markNotificationAsRead(notification.id);
                  }
                }}
                style={{ cursor: !notification.is_read ? 'pointer' : 'default' }}
              >
                <div className="notification-icon">
                  {getNotificationIcon(notification.type)}
                </div>
                <div className="notification-content">
                  <h3 className="notification-title">{notification.title}</h3>
                  <p className="notification-message">{notification.message}</p>
                  <span className="notification-date">
                    {notification.time_ago}
                  </span>
                </div>
                {!notification.is_read && (
                  <div className="unread-indicator"></div>
                )}
              </div>
            ))
          ) : (
            <div className="no-notifications-message">
              <Bell className="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <p>No notifications found.</p>
              {notificationFilter !== 'all' && (
                <button 
                  className="clear-filter-btn"
                  onClick={() => setNotificationFilter('all')}
                >
                  Clear filter
                </button>
              )}
            </div>
          )}
        </div>
      </div>
    );
  };

  const renderHelp = () => (
    <div className="customer-dashboard-content">
      <div className="help-header">
        <h1 className="page-title">Help & Support</h1>
      </div>

      <div className="help-content">
        <div className="coming-soon-card">
          <div className="coming-soon-icon">
            <MessageCircle className="w-16 h-16" />
          </div>
          <div className="coming-soon-content">
            <h2 className="coming-soon-title">Help & Support</h2>
            <p className="coming-soon-subtitle">
              We're working hard to bring you comprehensive help and support features.
            </p>
            <div className="coming-soon-features">
              <div className="feature-item">
                <CheckCircle className="w-5 h-5" />
                <span>Frequently Asked Questions (FAQ)</span>
              </div>
              <div className="feature-item">
                <CheckCircle className="w-5 h-5" />
                <span>Live Chat Support</span>
              </div>
              <div className="feature-item">
                <CheckCircle className="w-5 h-5" />
                <span>Video Tutorials</span>
              </div>
              <div className="feature-item">
                <CheckCircle className="w-5 h-5" />
                <span>Contact Support Team</span>
              </div>
            </div>
            <div className="coming-soon-status">
              <span className="status-badge">Coming Soon</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  const renderAccountSettings = () => (
    <div className="customer-dashboard-content">
      <div className="settings-header">
        <h1 className="page-title">Account Settings</h1>
      </div>

      <div className="settings-sections">
        {/* Profile Section */}
        <div className="settings-card">
          <div className="card-header">
            <h2 className="card-title">
              <User className="w-5 h-5" />
              Profile Information
            </h2>
            <button 
              className="edit-btn"
              onClick={() => setEditingProfile(!editingProfile)}
            >
              {editingProfile ? 'Cancel' : 'Edit'}
            </button>
          </div>
          <div className="profile-section">
            <div className="profile-picture-section">
              <div className="profile-picture">
                {auth.user.avatar ? (
                  <img src={auth.user.avatar} alt="Profile" />
                ) : (
                  <User className="w-12 h-12" />
                )}
              </div>
              {editingProfile && (
                <button className="change-picture-btn">Change Picture</button>
              )}
            </div>
            <div className="profile-fields">
              <div className="field-group">
                <label>Full Name</label>
                <input 
                  type="text" 
                  value={auth.user.name}
                  disabled={!editingProfile}
                  className="profile-input"
                />
              </div>
            </div>
          </div>
        </div>

        {/* Contact Information */}
        <div className="settings-card">
          <div className="card-header">
            <h2 className="card-title">
              <Phone className="w-5 h-5" />
              Contact Information
            </h2>
          </div>
          <div className="contact-fields">
            <div className="field-group">
              <label>Email Address</label>
              <input 
                type="email" 
                value={auth.user.email}
                disabled={!editingProfile}
                className="profile-input"
              />
            </div>
            <div className="field-group">
              <label>Mobile Phone</label>
              <input 
                type="tel" 
                value={auth.user.phone || ''}
                disabled={!editingProfile}
                className="profile-input"
              />
            </div>
          </div>
        </div>

        {/* Address Information */}
        <div className="settings-card">
          <div className="card-header">
            <h2 className="card-title">
              <Home className="w-5 h-5" />
              Home Address
            </h2>
          </div>
          <div className="address-fields">
            <div className="address-row">
              <div className="field-group">
                <label>House Number</label>
                <input 
                  type="text" 
                  value={auth.user.house_no_street || ''}
                  disabled={!editingProfile}
                  className="profile-input"
                />
              </div>
              <div className="field-group">
                <label>Street</label>
                <input 
                  type="text" 
                  value={auth.user.house_no_street || ''}
                  disabled={!editingProfile}
                  className="profile-input"
                />
              </div>
            </div>
            <div className="address-row">
              <div className="field-group">
                <label>Barangay</label>
                <input 
                  type="text" 
                  value={auth.user.barangay || ''}
                  disabled={!editingProfile}
                  className="profile-input"
                />
              </div>
              <div className="field-group">
                <label>Municipality</label>
                <input 
                  type="text" 
                  value={auth.user.city_municipality || ''}
                  disabled={!editingProfile}
                  className="profile-input"
                />
              </div>
            </div>
          </div>
        </div>

        {/* Password Section */}
        <div className="settings-card">
          <div className="card-header">
            <h2 className="card-title">
              <Settings className="w-5 h-5" />
              Security
            </h2>
          </div>
          <div className="security-section">
            <button className="change-password-btn">
              Change Password
            </button>
          </div>
        </div>

        {editingProfile && (
          <div className="settings-actions">
            <button className="save-btn">Save Changes</button>
            <button 
              className="cancel-btn"
              onClick={() => setEditingProfile(false)}
            >
              Cancel
            </button>
          </div>
        )}
      </div>
    </div>
  );

  const renderChatbot = () => (
    <div className="chatbot-overlay">
      <div className="chatbot-container">
        <div className="chatbot-header">
          <h3>Customer Support</h3>
          <button 
            className="chatbot-close"
            onClick={() => setShowChatbot(false)}
          >
            <X className="w-5 h-5" />
          </button>
        </div>
        <div className="chatbot-content">
          <div className="chatbot-message-area">
            <div className="bot-message">
              <MessageCircle className="w-5 h-5" />
              <p>Hello! I'm here to help you with:</p>
              <ul>
                <li>Booking policies and procedures</li>
                <li>Available services and supported brands</li>
                <li>Scheduling, rescheduling, or canceling bookings</li>
                <li>Account-related assistance</li>
                <li>Emergency service requests</li>
              </ul>
              <p>How can I assist you today?</p>
            </div>
          </div>
          <div className="chatbot-input-area">
            <input 
              type="text" 
              placeholder="Type your message..."
              className="chatbot-input"
            />
            <button className="chatbot-send">Send</button>
          </div>
        </div>
      </div>
    </div>
  );

  return (
    <>
      <Head title="Customer Dashboard" />
      
      <div className="customer-dashboard">
        {/* Header */}
        <header className="dashboard-header">
          <div className="header-container">
            <div className="header-logo">
              <Link href="/">
                <img src="/images/logo-main.png" alt="Kamotech Logo" className="logo-image" />
              </Link>
            </div>
            <div className="header-user">
              <span className="user-welcome">Welcome, {auth.user.name}</span>
              <button 
                className="header-sign-out-btn"
                onClick={handleSignOut}
                title="Sign Out"
              >
                <LogOut className="w-5 h-5" />
                Sign Out
              </button>
            </div>
          </div>
        </header>

        {/* Navigation Tabs */}
        <div className="dashboard-nav">
          <div className="nav-container">
            <button 
              className={`nav-tab ${activeTab === 'dashboard' ? 'active' : ''}`}
              onClick={() => setActiveTab('dashboard')}
            >
              <User className="w-5 h-5" />
              Dashboard
            </button>
            <button 
              className={`nav-tab ${activeTab === 'bookings' ? 'active' : ''}`}
              onClick={() => setActiveTab('bookings')}
            >
              <Calendar className="w-5 h-5" />
              My Bookings
            </button>
            <button 
              className={`nav-tab ${activeTab === 'notifications' ? 'active' : ''}`}
              onClick={() => setActiveTab('notifications')}
            >
              <Bell className="w-5 h-5" />
              Notifications
              {unreadCount > 0 && (
                <span className="notification-count">{unreadCount}</span>
              )}
            </button>
            <button 
              className={`nav-tab ${activeTab === 'help' ? 'active' : ''}`}
              onClick={() => setActiveTab('help')}
            >
              <MessageCircle className="w-5 h-5" />
              Help
            </button>
            <button 
              className={`nav-tab ${activeTab === 'settings' ? 'active' : ''}`}
              onClick={() => setActiveTab('settings')}
            >
              <Settings className="w-5 h-5" />
              Account Settings
            </button>
          </div>
        </div>

        {/* Main Content */}
        <div className="dashboard-main">
          {activeTab === 'dashboard' && renderDashboard()}
          {activeTab === 'bookings' && renderBookings()}
          {activeTab === 'notifications' && renderNotifications()}
          {activeTab === 'help' && renderHelp()}
          {activeTab === 'settings' && renderAccountSettings()}
        </div>

        {/* Chatbot Button */}
        <button 
          className="chatbot-toggle"
          onClick={() => setShowChatbot(true)}
          title="Customer Support"
        >
          <MessageCircle className="w-6 h-6" />
        </button>

        {/* Chatbot Modal */}
        {showChatbot && renderChatbot()}
      </div>
    </>
  );
}
