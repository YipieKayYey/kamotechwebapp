import { useState, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';
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
  CheckCircle,
  XCircle,
  AlertCircle,
  MessageCircle,
  Filter,
  Eye,
  EyeOff,
  MessageSquare,
  CreditCard,
  Wrench,
  X
} from 'lucide-react';
import axios from 'axios';
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

// Declare Botpress window object
declare global {
  interface Window {
    botpressWebChat?: {
      open: () => void;
      close: () => void;
      toggle: () => void;
      [key: string]: any;
    };
  }
}


export default function CustomerDashboard() {
  const { auth } = usePage<CustomerPageProps>().props;
  const [activeTab, setActiveTab] = useState('dashboard');
  const [bookingFilter, setBookingFilter] = useState('all');
  const [notificationFilter, setNotificationFilter] = useState('all');
  const [showAccountSettings, setShowAccountSettings] = useState(false);
  const [editingProfile, setEditingProfile] = useState(false);
  
  // API Data State
  const [dashboardData, setDashboardData] = useState<DashboardData | null>(null);
  const [allBookings, setAllBookings] = useState<BookingItem[]>([]);
  const [notifications, setNotifications] = useState<NotificationItem[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [loading, setLoading] = useState(true);
  const [bookingsLoading, setBookingsLoading] = useState(false);
  const [notificationsLoading, setNotificationsLoading] = useState(false);
  const [showCancelConfirm, setShowCancelConfirm] = useState(false);
  const [cancelTargetBooking, setCancelTargetBooking] = useState<string | number | null>(null);
  const [cancelSubmitting, setCancelSubmitting] = useState(false);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
  
  // Profile editing state
  const [profileData, setProfileData] = useState({
    name: auth.user.name,
    phone: auth.user.phone || '',
    house_no_street: auth.user.house_no_street || '',
    barangay: auth.user.barangay || '',
    city_municipality: auth.user.city_municipality || '',
    province: auth.user.province || '',
    nearest_landmark: auth.user.nearest_landmark || '',
  });
  const [profileSaving, setProfileSaving] = useState(false);
  const [profileErrors, setProfileErrors] = useState<Record<string, string>>({});

  // Security: inline change password state
  const [showPasswordEditor, setShowPasswordEditor] = useState(false);
  const [passwordData, setPasswordData] = useState({
    current_password: '',
    password: '',
    password_confirmation: '',
  });
  const [passwordErrors, setPasswordErrors] = useState<Record<string, string>>({});
  const [passwordSaving, setPasswordSaving] = useState(false);
  const [showCurrentPwd, setShowCurrentPwd] = useState(false);
  const [showNewPwd, setShowNewPwd] = useState(false);
  const [showConfirmPwd, setShowConfirmPwd] = useState(false);

  // Address autocomplete specifically for Account Settings (profileData)
  const [profileProvinceSuggestions, setProfileProvinceSuggestions] = useState<string[]>([]);
  const [profileMunicipalitySuggestions, setProfileMunicipalitySuggestions] = useState<string[]>([]);
  const [profileBarangaySuggestions, setProfileBarangaySuggestions] = useState<string[]>([]);

  const fetchProvinceSuggestions = async (q: string): Promise<string[]> => {
    if (!q || q.length < 1) return [];
    const res = await axios.get('/internal/locations/search', { params: { type: 'province', q, limit: 8 } });
    return (res.data as { id: number; text: string }[]).map(r => r.text);
  };

  const resolveProvinceId = async (provinceName: string): Promise<number | undefined> => {
    if (!provinceName) return undefined;
    const res = await axios.get('/internal/locations/search', { params: { type: 'province', q: provinceName, limit: 1 } });
    return res.data?.[0]?.id as number | undefined;
  };

  const fetchCitySuggestions = async (q: string, provinceName: string): Promise<string[]> => {
    if (!q || !provinceName) return [];
    const provId = await resolveProvinceId(provinceName);
    if (!provId) return [];
    const res = await axios.get('/internal/locations/search', { params: { type: 'city', parent_id: provId, q, limit: 10 } });
    return (res.data as { id: number; text: string }[]).map(r => r.text);
  };

  const resolveCityId = async (cityName: string, provinceName: string): Promise<number | undefined> => {
    if (!cityName || !provinceName) return undefined;
    const provId = await resolveProvinceId(provinceName);
    if (!provId) return undefined;
    const res = await axios.get('/internal/locations/search', { params: { type: 'city', parent_id: provId, q: cityName, limit: 1 } });
    return res.data?.[0]?.id as number | undefined;
  };

  const fetchBarangaySuggestions = async (q: string, cityName: string, provinceName: string): Promise<string[]> => {
    if (!q || !cityName || !provinceName) return [];
    const cityId = await resolveCityId(cityName, provinceName);
    if (!cityId) return [];
    const res = await axios.get('/internal/locations/search', { params: { type: 'barangay', parent_id: cityId, q, limit: 12 } });
    return (res.data as { id: number; text: string }[]).map(r => r.text);
  };

  const handleProfileAddressInputChange = async (field: 'province' | 'municipality' | 'barangay', value: string) => {
    setProfileData(prev => ({ ...prev, [field === 'municipality' ? 'city_municipality' : field]: value }));
    if (field === 'province') {
      setProfileProvinceSuggestions(await fetchProvinceSuggestions(value));
      // clear lower levels when province changes
      setProfileMunicipalitySuggestions([]);
      setProfileBarangaySuggestions([]);
    } else if (field === 'municipality') {
      setProfileMunicipalitySuggestions(await fetchCitySuggestions(value, profileData.province));
      setProfileBarangaySuggestions([]);
    } else if (field === 'barangay') {
      setProfileBarangaySuggestions(await fetchBarangaySuggestions(value, profileData.city_municipality, profileData.province));
    }
  };

  const selectProfileSuggestion = (field: 'province' | 'municipality' | 'barangay', value: string) => {
    setProfileData(prev => ({ ...prev, [field === 'municipality' ? 'city_municipality' : field]: value }));
    if (field === 'province') setProfileProvinceSuggestions([]);
    if (field === 'municipality') setProfileMunicipalitySuggestions([]);
    if (field === 'barangay') setProfileBarangaySuggestions([]);
  };
  
  // Load initial data
  useEffect(() => {
    loadDashboardData();
    // Configure axios defaults for CSRF & JSON responses (needed for password update)
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
      axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    axios.defaults.headers.common['Accept'] = 'application/json';
    axios.defaults.withCredentials = true;
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
    // Use named route to ensure correct URL and CSRF handling
    router.post(route('logout'));
  };

  const handleRequestCancellation = (bookingId: string | number) => {
    setCancelTargetBooking(bookingId);
    setShowCancelConfirm(true);
  };

  const confirmCancellation = async () => {
    if (!cancelTargetBooking) return;
    try {
      setCancelSubmitting(true);
      const result = await customerApi.requestCancellation(cancelTargetBooking);
      if (result.success) {
        setToast({ type: 'success', message: result.message || 'Cancellation requested.' });
        setShowCancelConfirm(false);
        setCancelTargetBooking(null);
        await refreshBookings();
      } else {
        setToast({ type: 'error', message: result.error || 'Failed to submit cancellation request. Please try again.' });
      }
    } catch (error: any) {
      const msg = (() => {
        if (error?.response?.status === 400) {
          const data = error.response.data || {};
          let m = data.error || 'Cannot cancel this booking.';
          if (data.hours_remaining !== undefined) m += ` Hours remaining: ${Math.round(data.hours_remaining)}`;
          return m;
        }
        if (error?.response?.status === 404) return 'Booking not found.';
        return 'An error occurred while processing your cancellation request. Please try again later.';
      })();
      setToast({ type: 'error', message: msg });
    } finally {
      setCancelSubmitting(false);
    }
  };

  const handleRateService = (bookingId: string) => {
    router.visit('/evaluation-feedback', {
      data: { bookingId }
    });
  };

  // Format phone number to Philippine format
  const formatPhilippinePhone = (input: string): string => {
    // Remove all non-digit characters
    const digits = input.replace(/\D/g, '');
    
    // If starts with 63, remove it
    let formatted = digits;
    if (formatted.startsWith('63')) {
      formatted = formatted.substring(2);
    }
    
    // If starts with 9 (without 0), add 0
    if (formatted.length > 0 && formatted[0] === '9') {
      formatted = '0' + formatted;
    }
    
    // Limit to 11 digits
    formatted = formatted.substring(0, 11);
    
    // Format as 0917-123-4567
    if (formatted.length > 4 && formatted.length <= 7) {
      formatted = formatted.substring(0, 4) + '-' + formatted.substring(4);
    } else if (formatted.length > 7) {
      formatted = formatted.substring(0, 4) + '-' + formatted.substring(4, 7) + '-' + formatted.substring(7);
    }
    
    return formatted;
  };

  // Profile management functions
  const handleProfileChange = (field: string, value: string) => {
    // Format phone number if it's the phone field
    if (field === 'phone') {
      value = formatPhilippinePhone(value);
    }
    
    setProfileData(prev => ({
      ...prev,
      [field]: value
    }));
    
    // Clear error when user starts typing
    if (profileErrors[field]) {
      setProfileErrors(prev => ({
        ...prev,
        [field]: ''
      }));
    }
  };

  const validateProfile = () => {
    const errors: Record<string, string> = {};
    
    if (!profileData.name.trim()) {
      errors.name = 'Name is required';
    }
    
    if (!profileData.phone || !profileData.phone.trim()) {
      errors.phone = 'Phone number is required';
    } else {
      // Remove all non-digit characters for validation
      const cleanPhone = profileData.phone.replace(/\D/g, '');
      
      // Check if it's a valid Philippine mobile number
      if (cleanPhone.length === 10 && cleanPhone.startsWith('9')) {
        // Valid format: 9XXXXXXXXX (will be formatted as 09XXXXXXXXX)
      } else if (cleanPhone.length === 11 && cleanPhone.startsWith('09')) {
        // Valid format: 09XXXXXXXXX
      } else if (cleanPhone.length === 12 && cleanPhone.startsWith('639')) {
        // Valid format: 639XXXXXXXXX (international format)
      } else {
        errors.phone = 'Please enter a valid Philippine mobile number (e.g., 09123456789)';
      }
    }
    
    // Address validation - at least basic address should be provided
    const addressFields = ['house_no_street', 'barangay', 'city_municipality', 'province'];
    const hasAddress = addressFields.some(field => profileData[field as keyof typeof profileData].trim());
    
    if (!hasAddress) {
      errors.address = 'Please provide at least your house number/street, barangay, city, or province';
    }
    
    setProfileErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const saveProfile = async () => {
    if (!validateProfile()) {
      setToast({ type: 'error', message: 'Please fix the errors below' });
      return;
    }

    try {
      setProfileSaving(true);
      const response = await fetch('/api/customer/profile', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify(profileData),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        setToast({ type: 'success', message: data.message || 'Profile updated successfully!' });
        setEditingProfile(false);
        setProfileErrors({});
        
        // Update the auth user data in the page props
        // This will be reflected in the UI on next page load
        window.location.reload(); // Simple refresh to update the user data
      } else {
        setToast({ type: 'error', message: data.error || 'Failed to update profile' });
        if (data.errors) {
          setProfileErrors(data.errors);
        }
      }
    } catch (error) {
      console.error('Error updating profile:', error);
      setToast({ type: 'error', message: 'An error occurred while updating your profile' });
    } finally {
      setProfileSaving(false);
    }
  };

  const cancelProfileEdit = () => {
    setProfileData({
      name: auth.user.name,
      phone: auth.user.phone || '',
      house_no_street: auth.user.house_no_street || '',
      barangay: auth.user.barangay || '',
      city_municipality: auth.user.city_municipality || '',
      province: auth.user.province || '',
      nearest_landmark: auth.user.nearest_landmark || '',
    });
    setProfileErrors({});
    setEditingProfile(false);
  };

  // Check if address is incomplete
  const isAddressIncomplete = () => {
    const addressFields = ['house_no_street', 'barangay', 'city_municipality', 'province'];
    return addressFields.every(field => !auth.user[field as keyof typeof auth.user]);
  };

  // Check if phone number is missing
  const isPhoneMissing = () => {
    return !auth.user.phone || auth.user.phone.trim() === '';
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

            {/* Phone Alert if missing */}
            {isPhoneMissing() && (
              <div className="address-alert-card" style={{ marginBottom: '1rem' }}>
                <div className="alert-content">
                  <div className="alert-icon">
                    <AlertCircle className="w-5 h-5 text-yellow-600" />
                  </div>
                  <div className="alert-text">
                    <p className="alert-title">Add Phone Number</p>
                    <p className="alert-description">Add your phone number so technicians can contact you</p>
                  </div>
                  <button 
                    className="alert-action-btn"
                    onClick={() => {
                      setActiveTab('settings');
                      setShowAccountSettings(true);
                      setEditingProfile(true);
                    }}
                  >
                    <Phone className="w-4 h-4" />
                    Add Phone
                  </button>
                </div>
              </div>
            )}

            {/* Address Alert if not complete */}
            {isAddressIncomplete() && (
              <div className="address-alert-card">
                <div className="alert-content">
                  <div className="alert-icon">
                    <AlertCircle className="w-5 h-5 text-yellow-600" />
                  </div>
                  <div className="alert-text">
                    <p className="alert-title">Complete Your Address</p>
                    <p className="alert-description">Add your address to make booking faster and more accurate</p>
                  </div>
                  <button 
                    className="alert-action-btn"
                    onClick={() => {
                      setActiveTab('settings');
                      setShowAccountSettings(true);
                      setEditingProfile(true);
                    }}
                  >
                    <MapPin className="w-4 h-4" />
                    Add Address
                  </button>
                </div>
              </div>
            )}

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
                      <span className="booking-date">{booking.scheduled_start || 'Not scheduled'}</span>
                      {booking.scheduled_end && (
                        <span className="booking-end">to {booking.scheduled_end}</span>
                      )}
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
                        <span>Start: {booking.scheduled_start || 'Not scheduled'}</span>
                      </div>
                      <div className="detail-item">
                        <Clock className="w-4 h-4" />
                        <span>End: {booking.scheduled_end || 'Not scheduled'}</span>
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
                      onClick={() => handleRequestCancellation(booking.id)}
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
        {/* Combined Support Card */}
        <div className="info-card" style={{ marginBottom: 24, padding: 20, borderRadius: 16, boxShadow: '0 10px 25px rgba(2,6,23,0.25)' }}>
          {/* header */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 12, paddingBottom: 12, borderBottom: '1px solid rgba(148,163,184,0.15)' }}>
            <div style={{ width: 36, height: 36, borderRadius: 9, background: '#0ea5e91a', display: 'grid', placeItems: 'center' }}>
              <MessageCircle className="w-5 h-5" />
            </div>
            <div>
              <h2 style={{ margin: 0, fontSize: 18, fontWeight: 700 }}>Kamotech Assistant & Support</h2>
              <p style={{ margin: 0, color: '#94a3b8', fontSize: 13 }}>Reach us directly or chat with our assistant for instant help.</p>
            </div>
          </div>

          {/* content */}
          <div style={{ display: 'flex', gap: 28, paddingTop: 16, flexWrap: 'wrap' }}>
            {/* Contact */}
            <div style={{ minWidth: 320, flex: '1 1 320px' }}>
              <div style={{ background: 'transparent', border: '1px solid rgba(148,163,184,0.15)', borderRadius: 12, padding: 16, display: 'grid', gap: 12 }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                  <div style={{ width: 36, height: 36, borderRadius: 8, background: '#0ea5e91a', display: 'grid', placeItems: 'center', color: '#0ea5e9' }}>
                    <Mail className="w-4 h-4" />
                  </div>
                  <div>
                    <div style={{ fontSize: 11, color: '#94a3b8', letterSpacing: '.08em' }}>EMAIL</div>
                    <a href="mailto:support@kamotech.com" style={{ fontWeight: 600, color: 'inherit' }}>support@kamotech.com</a>
                  </div>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                  <div style={{ width: 36, height: 36, borderRadius: 8, background: '#0ea5e91a', display: 'grid', placeItems: 'center', color: '#0ea5e9' }}>
                    <Phone className="w-4 h-4" />
                  </div>
                  <div>
                    <div style={{ fontSize: 11, color: '#94a3b8', letterSpacing: '.08em' }}>CONTACT NUMBER</div>
                    <a href="tel:+639074452484" style={{ fontWeight: 600, color: 'inherit' }}>(+63) 907-445-2484</a>
                  </div>
                </div>
              </div>
            </div>

            {/* Chatbot */}
            <div style={{ minWidth: 360, flex: '2 1 360px', display: 'grid', gap: 8 }}>
              <h3 style={{ margin: 0, fontWeight: 800, fontSize: 20 }}>Kamotech Assistant Chatbot</h3>
              <p style={{ margin: 0, color: '#94a3b8' }}>Ask FAQ questions and get guided help with service booking.</p>
              <div className="help-actions" style={{ marginTop: 4 }}>
                <button 
                  className="chat-quick-link"
                  onClick={() => {
                  console.log('Attempting to open Botpress chat...');
                  console.log('window.botpressWebChat:', window.botpressWebChat);
                  console.log('window.botpress:', (window as any).botpress);
                  
                  // Trigger Botpress chatbot
                  // Try multiple methods as Botpress v3 might use different APIs
                  if (window.botpressWebChat) {
                    console.log('Found window.botpressWebChat');
                    if (typeof window.botpressWebChat.open === 'function') {
                      window.botpressWebChat.open();
                    } else if (typeof window.botpressWebChat.toggle === 'function') {
                      window.botpressWebChat.toggle();
                    } else if (window.botpressWebChat.widget && typeof window.botpressWebChat.widget.open === 'function') {
                      window.botpressWebChat.widget.open();
                    } else {
                      console.log('No known method found on botpressWebChat:', window.botpressWebChat);
                    }
                  } else if ((window as any).botpress) {
                    console.log('Found window.botpress');
                    // Try the global botpress object
                    if (typeof (window as any).botpress.open === 'function') {
                      (window as any).botpress.open();
                    }
                  } else {
                    console.log('Botpress objects not found, trying to find button...');
                    // Try to trigger the default Botpress button click
                    const botpressButton = document.querySelector('[aria-label="Open chat window"]') || 
                                          document.querySelector('.bpw-widget-btn') ||
                                          document.querySelector('[id*="botpress"]') ||
                                          document.querySelector('[class*="botpress"]');
                    if (botpressButton) {
                      console.log('Found Botpress button:', botpressButton);
                      (botpressButton as HTMLElement).click();
                    } else {
                      console.log('Botpress chat button not found.');
                      // Try looking for all elements with Botpress-related attributes
                      const allElements = document.querySelectorAll('*');
                      const botpressElements = Array.from(allElements).filter(el => 
                        el.id?.includes('botpress') || 
                        el.className?.toString().includes('botpress') ||
                        el.className?.toString().includes('bpw')
                      );
                      console.log('Found Botpress-related elements:', botpressElements);
                    }
                  }
                  }}
                  title="Ask Kamotech Assistant"
                  style={{
                    background: '#0ea5e9',
                    color: 'white',
                    padding: '0.5rem 1rem',
                    borderRadius: 8,
                    fontWeight: 600
                  }}
                >
                  Ask Kamotech Assistant
                </button>
                <p style={{ marginTop: 8, fontSize: 12, color: '#64748b' }}>
                  Our chatbot can answer FAQ questions and guide you with service booking.
                </p>
              </div>
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
                  value={editingProfile ? profileData.name : auth.user.name}
                  onChange={(e) => handleProfileChange('name', e.target.value)}
                  disabled={!editingProfile}
                  className={`profile-input ${profileErrors.name ? 'error' : ''}`}
                />
                {profileErrors.name && (
                  <span className="error-message">{profileErrors.name}</span>
                )}
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
                disabled={true}
                className="profile-input"
                title="Email cannot be changed"
              />
            </div>
            <div className="field-group">
              <label>Mobile Phone</label>
              <input 
                type="tel" 
                value={editingProfile ? profileData.phone : (auth.user.phone || '')}
                onChange={(e) => handleProfileChange('phone', e.target.value)}
                disabled={!editingProfile}
                className={`profile-input ${profileErrors.phone ? 'error' : ''}`}
                placeholder="09XX-XXX-XXXX"
                maxLength={13}
              />
              {!profileErrors.phone && editingProfile && (
                <span className="field-hint" style={{ fontSize: '0.8rem', color: '#666' }}>
                  Philippine mobile format (e.g., 0917-123-4567)
                </span>
              )}
              {profileErrors.phone && (
                <span className="error-message">{profileErrors.phone}</span>
              )}
            </div>
          </div>
        </div>

        {/* Address Information */}
        <div className="settings-card">
          <div className="card-header">
            <h2 className="card-title">
              <MapPin className="w-5 h-5" />
              Address Information
              {isAddressIncomplete() && (
                <span className="incomplete-badge">Incomplete</span>
              )}
            </h2>
          </div>
          <div className="address-fields">
            {profileErrors.address && (
              <div className="address-error-message">
                <AlertCircle className="w-4 h-4" />
                {profileErrors.address}
              </div>
            )}
            <div className="field-group">
              <label>House No. & Street</label>
              <input 
                type="text" 
                value={editingProfile ? profileData.house_no_street : (auth.user.house_no_street || '')}
                onChange={(e) => handleProfileChange('house_no_street', e.target.value)}
                disabled={!editingProfile}
                className={`profile-input ${profileErrors.house_no_street ? 'error' : ''}`}
                placeholder="e.g., 123 Main Street"
              />
              {profileErrors.house_no_street && (
                <span className="error-message">{profileErrors.house_no_street}</span>
              )}
            </div>
            {/* Province first */}
            <div className="field-group">
              <label>Province</label>
              <div className="autocomplete-container">
                <input
                  type="text"
                  value={editingProfile ? profileData.province : (auth.user.province || '')}
                  onChange={(e) => handleProfileAddressInputChange('province', e.target.value)}
                  className={`profile-input ${profileErrors.province ? 'error' : ''}`}
                  placeholder="Enter province"
                  autoComplete="off"
                  disabled={!editingProfile}
                />
                {profileProvinceSuggestions && profileProvinceSuggestions.length > 0 && (
                  <div className="autocomplete-suggestions">
                    {profileProvinceSuggestions.map((s, i) => (
                      <div key={i} className="autocomplete-item" onClick={() => selectProfileSuggestion('province', s)}>
                        {s}
                      </div>
                    ))}
                  </div>
                )}
              </div>
              {profileErrors.province && (
                <span className="error-message">{profileErrors.province}</span>
              )}
            </div>

            {/* City next */}
            <div className="field-group">
              <label>City/Municipality</label>
              <div className="autocomplete-container">
                <input
                  type="text"
                  value={editingProfile ? profileData.city_municipality : (auth.user.city_municipality || '')}
                  onChange={(e) => handleProfileAddressInputChange('municipality', e.target.value)}
                  className={`profile-input ${profileErrors.city_municipality ? 'error' : ''}`}
                  placeholder="Enter city/municipality"
                  autoComplete="off"
                  disabled={!editingProfile}
                />
                {profileMunicipalitySuggestions && profileMunicipalitySuggestions.length > 0 && (
                  <div className="autocomplete-suggestions">
                    {profileMunicipalitySuggestions.map((s, i) => (
                      <div key={i} className="autocomplete-item" onClick={() => selectProfileSuggestion('municipality', s)}>
                        {s}
                      </div>
                    ))}
                  </div>
                )}
              </div>
              {profileErrors.city_municipality && (
                <span className="error-message">{profileErrors.city_municipality}</span>
              )}
            </div>

            {/* Barangay last */}
            <div className="field-group">
              <label>Barangay</label>
              <div className="autocomplete-container">
                <input
                  type="text"
                  value={editingProfile ? profileData.barangay : (auth.user.barangay || '')}
                  onChange={(e) => handleProfileAddressInputChange('barangay', e.target.value)}
                  className={`profile-input ${profileErrors.barangay ? 'error' : ''}`}
                  placeholder="Enter barangay"
                  autoComplete="off"
                  disabled={!editingProfile}
                />
                {profileBarangaySuggestions && profileBarangaySuggestions.length > 0 && (
                  <div className="autocomplete-suggestions">
                    {profileBarangaySuggestions.map((s, i) => (
                      <div key={i} className="autocomplete-item" onClick={() => selectProfileSuggestion('barangay', s)}>
                        {s}
                      </div>
                    ))}
                  </div>
                )}
              </div>
              {profileErrors.barangay && (
                <span className="error-message">{profileErrors.barangay}</span>
              )}
            </div>
            <div className="field-group">
              <label>Nearest Landmark (Optional)</label>
              <input 
                type="text" 
                value={editingProfile ? profileData.nearest_landmark : (auth.user.nearest_landmark || '')}
                onChange={(e) => handleProfileChange('nearest_landmark', e.target.value)}
                disabled={!editingProfile}
                className={`profile-input ${profileErrors.nearest_landmark ? 'error' : ''}`}
                placeholder="e.g., Near Jollibee, Beside City Hall"
              />
              {profileErrors.nearest_landmark && (
                <span className="error-message">{profileErrors.nearest_landmark}</span>
              )}
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
            {!showPasswordEditor ? (
              <button className="change-password-btn" onClick={() => setShowPasswordEditor(true)}>
                Change Password
              </button>
            ) : (
              <div className="password-form" style={{ display: 'grid', gap: '12px', maxWidth: 560 }}>
                <div className="field-group">
                  <label>Current Password</label>
                  <div style={{ position: 'relative' }}>
                    <input
                      type={showCurrentPwd ? 'text' : 'password'}
                      value={passwordData.current_password}
                      onChange={(e) => setPasswordData({ ...passwordData, current_password: e.target.value })}
                      className={`profile-input ${passwordErrors.current_password ? 'error' : ''}`}
                      placeholder="Current password"
                      style={{ paddingRight: 40 }}
                    />
                    <button
                      type="button"
                      onClick={() => setShowCurrentPwd((v) => !v)}
                      aria-label="Toggle current password visibility"
                      style={{ position: 'absolute', right: 8, top: '50%', transform: 'translateY(-50%)', background: 'transparent', border: 0, padding: 4, cursor: 'pointer' }}
                    >
                      {showCurrentPwd ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                    </button>
                  </div>
                  {passwordErrors.current_password && (
                    <span className="error-message">{passwordErrors.current_password}</span>
                  )}
                </div>
                <div className="field-group">
                  <label>New Password</label>
                  <div style={{ position: 'relative' }}>
                    <input
                      type={showNewPwd ? 'text' : 'password'}
                      value={passwordData.password}
                      onChange={(e) => setPasswordData({ ...passwordData, password: e.target.value })}
                      className={`profile-input ${passwordErrors.password ? 'error' : ''}`}
                      placeholder="New password"
                      style={{ paddingRight: 40 }}
                    />
                    <button
                      type="button"
                      onClick={() => setShowNewPwd((v) => !v)}
                      aria-label="Toggle new password visibility"
                      style={{ position: 'absolute', right: 8, top: '50%', transform: 'translateY(-50%)', background: 'transparent', border: 0, padding: 4, cursor: 'pointer' }}
                    >
                      {showNewPwd ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                    </button>
                  </div>
                  {passwordErrors.password && (
                    <span className="error-message">{passwordErrors.password}</span>
                  )}
                </div>
                <div className="field-group">
                  <label>Confirm Password</label>
                  <div style={{ position: 'relative' }}>
                    <input
                      type={showConfirmPwd ? 'text' : 'password'}
                      value={passwordData.password_confirmation}
                      onChange={(e) => setPasswordData({ ...passwordData, password_confirmation: e.target.value })}
                      className={`profile-input ${passwordErrors.password_confirmation ? 'error' : ''}`}
                      placeholder="Confirm new password"
                      style={{ paddingRight: 40 }}
                    />
                    <button
                      type="button"
                      onClick={() => setShowConfirmPwd((v) => !v)}
                      aria-label="Toggle confirm password visibility"
                      style={{ position: 'absolute', right: 8, top: '50%', transform: 'translateY(-50%)', background: 'transparent', border: 0, padding: 4, cursor: 'pointer' }}
                    >
                      {showConfirmPwd ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                    </button>
                  </div>
                  {passwordErrors.password_confirmation && (
                    <span className="error-message">{passwordErrors.password_confirmation}</span>
                  )}
                </div>

                <div className="settings-actions">
                  <button
                    className="save-btn"
                    onClick={async () => {
                      setPasswordErrors({});
                      setPasswordSaving(true);
                      try {
                        // Use POST + _method=PUT for maximum Laravel compatibility
                        await axios.post('/settings/password', {
                          ...passwordData,
                          _method: 'PUT',
                        });
                        setToast({ type: 'success', message: 'Password updated successfully.' });
                        setShowPasswordEditor(false);
                        setPasswordData({ current_password: '', password: '', password_confirmation: '' });
                      } catch (error: any) {
                        const data = error.response?.data;
                        if (data?.errors) {
                          setPasswordErrors(data.errors);
                          const firstMsg = Object.values<string>(data.errors)[0]?.[0] || 'Validation error';
                          setToast({ type: 'error', message: firstMsg });
                        } else if (data?.message) {
                          setToast({ type: 'error', message: data.message });
                        } else {
                          setToast({ type: 'error', message: 'Failed to update password.' });
                        }
                      } finally {
                        setPasswordSaving(false);
                      }
                    }}
                    disabled={passwordSaving}
                  >
                    {passwordSaving ? 'Saving...' : 'Save Password'}
                  </button>
                  <button className="cancel-btn" onClick={() => {
                    setShowPasswordEditor(false);
                    setPasswordErrors({});
                    setPasswordData({ current_password: '', password: '', password_confirmation: '' });
                  }} disabled={passwordSaving}>Cancel</button>
                </div>
              </div>
            )}
          </div>
        </div>

        {editingProfile && (
          <div className="settings-actions">
            <button 
              className="save-btn"
              onClick={saveProfile}
              disabled={profileSaving}
            >
              {profileSaving ? 'Saving...' : 'Save Changes'}
            </button>
            <button 
              className="cancel-btn"
              onClick={cancelProfileEdit}
              disabled={profileSaving}
            >
              Cancel
            </button>
          </div>
        )}
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
          {activeTab === 'help' && renderHelp()}
          {activeTab === 'settings' && renderAccountSettings()}
        </div>


        {/* Toast */}
        {toast && (
          <div 
            className={`toast ${toast.type}`} 
            style={{
              position: 'fixed',
              right: 16,
              top: 16,
              zIndex: 60,
              background: toast.type === 'success' ? '#16a34a' : '#dc2626',
              color: 'white',
              padding: '10px 14px',
              borderRadius: 8,
              boxShadow: '0 10px 15px -3px rgba(0,0,0,0.3)',
              fontWeight: 600,
              letterSpacing: 0.2,
            }}
            onAnimationEnd={() => setToast(null)}
          >
            {toast.message}
          </div>
        )}

        {/* Cancellation Confirm Modal */}
        {showCancelConfirm && (
          <div className="modal-overlay" style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)', zIndex: 50 }}>
            <div className="modal-container" style={{ maxWidth: 480, margin: '10vh auto', background: '#111827', borderRadius: 12, padding: 20, color: '#e5e7eb' }}>
              <div className="modal-header" style={{ marginBottom: 12 }}>
                <h3 style={{ margin: 0 }}>Request Cancellation</h3>
              </div>
              <div className="modal-body" style={{ lineHeight: 1.6 }}>
                <p>Are you sure you want to request cancellation for this booking?</p>
                <p style={{ fontSize: 13, color: '#9ca3af', marginTop: 8 }}>
                  Note: Cancellation requests must be made at least 48 hours before the scheduled service time.
                </p>
                <div style={{ marginTop: 12 }}>
                  <label style={{ display: 'block', fontSize: 13, color: '#9ca3af', marginBottom: 6 }}>Reason</label>
                  <select id="cancelReason" className="form-select" style={{ width: '100%' }} defaultValue="personal">
                    <option value="personal">Personal</option>
                    <option value="schedule_conflict">Schedule Conflict</option>
                    <option value="emergency">Emergency</option>
                    <option value="weather">Weather</option>
                    <option value="other">Other</option>
                  </select>
                  <textarea id="cancelDetails" className="form-input" placeholder="Briefly explain the reason (min 10 chars)" style={{ width: '100%', marginTop: 8 }} rows={3}></textarea>
                </div>
              </div>
              <div className="modal-actions" style={{ display: 'flex', gap: 8, justifyContent: 'flex-end', marginTop: 16 }}>
                <button className="btn btn-secondary" onClick={() => { setShowCancelConfirm(false); setCancelTargetBooking(null); }} disabled={cancelSubmitting}>Cancel</button>
                <button className="btn btn-primary" onClick={() => {
                  const reason = (document.getElementById('cancelReason') as HTMLSelectElement)?.value || 'personal';
                  const details = (document.getElementById('cancelDetails') as HTMLTextAreaElement)?.value || '';
                  if (details.trim().length < 10) {
                    setToast({ type: 'error', message: 'Please provide at least 10 characters for details.' });
                    return;
                  }
                  // attach payload globally for confirmCancellation read
                  (window as any).__cancelPayload = { reason_category: reason, reason_details: details };
                  confirmCancellation();
                }} disabled={cancelSubmitting}>
                  {cancelSubmitting ? 'Submitting...' : 'Confirm Request'}
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </>
  );
}
