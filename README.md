# KAMOTECH AC Service Management System

## 🏢 Project Overview

**KAMOTECH** is a comprehensive **Air Conditioning Service Management System** currently in development, built with Laravel 11, React 19, TypeScript, Tailwind CSS, and Filament PHP. This system is designed to streamline AC service operations in the Philippines, specifically for managing technicians, bookings, pricing, and customer relationships across service areas in Bataan.

**Current Phase**: Core System Complete - Dynamic rating system implemented with organized admin panel. Ready for customer interface development.

### 🎯 Key Features

- **🔧 Service Management**: Complete AC service lifecycle (Installation, Repair, Maintenance, Cleaning)
- **👨‍🔧 Technician Management**: Commission-based technician system with performance analytics and expertise tracking
- **📅 Booking System**: Smart scheduling with timeslots, dynamic pricing, and auto-calculation
- **💰 Dynamic Pricing Matrix**: Service pricing based on AC type and service combinations
- **📍 Address-Based Services**: Detailed address management with province/city/barangay components
- **📊 Performance Analytics**: Comprehensive reporting and dashboard with KPIs
- **⭐ Dynamic Rating System**: 5 configurable rating categories with auto-calculated overall ratings
- **💵 Commission Management**: Automated earnings calculation for technicians

---

## 🛠️ Technology Stack

### **Backend**
- **Laravel 11** - PHP Framework
- **MySQL** - Database
- **Filament PHP** - Admin Panel & Resource Management

### **Frontend**  
- **React 19** - UI Library
- **TypeScript** - Type Safety
- **Inertia.js** - SPA Framework
- **Tailwind CSS** - Styling
- **Shadcn/UI & Radix UI** - Component Libraries

### **Tools & Environment**
- **Vite** - Build Tool
- **XAMPP** - Local Development Environment
- **Composer** - PHP Package Manager
- **NPM** - Node Package Manager

---

## 📊 System Architecture

### **Database Models & Relationships**

```
Users (Customers, Technicians, Admins)
├── Technicians (Commission-based workers)
│   ├── Bookings (Service appointments)
│   ├── Earnings (Commission calculations) 
│   ├── Reviews (Customer feedback)
│   └── Availability (Working schedules)
│
├── Services (AC service types)
│   ├── ServicePricing (Dynamic pricing matrix)
│   └── Bookings
│
├── AirconTypes (Window, Split, Cassette, etc.)
│   ├── ServicePricing
│   └── Bookings
│
├── Timeslots (Scheduling intervals)
│   └── Bookings
│
├── ReviewCategories (Rating categories: Work Quality, Punctuality, etc.)
│   └── CategoryScores
│
└── Bookings (Core service appointments)
    ├── RatingReviews (with overall_rating auto-calculated from categories)
    │   └── CategoryScores (Individual category ratings 1-5)
    └── Earnings
```

### **Core Business Logic**

1. **🎯 Greedy Algorithm for Technician Selection** *(THESIS CONTRIBUTION)*:
   - **Service Rating** (70%): Technician's performance on specific service type using category-based ratings
   - **Availability** (30%): Current workload and schedule conflicts
   - **Real-time scoring** with weighted formula: `SCORE = (ServiceRating × 0.70) + (Availability × 0.30)`
   - **Service-specific expertise** tracking with diversified technician profiles
   - **Automatic optimal assignment** ensuring best customer experience

2. **Dynamic Pricing System**: 
   - Base service prices + AC type multipliers
   - Multi-unit progressive discounts (efficiency scaling)
   - Real-time total amount calculation
   - Address-based service delivery without travel fees

3. **Commission-Based Compensation**:
   - Technicians earn commission per completed job
   - Automated earnings calculation and tracking
   - Performance-based incentives

4. **Smart Scheduling & Availability**:
   - Predefined timeslots (6-9 AM, 9-12 PM, 12-3 PM, 3-6 PM)
   - Real-time technician availability tracking
   - Multi-day booking conflict detection
   - Geographic service area coverage

---

## 🚀 Features Implemented

