import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
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

// Sample data - this would come from your backend
const customerData = {
  id: 1,
  name: 'Juan Dela Cruz',
  email: 'juan.delacruz@example.com',
  phone: '+63 912 345 6789',
  address: {
    houseNumber: '123',
    street: 'Main Street',
    barangay: 'Poblacion',
    municipality: 'Balanga',
    province: 'Bataan'
  },
  profilePicture: null,
  joinedDate: '2024-01-15'
};

const sampleBookings = [
  {
    id: 'BK001',
    serviceType: 'AC Cleaning',
    date: '2024-02-15',
    time: '9:00 AM - 12:00 PM',
    location: '123 Main St, Poblacion, Balanga, Bataan',
    acType: 'Split Type',
    units: 2,
    brand: 'Daikin',
    technician: 'Pedro Santos',
    price: 1600,
    status: 'assigned',
    createdAt: '2024-02-10'
  },
  {
    id: 'BK002',
    serviceType: 'AC Repair',
    date: '2024-01-28',
    time: '2:00 PM - 5:00 PM',
    location: '123 Main St, Poblacion, Balanga, Bataan',
    acType: 'Window Type',
    units: 1,
    brand: 'LG',
    technician: 'Miguel Garcia',
    price: 1500,
    status: 'completed',
    createdAt: '2024-01-25'
  },
  {
    id: 'BK003',
    serviceType: 'AC Installation',
    date: '2024-01-15',
    time: '8:00 AM - 11:00 AM',
    location: '123 Main St, Poblacion, Balanga, Bataan',
    acType: 'Split Type',
    units: 1,
    brand: 'Carrier',
    technician: 'Juan Dela Cruz',
    price: 3000,
    status: 'completed',
    createdAt: '2024-01-10'
  },
  {
    id: 'BK004',
    serviceType: 'Freon Charging',
    date: '2024-01-05',
    time: '1:00 PM - 4:00 PM',
    location: '123 Main St, Poblacion, Balanga, Bataan',
    acType: 'Split Type',
    units: 1,
    brand: 'Samsung',
    technician: 'Carlos Rodriguez',
    price: 1200,
    status: 'cancelled',
    createdAt: '2024-01-02'
  }
];

const sampleNotifications = [
  {
    id: 1,
    type: 'booking',
    title: 'Booking Confirmed',
    message: 'Your AC Cleaning service for February 15, 2024 has been confirmed.',
    date: '2024-02-10',
    isRead: false
  },
  {
    id: 2,
    type: 'feedback',
    title: 'Rate Your Service',
    message: 'Please rate your AC Repair service completed on January 28, 2024.',
    date: '2024-01-29',
    isRead: false
  },
  {
    id: 3,
    type: 'promo',
    title: 'Special Offer: 20% Off AC Cleaning',
    message: 'Get 20% off your next AC cleaning service. Offer valid until March 15, 2024.',
    date: '2024-02-08',
    isRead: true
  },
  {
    id: 4,
    type: 'booking',
    title: 'Service Completed',
    message: 'Your AC Repair service has been completed successfully.',
    date: '2024-01-28',
    isRead: true
  }
];



