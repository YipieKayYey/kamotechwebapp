# KAMOTECH AC Service Management System

## 🏢 Project Overview

**KAMOTECH** is a comprehensive **Air Conditioning Service Management System** currently in development, built with Laravel 11, React 19, TypeScript, Tailwind CSS, and Filament PHP. This system is designed to streamline AC service operations in the Philippines, specifically for managing technicians, bookings, pricing, and customer relationships across service areas in Bataan.

**Current Phase**: Advanced System Complete - Full customer-facing application with booking system, dashboard, notifications, and cancellation management. Ready for production deployment.

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
- **Dynamic Rating Categories**: 5 configurable rating categories (Work Quality, Punctuality, Cleanliness, Attitude, Tools)
- **Auto-Calculated Overall Ratings**: System automatically computes average ratings from category scores
- **Service-Specific Ratings**: Ratings tracked per service type for precise technician expertise measurement
- **Customer Review Interface**: Complete review submission system with category-based scoring
- **Admin Review Management**: Full admin panel for review moderation and management

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

### **🎯 Customer Booking System**
- **Multi-Step Booking Wizard**: React-based booking form with step-by-step guidance
- **Real-Time Availability**: Live technician availability checking via AJAX APIs
- **Greedy Algorithm Integration**: Customer sees ranked technician options with scores
- **Dynamic Pricing Calculator**: Real-time total amount calculation with multi-unit support
- **Address Management**: Philippine province/municipality/barangay system
- **Booking Confirmation**: Success modals with booking number and details

### **🏠 Customer Dashboard**
- **Comprehensive Dashboard**: Complete customer portal with booking management
- **Booking History**: Paginated booking history with status tracking
- **Live Notifications**: Real-time notification system with read/unread tracking
- **Cancellation Requests**: 24-hour rule enforcement with admin notification system
- **Review Submission**: Category-based rating system for completed services
- **Profile Management**: Customer profile and address management

### **🔔 Notification System**
- **Real-Time Notifications**: Live notification delivery for booking updates
- **Multiple Notification Types**: Booking confirmations, reminders, status updates, cancellation requests
- **Unread Count Tracking**: Visual indicators for new notifications
- **Admin Notifications**: System-generated notifications for admin actions
- **Notification Management**: Mark as read, delete, and bulk operations

### **❌ Cancellation Management**
- **Customer Cancellation Requests**: Self-service cancellation request system
- **24-Hour Rule Enforcement**: Automatic prevention of last-minute cancellations
- **Status Color System**: Enhanced booking status visualization with distinct colors
- **Admin Processing**: Admin panel for handling cancellation requests
- **Notification Integration**: Automatic admin alerts for cancellation requests

---

## 🏗️ Database Schema

### **Key Tables**
- **users**: Authentication & profile data with detailed address components
- **technicians**: Technician-specific info & commission rates
- **services**: AC service types & base pricing
- **aircon_types**: Different AC unit categories
- **service_pricing**: Dynamic pricing matrix (service + AC type combinations)
- **bookings**: Core appointment data with enhanced cancellation management
- **timeslots**: Predefined scheduling intervals
- **review_categories**: Configurable rating categories (Work Quality, Punctuality, etc.)
- **ratings_reviews**: Customer feedback with auto-calculated overall ratings and service-specific tracking
- **category_scores**: Individual category ratings (1-5 stars per category)
- **earnings**: Commission tracking & payments
- **notifications**: Real-time notification system with read/unread tracking
- **chat_sessions**: Chat conversation management (foundation)
- **chat_messages**: Individual chat messages (foundation)
- **promotions**: Marketing promotion system (foundation)
- **technician_availability**: Daily schedule and workload management