### **👥 User Management**
- **Multi-role system**: Admin, Technician, Customer
- **Profile management**: Contact info, address, GPS coordinates
- **Account activation/deactivation**

### **🔧 Service Management**
- **Service Types**: Installation, Repair, Maintenance, Cleaning, Troubleshooting
- **Base pricing**: Starting from ₱800-₱2,500 per service
- **Duration tracking**: Service time estimates
- **Parts requirement**: Automatic flagging for services requiring parts

### **👨‍🔧 Technician Management**
- **Direct technician creation**: Create user account + technician profile in one step
- **Commission-based model**: Replaced hourly wages with commission rates
- **Performance tracking**: Jobs completed, ratings, earnings
- **Geographic assignments**: Base location and service radius
- **Availability management**: Daily schedules and workload limits

### **📱 Booking System**
- **Sequential booking numbers**: KMT-000001, KMT-000002, etc.
- **Smart scheduling**: Timeslot-based appointments
- **Dynamic pricing**: Auto-calculation based on service + AC type + travel fee
- **GPS integration**: Customer location tracking and distance calculation
- **Status tracking**: Pending → Confirmed → In Progress → Completed → Cancelled

### **💰 Pricing Matrix**
- **Service-specific pricing**: Different rates for different AC types
- **Travel fees**: Area-based additional charges
- **Automatic calculation**: Real-time total amount updates
- **Fallback system**: Base service price if specific pricing unavailable

### **⭐ Rating & Review System**
- **Customer feedback**: 5-star rating system with written reviews
- **Technician performance**: Average rating calculation
- **Approval system**: Admin moderation of reviews

### **📊 Dashboard & Analytics**
- **Real-time KPIs**: Today's bookings, monthly revenue, active technicians
- **Performance metrics**: Customer satisfaction, completion rates
- **Visual charts**: Booking trends and revenue tracking
- **Alert system**: Pending bookings notifications

### **📈 Reporting System**
- **Technician performance reports**: Weekly, Monthly, Yearly, Custom date ranges
- **Performance ratings**: Excellent, Good, Average, Needs Improvement
- **Earnings tracking**: Commission calculations and payment status
- **Completion rate analysis**: Job success metrics

---

## 🏗️ Database Schema

### **Key Tables**
- **users**: Authentication & profile data with detailed address components
- **technicians**: Technician-specific info & commission rates
- **services**: AC service types & base pricing
- **aircon_types**: Different AC unit categories
- **service_pricing**: Dynamic pricing matrix (service + AC type combinations)
- **bookings**: Core appointment data with detailed address components
- **timeslots**: Predefined scheduling intervals
- **review_categories**: Configurable rating categories (Work Quality, Punctuality, etc.)
- **ratings_reviews**: Customer feedback with auto-calculated overall ratings
- **category_scores**: Individual category ratings (1-5 stars per category)
- **earnings**: Commission tracking & payments

### **Sample Data Included**
- **8 Services**: From basic cleaning (₱800) to installation (₱2,500)
- **6 AC Types**: Window, Split, Cassette, Ducted, VRF, Chiller
- **5 Rating Categories**: Work Quality, Punctuality, Cleanliness, Attitude, Tools
- **4 Timeslots**: 6-9 AM, 9-12 PM, 12-3 PM, 3-6 PM
- **70+ Sample Bookings**: Mix of recent and historical data with diversified assignments
- **50+ Users**: Admins, technicians, and customers with realistic address data
- **175 Category Ratings**: Complete rating matrix (35 reviews × 5 categories)

---

## 🎨 Admin Panel Features (Filament)

### **📋 Organized Navigation Groups**
- **📋 Booking Management**: Customer Bookings, All Bookings
- **🔧 Service Management**: Services, Service Pricing, Aircon Types
- **👨‍🔧 Technician Management**: Technician profiles with commission tracking
- **👥 User Management**: Customer and admin user management
- **⭐ Review Management**: Rating Categories, Customer Reviews with dynamic scoring
- **📊 Reports & Analytics**: Performance dashboards and technician reports