export default function CustomerDashboard() {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [bookingFilter, setBookingFilter] = useState('all');
  const [notificationFilter, setNotificationFilter] = useState('all');
  const [showAccountSettings, setShowAccountSettings] = useState(false);
  const [editingProfile, setEditingProfile] = useState(false);
  const [showChatbot, setShowChatbot] = useState(false);

  // Filter bookings based on selected filter
  const filteredBookings = sampleBookings.filter(booking => {
    if (bookingFilter === 'all') return true;
    if (bookingFilter === 'your-bookings') return booking.status === 'assigned';
    if (bookingFilter === 'completed') return booking.status === 'completed';
    if (bookingFilter === 'cancelled') return booking.status === 'cancelled';
    return true;
  });

  // Filter notifications based on selected filter
  const filteredNotifications = sampleNotifications.filter(notification => {
    if (notificationFilter === 'all') return true;
    return notification.type === notificationFilter;
  });

  const unreadNotifications = sampleNotifications.filter(n => !n.isRead).length;
  const upcomingBookings = sampleBookings.filter(b => b.status === 'assigned').length;
  const recentBookings = sampleBookings.filter(b => b.status === 'completed').slice(0, 3);

  const handleSignOut = () => {
    router.post('/logout');
  };

  const handleRequestCancellation = (bookingId: string) => {
    alert(`Cancellation request submitted for booking ${bookingId}. We will contact you shortly.`);
  };

  const handleRateService = (bookingId: string) => {
    router.visit('/evaluation-feedback', {
      data: { bookingId }
    });
  };

  const markAllAsRead = () => {
    alert('All notifications marked as read');
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'assigned':
        return 'status-assigned';
      case 'completed':
        return 'status-completed';
      case 'cancelled':
        return 'status-cancelled';
      default:
        return 'status-default';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'assigned':
        return <Clock className="w-4 h-4" />;
      case 'completed':
        return <CheckCircle className="w-4 h-4" />;
      case 'cancelled':
        return <XCircle className="w-4 h-4" />;
      default:
        return <AlertCircle className="w-4 h-4" />;
    }
  };

  const getNotificationIcon = (type: string) => {
    switch (type) {
      case 'booking':
        return <Calendar className="w-5 h-5 text-blue-500" />;
      case 'feedback':
        return <MessageSquare className="w-5 h-5 text-green-500" />;
      case 'promo':
        return <CreditCard className="w-5 h-5 text-purple-500" />;
      default:
        return <Bell className="w-5 h-5 text-gray-500" />;
    }
  };

  const renderDashboard = () => (
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
                <span className="info-value">{customerData.name}</span>
              </div>
            </div>
            <div className="info-item-compact">
              <div className="info-icon">
                <Phone className="w-4 h-4" />
              </div>
              <div className="info-details">
                <span className="info-label">Contact Number</span>
                <span className="info-value">{customerData.phone}</span>
              </div>
            </div>
            <div className="info-item-compact">
              <div className="info-icon">
                <Mail className="w-4 h-4" />
              </div>
              <div className="info-details">
                <span className="info-label">Email Address</span>
                <span className="info-value">{customerData.email}</span>
              </div>
            </div>
            <div className="info-item-compact">
              <div className="info-icon">
                <Home className="w-4 h-4" />
              </div>
              <div className="info-details">
                <span className="info-label">Home Address</span>
                <span className="info-value">
                  {customerData.address.houseNumber} {customerData.address.street}, {customerData.address.barangay}, {customerData.address.municipality}, {customerData.address.province}
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
              <span className="stat-number">{upcomingBookings}</span>
              <span className="stat-label">Upcoming Bookings</span>
            </div>
          </div>
          <div className="stat-card compact">
            <div className="stat-icon completed">
              <CheckCircle className="w-5 h-5" />
            </div>
            <div className="stat-info">
              <span className="stat-number">{sampleBookings.filter(b => b.status === 'completed').length}</span>
              <span className="stat-label">Completed Services</span>
            </div>
          </div>
          <div className="stat-card compact">
            <div className="stat-icon notifications">
              <Bell className="w-5 h-5" />
            </div>
            <div className="stat-info">
              <span className="stat-number">{unreadNotifications}</span>
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
          {recentBookings.map((booking) => (
            <div key={booking.id} className="recent-booking-item">
              <div className="booking-service">
                <span className="service-type">{booking.serviceType}</span>
                <span className="booking-date">{new Date(booking.date).toLocaleDateString()}</span>
              </div>
              <div className="booking-status">
                <span className={`status-badge ${getStatusColor(booking.status)}`}>
                  {getStatusIcon(booking.status)}
                  {booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                </span>
              </div>
            </div>
          ))}
        </div>
      </div>


    </div>
  );

  const renderBookings = () => (
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
          >
            All Bookings
          </button>
          <button 
            className={`filter-btn ${bookingFilter === 'your-bookings' ? 'active' : ''}`}
            onClick={() => setBookingFilter('your-bookings')}
          >
            Your Bookings
          </button>
          <button 
            className={`filter-btn ${bookingFilter === 'completed' ? 'active' : ''}`}
            onClick={() => setBookingFilter('completed')}
          >
            Completed
          </button>
          <button 
            className={`filter-btn ${bookingFilter === 'cancelled' ? 'active' : ''}`}
            onClick={() => setBookingFilter('cancelled')}
          >
            Cancelled
          </button>
        </div>
      </div>

      {/* Bookings List */}
      <div className="bookings-list">
        {filteredBookings.map((booking) => (
          <div key={booking.id} className="booking-card">
            <div className="booking-card-header">
              <div className="booking-id-section">
                <span className="booking-id">#{booking.id}</span>
                <span className={`status-badge ${getStatusColor(booking.status)}`}>
                  {getStatusIcon(booking.status)}
                  {booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                </span>
              </div>
              <div className="booking-price">₱{booking.price.toLocaleString()}</div>
            </div>
            
            <div className="booking-card-content">
              <div className="booking-main-info">
                <h3 className="service-type">{booking.serviceType}</h3>
                <div className="booking-details-grid">
                  <div className="detail-item">
                    <Calendar className="w-4 h-4" />
                    <span>{new Date(booking.date).toLocaleDateString()}</span>
                  </div>
                  <div className="detail-item">
                    <Clock className="w-4 h-4" />
                    <span>{booking.time}</span>
                  </div>
                  <div className="detail-item">
                    <MapPin className="w-4 h-4" />
                    <span>{booking.location}</span>
                  </div>
                  <div className="detail-item">
                    <Wrench className="w-4 h-4" />
                    <span>{booking.acType} - {booking.units} unit{booking.units > 1 ? 's' : ''}</span>
                  </div>
                  <div className="detail-item">
                    <span className="brand-label">Brand:</span>
                    <span>{booking.brand}</span>
                  </div>
                  <div className="detail-item">
                    <User className="w-4 h-4" />
                    <span>Technician: {booking.technician}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <div className="booking-card-actions">
              {booking.status === 'assigned' && (
                <button 
                  className="action-btn cancel"
                  onClick={() => handleRequestCancellation(booking.id)}
                >
                  Request Cancellation
                </button>
              )}
              {booking.status === 'completed' && (
                <button 
                  className="action-btn rate"
                  onClick={() => handleRateService(booking.id)}
                >
                  <Star className="w-4 h-4" />
                  Rate Service
                </button>
              )}
            </div>
          </div>
        ))}
      </div>
    </div>
  );

  const renderNotifications = () => (
    <div className="customer-dashboard-content">
      <div className="notifications-header">
        <h1 className="page-title">Notifications</h1>
        <div className="notifications-actions">
          <button 
            className="mark-all-read-btn"
            onClick={markAllAsRead}
          >
            Mark all as read
          </button>
        </div>
      </div>

      {/* Notification Filters */}
      <div className="notification-filters">
        <button 
          className={`filter-btn ${notificationFilter === 'all' ? 'active' : ''}`}
          onClick={() => setNotificationFilter('all')}
        >
          <Filter className="w-4 h-4" />
          All
        </button>
        <button 
          className={`filter-btn ${notificationFilter === 'booking' ? 'active' : ''}`}
          onClick={() => setNotificationFilter('booking')}
        >
          <Calendar className="w-4 h-4" />
          Booking
        </button>
        <button 
          className={`filter-btn ${notificationFilter === 'feedback' ? 'active' : ''}`}
          onClick={() => setNotificationFilter('feedback')}
        >
          <MessageSquare className="w-4 h-4" />
          Feedback
        </button>
        <button 
          className={`filter-btn ${notificationFilter === 'promo' ? 'active' : ''}`}
          onClick={() => setNotificationFilter('promo')}
        >
          <CreditCard className="w-4 h-4" />
          Promo
        </button>
      </div>

      {/* Notifications List */}
      <div className="notifications-list">
        {filteredNotifications.map((notification) => (
          <div 
            key={notification.id} 
            className={`notification-card ${!notification.isRead ? 'unread' : ''}`}
          >
            <div className="notification-icon">
              {getNotificationIcon(notification.type)}
            </div>
            <div className="notification-content">
              <h3 className="notification-title">{notification.title}</h3>
              <p className="notification-message">{notification.message}</p>
              <span className="notification-date">
                {new Date(notification.date).toLocaleDateString()}
              </span>
            </div>
            {!notification.isRead && (
              <div className="unread-indicator"></div>
            )}
          </div>
        ))}
      </div>
    </div>
  );

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
                {customerData.profilePicture ? (
                  <img src={customerData.profilePicture} alt="Profile" />
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
                  value={customerData.name}
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
                value={customerData.email}
                disabled={!editingProfile}
                className="profile-input"
              />
            </div>
            <div className="field-group">
              <label>Mobile Phone</label>
              <input 
                type="tel" 
                value={customerData.phone}
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
                  value={customerData.address.houseNumber}
                  disabled={!editingProfile}
                  className="profile-input"
                />
              </div>
              <div className="field-group">
                <label>Street</label>
                <input 
                  type="text" 
                  value={customerData.address.street}
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
                  value={customerData.address.barangay}
                  disabled={!editingProfile}
                  className="profile-input"
                />
              </div>
              <div className="field-group">
                <label>Municipality</label>
                <input 
                  type="text" 
                  value={customerData.address.municipality}
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
              <span className="user-welcome">Welcome, {customerData.name}</span>
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
              {unreadNotifications > 0 && (
                <span className="notification-count">{unreadNotifications}</span>
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
