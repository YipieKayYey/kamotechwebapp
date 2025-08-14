import { Head, Link, router } from '@inertiajs/react';
import {
	Calendar,
	CheckCircle,
	ClipboardList,
	DollarSign,
	Gauge,
	LogOut,
	MapPin,
	Star,
	TrendingUp,
	Wrench,
	Filter,
	PlayCircle,
	CheckCircle2,
	Settings,
	User,
} from 'lucide-react';
import { useState } from 'react';

type JobStatus = 'assigned' | 'on_the_way' | 'in_progress' | 'completed' | 'cancelled';
type ReportRange = 'weekly' | 'monthly' | 'yearly';

interface TechBooking {
  id: string;
  serviceType: string;
  date: string;
  time: string;
  location: string;
  customer: string;
  commission: number;
  status: JobStatus;
}

export default function TechnicianDashboard() {
  const [activeTab, setActiveTab] = useState<'dashboard' | 'bookings' | 'commission' | 'ratings' | 'reports' | 'settings'>('dashboard');
  const [bookingFilter, setBookingFilter] = useState<'all' | 'your' | 'completed' | 'cancelled'>('all');
  const [reportRange, setReportRange] = useState<ReportRange>('yearly');
  const [commissionRange, setCommissionRange] = useState<ReportRange>('monthly');
  const [bookings, setBookings] = useState<TechBooking[]>([
    { id: 'J-001', serviceType: 'AC Cleaning', date: '2025-08-14', time: '9:00 AM', location: 'Balanga, Bataan', customer: 'Juan Dela Cruz', commission: 250, status: 'assigned' },
    { id: 'J-002', serviceType: 'AC Repair', date: '2025-08-14', time: '1:00 PM', location: 'Orion, Bataan', customer: 'Maria Santos', commission: 350, status: 'on_the_way' },
    { id: 'J-003', serviceType: 'Installation', date: '2025-08-15', time: '10:00 AM', location: 'Dinalupihan, Bataan', customer: 'Pedro Garcia', commission: 600, status: 'completed' },
    { id: 'J-004', serviceType: 'Troubleshooting', date: '2025-08-13', time: '3:00 PM', location: 'Pilar, Bataan', customer: 'Ana Reyes', commission: 200, status: 'cancelled' },
  ]);

  const today = new Date().toISOString().split('T')[0];
  const jobsToday = bookings.filter(b => b.date === today).length;
  const jobsThisWeek = 12;
  const monthlyEarnings = 18500;
  const averageRating = 4.7;

  const todaysSchedule = bookings.filter(b => b.date === today && (b.status === 'assigned' || b.status === 'on_the_way' || b.status === 'in_progress'));
  const recentCompleted = bookings.filter(b => b.status === 'completed').slice(0, 5);

  const filteredBookings = bookings.filter(b => {
    if (bookingFilter === 'all') return true;
    if (bookingFilter === 'your') return ['assigned', 'on_the_way', 'in_progress'].includes(b.status);
    if (bookingFilter === 'completed') return b.status === 'completed';
    if (bookingFilter === 'cancelled') return b.status === 'cancelled';
    return true;
  });

  const updateStatus = (id: string, status: JobStatus) => {
    setBookings(prev => prev.map(b => (b.id === id ? { ...b, status } : b)));
  };

  const handleSignOut = () => {
	router.post('/logout');
  };

  const renderHeader = () => (
	<header className="dashboard-header">
		<div className="header-container">
			<div className="header-logo">
				<Link href="/">
					<img src="/images/logo-main.png" alt="Kamotech Logo" className="logo-image" />
				</Link>
			</div>
			<div className="header-user">
				<span className="user-welcome">Welcome, Technician</span>
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
  );

  const QuickActions = () => (
    <div className="action-buttons" style={{ marginBottom: '0.5rem' }}>
      <button className="action-btn primary" onClick={() => setActiveTab('bookings')}>
        <Calendar className="w-4 h-4" /> View all schedule
      </button>
      <button className="action-btn secondary" onClick={() => setActiveTab('bookings')}>
        <ClipboardList className="w-4 h-4" /> Job history
      </button>
      <button className="action-btn secondary" onClick={() => setActiveTab('commission')}>
        <DollarSign className="w-4 h-4" /> Commissions report
      </button>
    </div>
  );

  const renderDashboard = () => (
    <div className="tech-dashboard">
      <div className="dashboard-stats">
        <div className="stat-card">
          <div className="stat-icon upcoming"><Calendar /></div>
          <div className="tech-stat-info">
            <div className="tech-stat-number">{jobsToday}</div>
            <div className="tech-stat-label">Bookings Today</div>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon completed"><CheckCircle /></div>
          <div className="tech-stat-info">
            <div className="tech-stat-number">{jobsThisWeek}</div>
            <div className="tech-stat-label">This Week</div>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon notifications"><DollarSign /></div>
          <div className="tech-stat-info">
            <div className="tech-stat-number">₱{monthlyEarnings.toLocaleString()}</div>
            <div className="tech-stat-label">Monthly Earnings</div>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon completed"><Star /></div>
          <div className="tech-stat-info">
            <div className="tech-stat-number">{averageRating.toFixed(1)}</div>
            <div className="tech-stat-label">Avg Rating</div>
          </div>
        </div>
      </div>

      <QuickActions />

      <div className="tech-grid-2">
        {/* Today's Schedule */}
        <div className="info-card">
          <div className="card-header">
            <h2 className="card-title"><Wrench className="w-5 h-5" /> Today’s Schedule</h2>
          </div>
          <div className="tech-list">
            {todaysSchedule.length === 0 && <div className="tech-empty">No schedule for today.</div>}
            {todaysSchedule.map(job => (
              <div className="tech-list-item" key={job.id}>
                <div className="tech-list-main">
                  <div className="tech-list-title">{job.serviceType}</div>
                  <div className="tech-list-meta">
                    <Calendar className="w-4 h-4" /> {job.date} • {job.time}
                    <MapPin className="w-4 h-4" /> {job.location}
                  </div>
                </div>
                <div className="tech-list-side">
                  <span className={`status-badge status-${job.status}`}>{job.status.replace('_', ' ')}</span>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Recent Completed */}
        <div className="info-card">
          <div className="card-header">
            <h2 className="card-title"><CheckCircle2 className="w-5 h-5" /> Recent Completed Jobs</h2>
          </div>
          <div className="tech-list">
            {recentCompleted.map(job => (
              <div className="tech-list-item" key={job.id}>
                <div className="tech-list-main">
                  <div className="tech-list-title">{job.serviceType}</div>
                  <div className="tech-list-meta">
                    <Calendar className="w-4 h-4" /> {job.date} • {job.time}
                    <MapPin className="w-4 h-4" /> {job.location}
                  </div>
                </div>
                <div className="tech-list-side">
                  <span className="tech-earnings">₱{job.commission.toLocaleString()}</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Monthly performance (simple placeholder bars) */}
      <div className="info-card">
        <div className="card-header">
          <h2 className="card-title"><TrendingUp className="w-5 h-5" /> Monthly Performance</h2>
        </div>
        <div className="tech-performance">
          {[60, 80, 45, 90, 70, 85, 50, 95, 65, 78, 88, 92].map((val, idx) => (
            <div key={idx} className="tech-bar">
              <div className="tech-bar-fill" style={{ height: `${val}%` }} />
              <div className="tech-bar-label">M{idx + 1}</div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );

  const renderBookings = () => (
    <div className="tech-bookings">
      <div className="tech-header-row">
        <h1 className="page-title">My Booking</h1>
        <div className="tech-filters">
          <button className={`filter-btn ${bookingFilter === 'all' ? 'active' : ''}`} onClick={() => setBookingFilter('all')}><Filter className="w-4 h-4" /> All</button>
          <button className={`filter-btn ${bookingFilter === 'your' ? 'active' : ''}`} onClick={() => setBookingFilter('your')}>Your Bookings</button>
          <button className={`filter-btn ${bookingFilter === 'completed' ? 'active' : ''}`} onClick={() => setBookingFilter('completed')}>Completed</button>
          <button className={`filter-btn ${bookingFilter === 'cancelled' ? 'active' : ''}`} onClick={() => setBookingFilter('cancelled')}>Cancelled</button>
        </div>
      </div>

      <div className="bookings-list">
        {filteredBookings.map(job => (
          <div key={job.id} className="booking-card">
            <div className="booking-card-header">
              <div className="booking-id-section">
                <span className="booking-id">#{job.id}</span>
                  <span className={`status-badge status-${job.status}`}>{job.status.replace('_', ' ')}</span>
              </div>
              <div className="booking-price">₱{job.commission.toLocaleString()}</div>
            </div>
            <div className="booking-card-content">
              <div className="booking-main-info">
                <h3 className="service-type">{job.serviceType}</h3>
                <div className="booking-details-grid">
                  <div className="detail-item"><Calendar className="w-4 h-4" /> <span>{job.date} • {job.time}</span></div>
                  <div className="detail-item"><MapPin className="w-4 h-4" /> <span>{job.location}</span></div>
                  <div className="detail-item"><User className="w-4 h-4" /> <span>{job.customer}</span></div>
                </div>
              </div>
            </div>
            <div className="tech-status-actions">
              <button className={`tech-btn-outline ${job.status === 'on_the_way' ? 'active' : ''}`} onClick={() => updateStatus(job.id, 'on_the_way')}><PlayCircle className="w-4 h-4" /> On the way</button>
              <button className={`tech-btn-outline ${job.status === 'in_progress' ? 'active' : ''}`} onClick={() => updateStatus(job.id, 'in_progress')}><Gauge className="w-4 h-4" /> In progress</button>
              <button className={`tech-btn-outline ${job.status === 'completed' ? 'active' : ''}`} onClick={() => updateStatus(job.id, 'completed')}><CheckCircle2 className="w-4 h-4" /> Completed</button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );

  const renderCommission = () => (
    <div className="tech-commission">
      <div className="tech-header-row">
        <h1 className="page-title">Commission</h1>
      </div>
      <div className="tech-filters">
        <button className={`filter-btn ${commissionRange === 'weekly' ? 'active' : ''}`} onClick={() => setCommissionRange('weekly')}>Weekly</button>
        <button className={`filter-btn ${commissionRange === 'monthly' ? 'active' : ''}`} onClick={() => setCommissionRange('monthly')}>Monthly</button>
        <button className={`filter-btn ${commissionRange === 'yearly' ? 'active' : ''}`} onClick={() => setCommissionRange('yearly')}>Yearly</button>
      </div>
      <div className="tech-stats-grid">
        <div className="tech-stat-card">
          <div className="tech-stat-icon purple"><DollarSign /></div>
          <div className="tech-stat-info">
            <div className="tech-stat-number">₱2,350</div>
            <div className="tech-stat-label">Today</div>
          </div>
        </div>
        <div className="tech-stat-card">
          <div className="tech-stat-icon green"><DollarSign /></div>
          <div className="tech-stat-info">
            <div className="tech-stat-number">₱10,480</div>
            <div className="tech-stat-label">This Week</div>
          </div>
        </div>
        <div className="tech-stat-card">
          <div className="tech-stat-icon blue"><DollarSign /></div>
          <div className="tech-stat-info">
            <div className="tech-stat-number">₱18,500</div>
            <div className="tech-stat-label">This Month</div>
          </div>
        </div>
        <div className="tech-stat-card">
          <div className="tech-stat-icon amber"><ClipboardList /></div>
          <div className="tech-stat-info">
            <div className="tech-stat-number">42</div>
            <div className="tech-stat-label">Jobs Completed</div>
          </div>
        </div>
      </div>

      {(() => {
        const config = (() => {
          if (commissionRange === 'weekly') {
            return { title: 'Earnings Trend (Weekly)', values: [45, 62, 55, 70, 65, 80, 90], label: 'D', color: 'green' };
          }
          if (commissionRange === 'monthly') {
            return { title: 'Earnings Trend (Monthly)', values: [18, 22, 26, 24, 30], label: 'W', color: '' };
          }
          return { title: 'Earnings Trend (Yearly)', values: [35, 48, 52, 60, 72, 80, 78, 85, 88, 90, 92, 95], label: 'M', color: 'purple' };
        })();
        return (
          <div className="info-card">
            <div className="card-header">
              <h2 className="card-title"><TrendingUp className="w-5 h-5" /> {config.title}</h2>
            </div>
            <div className="tech-performance">
              {config.values.map((val, idx) => (
                <div className="tech-bar" key={`${config.label}-${idx}`}>
                  <div className={`tech-bar-fill${config.color ? ' ' + config.color : ''}`} style={{ height: `${val}%` }} />
                  <div className="tech-bar-label">{config.label}{idx + 1}</div>
                </div>
              ))}
            </div>
          </div>
        );
      })()}
    </div>
  );

  const renderRatings = () => (
    <div className="tech-ratings">
      <div className="tech-header-row">
        <h1 className="page-title">Ratings</h1>
      </div>
      <div className="info-card">
        <div className="tech-rating-summary">
          <div className="tech-rating-score">
            <Star className="w-8 h-8" />
            <div className="tech-score">4.7</div>
            <div className="tech-score-label">Average Rating</div>
          </div>
          <div className="tech-rating-breakdown">
            {[5,4,3,2,1].map(star => (
              <div key={star} className="tech-rating-row">
                <div className="tech-stars">{'★'.repeat(star)}</div>
                <div className="tech-rating-bar"><div style={{ width: `${star*15}%` }} /></div>
                <div className="tech-rating-count">{star*3}</div>
              </div>
            ))}
          </div>
        </div>
      </div>
      <div className="info-card">
        <div className="card-header">
          <h2 className="card-title"><Star className="w-5 h-5" /> Recent Reviews</h2>
        </div>
        <div className="tech-reviews">
          {[{name:'Juan Dela Cruz', rating:5, text:'Excellent and on-time service!'}, {name:'Maria Santos', rating:4, text:'Great job, could improve arrival time.'}].map((r, idx) => (
            <div className="tech-review" key={idx}>
              <div className="tech-review-head">
                <div className="tech-review-name">{r.name}</div>
                <div className="tech-review-stars">{'★'.repeat(r.rating)}{'☆'.repeat(5-r.rating)}</div>
              </div>
              <div className="tech-review-text">{r.text}</div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );

  const renderReports = () => (
    <div className="tech-reports">
      <div className="tech-header-row">
        <h1 className="page-title">Reports</h1>
      </div>
      <div className="tech-filters">
        <button className={`filter-btn ${reportRange === 'weekly' ? 'active' : ''}`} onClick={() => setReportRange('weekly')}>Weekly</button>
        <button className={`filter-btn ${reportRange === 'monthly' ? 'active' : ''}`} onClick={() => setReportRange('monthly')}>Monthly</button>
        <button className={`filter-btn ${reportRange === 'yearly' ? 'active' : ''}`} onClick={() => setReportRange('yearly')}>Yearly</button>
      </div>
      <div className="tech-stats-grid">
        <div className="tech-stat-card">
          <div className="tech-stat-icon blue"><ClipboardList /></div>
          <div className="tech-stat-info">
            <div className="tech-stat-number">18</div>
            <div className="tech-stat-label">Weekly Jobs</div>
          </div>
        </div>
        <div className="tech-stat-card">
          <div className="tech-stat-icon green"><ClipboardList /></div>
          <div className="tech-stat-info">
            <div className="tech-stat-number">76</div>
            <div className="tech-stat-label">Monthly Jobs</div>
          </div>
        </div>
        <div className="tech-stat-card">
          <div className="tech-stat-icon purple"><ClipboardList /></div>
          <div className="tech-stat-info">
            <div className="tech-stat-number">892</div>
            <div className="tech-stat-label">Annual Hours</div>
          </div>
        </div>
        <div className="tech-stat-card">
          <div className="tech-stat-icon amber"><DollarSign /></div>
          <div className="tech-stat-info">
            <div className="tech-stat-number">₱215,400</div>
            <div className="tech-stat-label">YTD Earnings</div>
          </div>
        </div>
      </div>
      {(() => {
        const config = (() => {
          if (reportRange === 'weekly') {
            return { title: 'Weekly Trend', values: [35, 48, 60, 42, 68, 80, 55], label: 'D', color: 'green' };
          }
          if (reportRange === 'monthly') {
            return { title: 'Monthly Trend', values: [45, 58, 62, 50, 70], label: 'W', color: '' };
          }
          return { title: 'Yearly Trend', values: [35, 55, 60, 40, 75, 80, 90, 88, 70, 65, 85, 95], label: 'M', color: 'purple' };
        })();
        return (
          <div className="info-card">
            <div className="card-header">
              <h2 className="card-title"><TrendingUp className="w-5 h-5" /> {config.title}</h2>
            </div>
            <div className="tech-performance">
              {config.values.map((v, i) => (
                <div className="tech-bar" key={`${config.label}-${i}`}>
                  <div className={`tech-bar-fill${config.color ? ' ' + config.color : ''}`} style={{ height: `${v}%` }} />
                  <div className="tech-bar-label">{config.label}{i + 1}</div>
                </div>
              ))}
            </div>
          </div>
        );
      })()}
    </div>
  );

  const renderSettings = () => (
    <div className="tech-settings">
      <div className="tech-header-row">
        <h1 className="page-title">Account Settings</h1>
      </div>
      <div className="tech-grid-2">
        <div className="info-card">
          <div className="card-header">
            <h2 className="card-title"><User className="w-5 h-5" /> Personal Information</h2>
          </div>
          <div className="profile-fields">
            <div className="field-group">
              <label>Full Name</label>
              <input className="profile-input" type="text" defaultValue="Tech User" />
            </div>
            <div className="field-group">
              <label>Email</label>
              <input className="profile-input" type="email" defaultValue="tech@example.com" />
            </div>
            <div className="field-group">
              <label>Phone</label>
              <input className="profile-input" type="tel" defaultValue="0912 345 6789" />
            </div>
          </div>
        </div>
        <div className="info-card">
          <div className="card-header">
            <h2 className="card-title"><Settings className="w-5 h-5" /> Security</h2>
          </div>
          <div className="profile-fields">
            <div className="field-group">
              <label>Current Password</label>
              <input className="profile-input" type="password" placeholder="••••••••" />
            </div>
            <div className="field-group">
              <label>New Password</label>
              <input className="profile-input" type="password" placeholder="••••••••" />
            </div>
            <div className="field-group">
              <label>Confirm New Password</label>
              <input className="profile-input" type="password" placeholder="••••••••" />
            </div>
            <div className="settings-actions">
              <button className="save-btn">Save Changes</button>
              <button className="cancel-btn">Cancel</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  return (
    <>
		<Head title="Technician Dashboard" />

		<div className="technician-dashboard">
			{renderHeader()}

			{/* Navigation Tabs */}
			<div className="dashboard-nav">
				<div className="nav-container">
					<button 
						className={`nav-tab ${activeTab === 'dashboard' ? 'active' : ''}`}
						onClick={() => setActiveTab('dashboard')}
					>
						<Gauge className="w-5 h-5" />
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
						className={`nav-tab ${activeTab === 'commission' ? 'active' : ''}`}
						onClick={() => setActiveTab('commission')}
					>
						<DollarSign className="w-5 h-5" />
						Commission
					</button>
					<button 
						className={`nav-tab ${activeTab === 'ratings' ? 'active' : ''}`}
						onClick={() => setActiveTab('ratings')}
					>
						<Star className="w-5 h-5" />
						Ratings
					</button>
					<button 
						className={`nav-tab ${activeTab === 'reports' ? 'active' : ''}`}
						onClick={() => setActiveTab('reports')}
					>
						<ClipboardList className="w-5 h-5" />
						Reports
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
				{activeTab === 'commission' && renderCommission()}
				{activeTab === 'ratings' && renderRatings()}
				{activeTab === 'reports' && renderReports()}
				{activeTab === 'settings' && renderSettings()}
			</div>
		</div>
	</>
  );
}