### **📊 Dashboard Widgets**
- **Stats Overview**: 6 KPI cards with trending data
- **Bookings Chart**: Visual booking trends
- **Real-time metrics**: Today's performance vs historical data

### **📈 Custom Pages**
- **Technician Reports**: Comprehensive performance analytics
- **Flexible reporting**: Multiple date ranges and technician filtering
- **Performance ratings**: Automated quality assessment

---

## 🚧 Current Status

### ✅ **Phase 1: Foundation Complete**
- ✅ Complete database schema with all relationships (15 migrations)
- ✅ All Eloquent models with proper relationships and business logic
- ✅ Full Filament admin panel with all resources (8 admin resources)
- ✅ Dashboard with KPIs and charts (6 performance widgets)
- ✅ Sample data seeding (13 seeders, 65+ bookings, 5 technicians)
- ✅ Frontend UI pages (13+ React pages with beautiful design)
- ✅ Authentication system (login, register, password reset)
- ✅ Service showcase pages (7 detailed service pages)
- ✅ Google Maps service classes (geocoding, distance calculation)
- ✅ Dynamic pricing models and database structure

### ✅ **Phase 2: Core Algorithm Complete**
- ✅ **Enhanced Database Schema**: Multi-unit booking support (`number_of_units`, `ac_brand`, `scheduled_end_date`, `estimated_duration_minutes`, `estimated_days`)
- ✅ **Service-Specific Ratings**: Enhanced `ratings_reviews` table with `service_id` for precise technician performance tracking
- ✅ **Greedy Algorithm Implementation**: Complete `TechnicianRankingService` with weighted scoring (Service Rating 70%, Availability 30%)
- ✅ **Real-Time Availability System**: `TechnicianAvailabilityService` for live technician availability checking with multi-day booking conflict detection
- ✅ **Smart Admin Integration**: Filament resources show "X technicians available" per timeslot and ranked technician selection with scores
- ✅ **Multi-Unit Pricing**: Dynamic pricing calculation with progressive discounts for multiple AC units
- ✅ **Comprehensive Test Data**: Enhanced seeders with realistic multi-unit, multi-day scenarios and service-specific ratings

### ✅ **Phase 3: Dynamic Rating System & Admin Organization Complete**
- ✅ **Dynamic Rating Categories**: 5 configurable rating categories (Work Quality, Punctuality, Cleanliness, Attitude, Tools)
- ✅ **Auto-Calculated Overall Ratings**: System automatically computes average ratings from category scores
- ✅ **Enhanced Database Schema**: New `review_categories` and `category_scores` tables for flexible rating management
- ✅ **Updated Greedy Algorithm**: Removed proximity-based scoring, focused on service expertise and availability
- ✅ **Organized Admin Navigation**: Professional navigation groups (Booking, Service, Technician, User, Review Management)
- ✅ **Diversified Technician Profiles**: Realistic expertise-based seeding with service specializations
- ✅ **Address-Only System**: Removed GPS dependencies, implemented detailed address components (Province/City/Barangay)
- ✅ **Complete Integration**: All widgets, reports, and resources updated for new rating system

### 🔄 **Phase 4: Customer Interface (In Planning)**
- 🎯 **Customer Booking Frontend**: React booking form connecting to backend algorithm
- 🎯 **Real-Time Availability Display**: Customer-facing timeslot selection with availability counts
- 🎯 **Technician Selection Interface**: Customer can see ranked technician options with ratings
- 🎯 **Address Input Integration**: Enhanced address form with autocomplete for Philippine addresses

### ❌ **Future Enhancements**
- ❌ **Payment Processing**: Payment gateway integration
- ❌ **Email Notifications**: Booking confirmations and status updates
- ❌ **Mobile App**: Technician mobile application
- ❌ **Real-Time Tracking**: GPS tracking during service

### 📋 **Planned Features**
- 📱 Mobile app for technicians
- 🗺️ Real-time GPS tracking
- 📦 Inventory management for AC parts
- 🏪 Customer self-service portal
- 📊 Advanced analytics and business intelligence
- 💳 Multiple payment gateway options
- 📧 Automated SMS/Email notifications

---

## 🎯 Next Development Priorities