### **Sample Data Included**
- **8 Services**: From basic cleaning (₱800) to installation (₱2,500)
- **6 AC Types**: Window, Split, Cassette, Ducted, VRF, Chiller
- **5 Rating Categories**: Work Quality, Punctuality, Cleanliness, Attitude, Tools
- **4 Timeslots**: 6-9 AM, 9-12 PM, 12-3 PM, 3-6 PM
- **70+ Sample Bookings**: Mix of recent and historical data with diverse status types
- **50+ Users**: Admins, technicians, and customers with realistic Philippine address data
- **175 Category Ratings**: Complete rating matrix with service-specific performance tracking
- **Notification Data**: Sample notifications for booking updates and cancellation requests
- **Technician Availability**: Realistic weekly schedules and workload limits

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
- ✅ Complete database schema with all relationships (19 migrations)
- ✅ All Eloquent models with proper relationships and business logic
- ✅ Full Filament admin panel with all resources (9 admin resources)
- ✅ Dashboard with KPIs and charts (6 performance widgets)
- ✅ Sample data seeding (15+ seeders, 65+ bookings, 5 technicians)
- ✅ Frontend UI pages (25+ React pages with beautiful design)
- ✅ Authentication system (login, register, password reset)
- ✅ Service showcase pages (7 detailed service pages)
- ✅ Address-based service system (removed GPS dependencies)
- ✅ Dynamic pricing models and database structure

### ✅ **Phase 2: Core Algorithm Complete**
- ✅ **Enhanced Database Schema**: Multi-unit booking support with cancellation management
- ✅ **Service-Specific Ratings**: Enhanced `ratings_reviews` table with `service_id` for precise technician performance tracking
- ✅ **Greedy Algorithm Implementation**: Complete `TechnicianRankingService` with pure service-rating focus (100% Service Rating)
- ✅ **Real-Time Availability System**: `TechnicianAvailabilityService` for live technician availability checking with multi-day booking conflict detection
- ✅ **Smart Admin Integration**: Filament resources show "X technicians available" per timeslot and ranked technician selection with scores
- ✅ **Multi-Unit Pricing**: Dynamic pricing calculation with progressive discounts for multiple AC units
- ✅ **Comprehensive Test Data**: Enhanced seeders with realistic multi-unit, multi-day scenarios and service-specific ratings

### ✅ **Phase 3: Dynamic Rating System & Admin Organization Complete**
- ✅ **Dynamic Rating Categories**: 5 configurable rating categories (Work Quality, Punctuality, Cleanliness, Attitude, Tools)
- ✅ **Auto-Calculated Overall Ratings**: System automatically computes average ratings from category scores
- ✅ **Enhanced Database Schema**: New `review_categories` and `category_scores` tables for flexible rating management
- ✅ **Updated Greedy Algorithm**: Refined to pure service expertise focus for optimal technician selection
- ✅ **Organized Admin Navigation**: Professional navigation groups (Booking, Service, Technician, User, Review Management)
- ✅ **Diversified Technician Profiles**: Realistic expertise-based seeding with service specializations
- ✅ **Address-Only System**: Complete Philippine address system (Province/City/Barangay) with validation
- ✅ **Complete Integration**: All widgets, reports, and resources updated for new rating system

### ✅ **Phase 4: Customer Booking Interface Complete**
- ✅ **Customer Booking Frontend**: Full React booking form with multi-step wizard connecting to backend algorithm
- ✅ **Real-Time Availability Display**: Live technician availability checking with "X technicians available" display
- ✅ **Technician Selection Interface**: Customers see ranked technician options with ratings and greedy algorithm scores
- ✅ **Address Input Integration**: Complete Philippine address form with province/municipality/barangay dropdowns
- ✅ **Dynamic Pricing Integration**: Real-time cost calculation with multi-unit support
- ✅ **Booking Confirmation**: Success modals with booking numbers and details
- ✅ **AJAX API Endpoints**: Complete API for availability, technician ranking, and pricing

### ✅ **Phase 5: Customer Dashboard & Management Complete**
- ✅ **Customer Dashboard**: Comprehensive customer portal with statistics and recent bookings
- ✅ **Booking History Management**: Paginated booking history with filtering and status tracking
- ✅ **Real-Time Notifications**: Live notification system with unread count tracking
- ✅ **Cancellation Request System**: Customer-initiated cancellation with 24-hour rule enforcement
- ✅ **Review Submission Interface**: Category-based rating system for completed services
- ✅ **Profile Management**: Customer profile editing and address management
- ✅ **API Integration**: Complete customer API with error handling and validation