### **Current Focus (Phase 4): Customer Frontend Integration**
1. **🎨 Customer Booking Interface**
   - Create React booking form components with step-by-step wizard
   - Integrate real-time availability display from `TechnicianAvailabilityService`
   - Show ranked technician options with ratings and scores
   - Implement multi-unit booking selection with dynamic pricing

2. **🔗 API Endpoints**
   - Create Laravel API routes for booking submission
   - Connect React forms to greedy algorithm backend
   - Real-time availability checking endpoints
   - Form validation and error handling

3. **📍 Address Input Enhancement**
   - Address autocomplete for Philippine locations
   - Province/City/Barangay dropdown or autocomplete components
   - Enhanced address validation and formatting
   - Integration with local address APIs if needed

4. **📱 User Experience Enhancements**
   - Real-time availability updates in booking form
   - Technician selection with detailed profiles and ratings
   - Booking confirmation and status tracking
   - Responsive design for mobile devices

### **Phase 3 Achievements (COMPLETED ✅)**
- ✅ **Dynamic Rating System**: 5 configurable categories with auto-calculated overall ratings
- ✅ **Organized Admin Navigation**: Professional navigation groups for better UX
- ✅ **Address-Based Services**: Removed GPS dependencies, enhanced address management
- ✅ **Updated Greedy Algorithm**: Focused scoring on service expertise (70%) and availability (30%)
- ✅ **Diversified Data**: Realistic technician expertise profiles and interconnected seeders
- ✅ **Complete Integration**: All widgets, reports, and resources updated for new system

### **Success Metrics for Phase 4**
- 🎯 Customer can book through website with real-time availability
- 🎯 System shows "X technicians available" to customers  
- 🎯 Automatic optimal technician assignment via greedy algorithm
- 🎯 Enhanced address input with Philippine location support
- 🎯 Seamless booking experience from selection to confirmation
- 🎯 Category-based rating system integrated into customer flow

---

## 💼 Business Model

### **Revenue Streams**
1. **Service Fees**: Direct payment from customers
2. **Travel Fees**: Area-based additional charges
3. **Commission Structure**: Technician earnings from completed jobs

### **Target Market**
- **Primary**: Residential AC owners in Bataan, Philippines
- **Secondary**: Small businesses and commercial establishments
- **Service Areas**: 15+ municipalities and cities in Bataan

### **Competitive Advantages**
- **Technology-driven**: Modern web-based management system
- **Performance tracking**: Data-driven technician management
- **Dynamic pricing**: Flexible pricing based on service complexity
- **Geographic coverage**: Structured service area management

---

## 🎓 Academic Context

This project serves as a **thesis project** demonstrating:
- **Full-stack development**: Laravel + React ecosystem
- **Business process automation**: Service industry digitization
- **Database design**: Complex relationships and business rules
- **User experience design**: Admin and customer interfaces
- **Performance analytics**: Data-driven decision making

**Development Timeline**:
- **Phase 1 (Completed)**: Database architecture, admin panel, frontend UI (July-December 2024)
- **Phase 2 (Completed)**: Greedy algorithm, technician availability system, multi-unit booking support (January 2025)
- **Phase 3 (Completed)**: Dynamic rating system, organized admin navigation, address-based services (January 2025)
- **Phase 4 (Current)**: Customer booking frontend, API integration, address input enhancement (January-February 2025)
- **Phase 5 (Planned)**: Testing, deployment, final integration (February-March 2025)
- **Thesis Defense**: Scheduled for April 2025

---

## 📞 Project Information

**Developer**: Thesis Student  
**Institution**: Academic Institution  
**Project Type**: Undergraduate Thesis  
**Industry**: Air Conditioning Service Management  
**Geographic Focus**: Bataan, Philippines  

---

## 📄 License

This project is developed for academic purposes as part of a thesis requirement. All rights reserved for educational use.

---

*Last Updated: January 2025*  
*Version: 1.0.0 - Phase 3 Complete (Dynamic Rating System & Admin Organization)*  
*Next Release: v1.1.0 - Customer Booking Interface (February 2025)*