### 🔄 **Phase 6: Advanced Features (In Progress)**
- ✅ **Notification System**: Complete notification infrastructure with admin integration
- ✅ **Cancellation Management**: Full cancellation request workflow with admin processing
- ✅ **Enhanced Status Management**: Improved booking status colors and visual indicators
- 🔄 **Chat System Foundation**: Database models established (ChatSession, ChatMessage)
- 🔄 **Promotion System Foundation**: Database model established for future marketing features
- 🎯 **Technician Mobile Interface**: Enhanced technician dashboard features

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

### **Current Focus (Phase 6): Advanced Features & Production Readiness**
1. **🚀 Production Deployment Preparation**
   - Environment configuration and security hardening
   - Database optimization and indexing
   - Performance testing and optimization
   - Error monitoring and logging setup
   - Backup and recovery procedures

2. **📱 Mobile & Progressive Web App**
   - Responsive design enhancements for mobile devices
   - PWA implementation for better mobile experience
   - Offline capabilities for basic functionality
   - Push notification support

3. **💬 Real-Time Chat System**
   - Complete chat interface implementation
   - Real-time messaging with WebSocket/Pusher
   - Chat history and conversation management
   - Admin chat support capabilities

4. **🎁 Promotion & Marketing System**
   - Promotion management interface
   - Discount code system implementation
   - Seasonal pricing and special offers
   - Customer loyalty program features

5. **💳 Payment Integration**
   - Payment gateway integration (GCash, PayMaya, Bank transfers)
   - Payment status tracking and management
   - Receipt generation and email delivery
   - Refund processing for cancellations

### **Phase 5 Achievements (COMPLETED ✅)**
- ✅ **Customer Booking System**: Complete end-to-end booking flow with greedy algorithm
- ✅ **Customer Dashboard**: Full customer portal with booking management and notifications
- ✅ **Cancellation Management**: Self-service cancellation with 24-hour rule enforcement
- ✅ **Notification System**: Real-time notifications with admin integration
- ✅ **Review System**: Category-based rating system with service-specific tracking
- ✅ **API Integration**: Complete customer-facing API with error handling

### **Phase 4 Achievements (COMPLETED ✅)**
- ✅ **Multi-Step Booking Wizard**: React booking form with step-by-step guidance
- ✅ **Real-Time Availability**: Live technician availability with AJAX updates
- ✅ **Greedy Algorithm Integration**: Customer sees ranked technician options with scores
- ✅ **Dynamic Pricing**: Real-time cost calculation with multi-unit support
- ✅ **Philippine Address System**: Complete province/municipality/barangay management
- ✅ **Booking Confirmation**: Success modals with booking details and numbers

### **Success Metrics for Phase 6**
- 🎯 Production-ready deployment with monitoring and backups
- 🎯 Mobile-optimized experience with PWA capabilities
- 🎯 Real-time chat support between customers and technicians
- 🎯 Promotion system for marketing campaigns and customer retention
- 🎯 Payment processing for seamless transaction completion
- 🎯 Performance optimization for handling increased user load

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
- **Phase 4 (Completed)**: Customer booking frontend, API integration, real-time features (January 2025)
- **Phase 5 (Completed)**: Customer dashboard, notification system, cancellation management (January 2025)
- **Phase 6 (Current)**: Advanced features, chat system, promotion management, production readiness (January-February 2025)
- **Phase 7 (Planned)**: Final testing, deployment, performance optimization (February-March 2025)
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
*Version: 2.0.0 - Phase 5 Complete (Advanced Customer System with Booking, Dashboard, Notifications & Cancellation Management)*  
*Next Release: v3.0.0 - Production-Ready System with Chat, Promotions & Payment Integration (March 2025)*