import { useState, useEffect } from 'react';
import { PublicNavigation } from '@/components/public-navigation';
import { PublicFooter } from '@/components/public-footer';
import { ChevronLeft, ChevronRight, Check, Calendar, Clock, MapPin, Phone, User } from 'lucide-react';
import { router } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import axios from 'axios';

// Props interface for data from Laravel
interface BookingPageProps {
  services: Array<{
    id: number;
    name: string;
    description: string;
    base_price: number;
    duration_minutes: number;
    category: string;
    requires_parts: boolean;
  }>;
  airconTypes: Array<{
    id: number;
    name: string;
    description: string;
  }>;
  timeslots: Array<{
    id: number;
    display_time: string;
    start_time: string;
    end_time: string;
  }>;
  brands: string[];
  provinces: string[];
  municipalities: Record<string, string[]>;
  auth: {
    user: {
      id: number;
      name: string;
      email: string;
      phone: string;
      province?: string;
      city_municipality?: string;
      barangay?: string;
      house_no_street?: string;
      formatted_address?: string;
      has_structured_address: boolean;
    } | null;
  };
  csrf_token: string;
  error?: string;
}

// Real-time availability data
interface AvailabilityData {
  [timeslotId: number]: {
    timeslot_id: number;
    display_time: string;
    available_count: number;
    is_available: boolean;
  };
}

// Ranked technician data from Greedy Algorithm
interface RankedTechnician {
  id: string;
  name: string;
  rating: number;
  experience: string;
  specializations: string[];
  rank: number;
  greedy_score: number;
  service_review_count: number;
  service_completed_jobs: number;
}

interface BookingData {
  // Step 1: Service and Unit Details
  serviceType: number | string; // Change to accept service ID
  airconType: number | string; // Change to accept aircon type ID
  numberOfUnits: number;
  brand: string;
  
  // Step 2: Date and Time
  selectedDate: string;
  selectedTime: number | string; // Change to accept timeslot ID
  selectedTechnician: string;
  
  // Step 3: Contact Details
  customerName: string; // Customer name for guest bookings
  useCustomAddress: boolean; // Toggle for using different address
  mobileNumber: string;
  province: string;
  municipality: string;
  barangay: string;
  houseNumber: string;
  street: string;
  nearestLandmark: string;
}

// All data now comes from props - no hardcoded fallbacks



export default function Booking({ 
  services, 
  airconTypes, 
  timeslots, 
  brands, 
  provinces, 
  municipalities,
  auth,
  csrf_token,
  error 
}: BookingPageProps) {
  const [currentStep, setCurrentStep] = useState(1);
  const [bookingData, setBookingData] = useState<BookingData>({
    serviceType: '',
    airconType: '',
    numberOfUnits: 0,
    brand: 'Unknown',
    selectedDate: '',
    selectedTime: '',
    selectedTechnician: '',
    customerName: auth.user?.name || '',
    useCustomAddress: false,
    mobileNumber: auth.user?.phone || '',
    province: auth.user?.province || '',
    municipality: auth.user?.city_municipality || '',
    barangay: auth.user?.barangay || '',
    houseNumber: auth.user?.house_no_street || '',
    street: '',
    nearestLandmark: ''
  });

  // Real-time availability state
  const [availabilityData, setAvailabilityData] = useState<AvailabilityData>({});
  const [loadingAvailability, setLoadingAvailability] = useState(false);
  const [estimatedCost, setEstimatedCost] = useState(0);
  const [estimatedDays, setEstimatedDays] = useState(1);

  // AI Technician Ranking state
  const [rankedTechnicians, setRankedTechnicians] = useState<RankedTechnician[]>([]);
  const [loadingTechnicians, setLoadingTechnicians] = useState(false);

  // Booking success modal state
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  const [bookingNumber, setBookingNumber] = useState<string>('');

  // Auto-fill customer data if logged in
  useEffect(() => {
    if (auth.user) {
      setBookingData(prev => ({
        ...prev,
        customerName: auth.user?.name || prev.customerName,
        mobileNumber: auth.user?.phone || prev.mobileNumber,
        // Only auto-fill address if not using custom address
        province: prev.useCustomAddress ? prev.province : (auth.user?.province || prev.province),
        municipality: prev.useCustomAddress ? prev.municipality : (auth.user?.city_municipality || prev.municipality),
        barangay: prev.useCustomAddress ? prev.barangay : (auth.user?.barangay || prev.barangay),
        houseNumber: prev.useCustomAddress ? prev.houseNumber : (auth.user?.house_no_street || prev.houseNumber),
      }));
    }
  }, [auth.user]);

  // Calculate pricing when service details are complete
  useEffect(() => {
    if (bookingData.serviceType && bookingData.airconType && bookingData.numberOfUnits > 0) {
      calculateDynamicPricing(bookingData.serviceType, bookingData.airconType, bookingData.numberOfUnits);
    }
  }, [bookingData.serviceType, bookingData.airconType, bookingData.numberOfUnits]);

  // Use props data directly
  const availableServices = services || [];
  const availableAirconTypes = airconTypes || [];
  const availableBrands = brands || [];
  const availableProvinces = provinces || [];

  // Check availability when date changes
  const checkAvailability = async (date: string) => {
    if (!date) return;
    
    setLoadingAvailability(true);
    try {
      const response = await axios.get('/api/booking/availability', {
        params: { date }
      });
      
      if (response.data.success) {
        setAvailabilityData(response.data.data.availability);
      }
    } catch (error) {
      console.error('Error checking availability:', error);
      // No fallback - let user know to try again
      setAvailabilityData({});
    } finally {
      setLoadingAvailability(false);
    }
  };

  // Get ranked technicians using Greedy Algorithm
  const getRankedTechnicians = async (serviceId: number | string, date: string, timeslotId: number | string) => {
    if (!serviceId || !date || !timeslotId) return;
    
    setLoadingTechnicians(true);
    try {
      const response = await axios.get('/api/booking/technicians', {
        params: { 
          service_id: serviceId,
          date: date,
          timeslot_id: timeslotId
        },
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      });
      
      if (response.data.success) {
        const technicians = response.data.data.technicians;
        setRankedTechnicians(technicians);
      } else {
        console.error('🚨 API returned success=false:', response.data);
        throw new Error('API returned success=false');
      }
    } catch (error) {
      console.error('Error getting ranked technicians:', error);
      // No fallback - show empty state
      setRankedTechnicians([]);
    } finally {
      setLoadingTechnicians(false);
    }
  };

  // Calculate simple pricing without discounts
  const calculateDynamicPricing = async (serviceId: number | string, airconTypeId: number | string, numberOfUnits: number) => {
    if (!serviceId || !airconTypeId || numberOfUnits < 1) {
      setEstimatedCost(0);
      return;
    }
    
    try {
      const response = await axios.get('/api/booking/pricing', {
        params: { 
          service_id: serviceId,
          aircon_type_id: airconTypeId,
          number_of_units: numberOfUnits
        }
      });
      
      if (response.data.success) {
        setEstimatedCost(response.data.data.total_amount);
        // Calculate estimated days based on service and units
        const estimatedDays = calculateEstimatedDays(serviceId, numberOfUnits);
        setEstimatedDays(estimatedDays);
      }
    } catch (error) {
      console.error('Error calculating pricing:', error);
      setEstimatedCost(0);
    }
  };

  // Calculate estimated days based on service type and number of units
  const calculateEstimatedDays = (serviceId: number | string, numberOfUnits: number): number => {
    const service = availableServices.find(s => s.id.toString() === serviceId.toString());
    if (!service) return 1;

    // Base days calculation based on service type
    let baseDays = 1;
    if (service.category === 'installation') {
      baseDays = 2; // Installation takes longer
    }

    // Additional days for multiple units (every 3 units adds 1 day)
    if (numberOfUnits > 3) {
      baseDays += Math.ceil((numberOfUnits - 3) / 3);
    }

    return Math.max(1, baseDays);
  };

  const [availableMunicipalities, setAvailableMunicipalities] = useState<string[]>([]);
  const [availableBarangays, setAvailableBarangays] = useState<string[]>([]);
  const [showTimeSlots, setShowTimeSlots] = useState(false);
  const [showTechnicians, setShowTechnicians] = useState(false);
  const [currentCalendarDate, setCurrentCalendarDate] = useState(new Date());
  const [provinceSuggestions, setProvinceSuggestions] = useState<string[]>([]);
  const [municipalitySuggestions, setMunicipalitySuggestions] = useState<string[]>([]);
  const [barangaySuggestions, setBarangaySuggestions] = useState<string[]>([]);

  const handleInputChange = (field: keyof BookingData, value: string | number) => {
    setBookingData(prev => ({ ...prev, [field]: value }));
    
    // Trigger dynamic pricing when service details change
    if (field === 'serviceType' || field === 'airconType' || field === 'numberOfUnits') {
      const newData = { ...bookingData, [field]: value };
      if (newData.serviceType && newData.airconType && newData.numberOfUnits > 0) {
        calculateDynamicPricing(newData.serviceType, newData.airconType, newData.numberOfUnits);
      }
    }
    
    // Handle dependent dropdowns using dynamic data
    if (field === 'province') {
      const municipalityList = municipalities[value as string] || [];
      setAvailableMunicipalities(municipalityList);
      setBookingData(prev => ({ ...prev, municipality: '', barangay: '' }));
    }
    
    if (field === 'municipality') {
      // Clear barangay when municipality changes
      setAvailableBarangays([]);
      setBookingData(prev => ({ ...prev, barangay: '' }));
    }

    // Handle date selection to show time slots with real-time availability
    if (field === 'selectedDate') {
      setShowTimeSlots(true);
      setShowTechnicians(false);
      setBookingData(prev => ({ ...prev, selectedTime: '', selectedTechnician: '' }));
      
      // Check real-time availability
      checkAvailability(value as string);
    }

    // Handle time selection to show technicians with AI ranking
    if (field === 'selectedTime') {
      setShowTechnicians(true);
      setBookingData(prev => ({ ...prev, selectedTechnician: '' }));
      
      // Trigger AI technician ranking (Greedy Algorithm)
      if (bookingData.serviceType && bookingData.selectedDate) {
        getRankedTechnicians(bookingData.serviceType, bookingData.selectedDate, value as string);
      }
    }
  };

  const nextStep = () => {
    if (currentStep < 4) {
      setCurrentStep(currentStep + 1);
    }
  };

  const prevStep = () => {
    if (currentStep > 1) {
      setCurrentStep(currentStep - 1);
    }
  };

  const isStepValid = () => {
    switch (currentStep) {
      case 1:
        return bookingData.serviceType && bookingData.airconType && bookingData.numberOfUnits > 0;
      case 2:
        return bookingData.selectedDate && bookingData.selectedTime && bookingData.selectedTechnician;
      case 3:
        // For guest users, customer name is required
        const nameValid = auth.user || bookingData.customerName.trim();
        return nameValid && bookingData.mobileNumber && bookingData.province && bookingData.municipality && 
               bookingData.barangay && bookingData.houseNumber && bookingData.nearestLandmark.trim();
      case 4:
        // Final validation - all required fields must be filled
        const nameValidFinal = auth.user || bookingData.customerName.trim();
        return nameValidFinal && bookingData.serviceType && bookingData.airconType &&
               bookingData.numberOfUnits > 0 && bookingData.selectedDate && bookingData.selectedTime &&
               bookingData.selectedTechnician && bookingData.mobileNumber && bookingData.province &&
               bookingData.municipality && bookingData.barangay && bookingData.houseNumber && bookingData.nearestLandmark.trim();
      default:
        return true;
    }
  };

  // Dynamic cost calculation using API
  const getEstimatedCost = () => {
    if (estimatedCost > 0) {
      return estimatedCost;
    }
    
    // Use service base price if available
    const selectedService = availableServices.find(s => s.id.toString() === bookingData.serviceType.toString());
    if (selectedService) {
      return selectedService.base_price * bookingData.numberOfUnits;
    }
    
    return 0;
  };

  const handleSubmit = () => {
    // Prepare booking data for submission
    const submissionData = {
      _token: csrf_token,
      service_id: bookingData.serviceType,
      aircon_type_id: bookingData.airconType,
      number_of_units: bookingData.numberOfUnits,
      ac_brand: bookingData.brand,
      scheduled_date: bookingData.selectedDate,
      timeslot_id: bookingData.selectedTime,
      technician_id: bookingData.selectedTechnician,
      customer_mobile: bookingData.mobileNumber,
      province: bookingData.province,
      city_municipality: bookingData.municipality,
      barangay: bookingData.barangay,
      house_no_street: bookingData.houseNumber + (bookingData.street ? ', ' + bookingData.street : ''),
      nearest_landmark: bookingData.nearestLandmark,
      use_custom_address: bookingData.useCustomAddress,
      // Add customer name for guest bookings
      customer_name: auth.user ? undefined : bookingData.customerName
    };

    // Submit using Inertia
    router.post('/booking', submissionData, {
      onSuccess: (page) => {
        // Extract booking number from Inertia page props
        const pageProps = page.props as any;
        const bookingNum = pageProps?.booking?.booking_number || 'KMT-000000';
        
        setBookingNumber(bookingNum);
        setShowSuccessModal(true);
      },
      onError: (errors) => {
        console.error('Booking submission errors:', errors);
        alert('Failed to create booking. Please check your information and try again.');
      },
      onFinish: () => {
        // Can add loading state management here if needed
      }
    });
  };

  // Handle booking again
  const handleBookAgain = () => {
    setShowSuccessModal(false);
    setCurrentStep(1);
    setBookingData({
      serviceType: '',
      airconType: '',
      numberOfUnits: 0,
      brand: 'Unknown',
      selectedDate: '',
      selectedTime: '',
      selectedTechnician: '',
      customerName: auth.user?.name || '',
      useCustomAddress: false,
      mobileNumber: auth.user?.phone || '',
      province: auth.user?.province || '',
      municipality: auth.user?.city_municipality || '',
      barangay: auth.user?.barangay || '',
      houseNumber: auth.user?.house_no_street || '',
      street: '',
      nearestLandmark: ''
    });
    setRankedTechnicians([]);
    setEstimatedCost(0);
    setShowTimeSlots(false);
    setShowTechnicians(false);
  };

  // Handle go to home or dashboard based on user authentication
  const handleGoHome = () => {
    setShowSuccessModal(false);
    // Redirect to dashboard for logged-in users, homepage for guests
    if (auth.user) {
      router.get('/customer-dashboard');
    } else {
      router.get('/');
    }
  };

  // Handle address toggle between customer address and custom address
  const handleAddressToggle = (useCustom: boolean) => {
    setBookingData(prev => {
      if (useCustom) {
        // Switch to custom address - clear all address fields
        return {
          ...prev,
          useCustomAddress: true,
          province: '',
          municipality: '',
          barangay: '',
          houseNumber: '',
          street: '',
          nearestLandmark: ''
        };
      } else {
        // Switch back to customer address - restore user's address if available
        return {
          ...prev,
          useCustomAddress: false,
          province: auth.user?.province || '',
          municipality: auth.user?.city_municipality || '',
          barangay: auth.user?.barangay || '',
          houseNumber: auth.user?.house_no_street || '',
          street: '',
          nearestLandmark: ''
        };
      }
    });
  };

  // Success Modal Component
  const renderSuccessModal = () => {
    if (!showSuccessModal) return null;

    return (
      <div className="modal-overlay">
        <div className="modal-container">
          <div className="modal-content">
            <div className="success-icon">
              <div className="success-checkmark">
                <Check size={48} />
              </div>
            </div>
            
            <div className="success-header">
              <h2>🎉 Booking Completed Successfully!</h2>
              <p>Your air conditioning service has been scheduled</p>
            </div>
            
            <div className="booking-details">
              <div className="booking-number">
                <span className="label">Booking Number:</span>
                <span className="value">{bookingNumber}</span>
              </div>
              
              <div className="service-summary">
                <h3>Service Summary</h3>
                <div className="summary-grid">
                  <div className="summary-item">
                    <span className="summary-label">Service:</span>
                    <span className="summary-value">
                      {availableServices.find(s => s.id.toString() === bookingData.serviceType.toString())?.name || 'Selected Service'}
                    </span>
                  </div>
                  <div className="summary-item">
                    <span className="summary-label">Date & Time:</span>
                    <span className="summary-value">
                      {bookingData.selectedDate} 
                      {estimatedDays > 1 && (
                        <span className="multi-day-indicator">
                          {' '}- {new Date(new Date(bookingData.selectedDate).getTime() + (estimatedDays - 1) * 24 * 60 * 60 * 1000).toLocaleDateString()}
                          {' '}({estimatedDays} days)
                        </span>
                      )}
                      {' '}at {timeslots.find(t => t.id.toString() === bookingData.selectedTime.toString())?.display_time}
                      {estimatedDays > 1 && <span className="multi-day-note"> (daily)</span>}
                    </span>
                  </div>
                  <div className="summary-item">
                    <span className="summary-label">Technician:</span>
                    <span className="summary-value">
                      {rankedTechnicians.find(t => t.id === bookingData.selectedTechnician)?.name || 'Assigned Technician'}
                    </span>
                  </div>
                  <div className="summary-item">
                    <span className="summary-label">Estimated Cost:</span>
                    <span className="summary-value">₱{(estimatedCost || getEstimatedCost()).toLocaleString()}</span>
                  </div>
                </div>
              </div>
              
              <div className="next-steps">
                <h3>What's Next?</h3>
                <ul>
                  <li>📞 Our team will contact you shortly to confirm the appointment</li>
                  <li>💬 You'll receive SMS updates about your booking status</li>
                  <li>🔧 Your technician will arrive within the scheduled time slot</li>
                  <li>💳 Payment can be made after service completion</li>
                </ul>
              </div>
            </div>
            
            <div className="modal-actions">
              <button 
                onClick={handleBookAgain}
                className="btn btn-secondary btn-large"
              >
                📅 Book Another Service
              </button>
              <button 
                onClick={handleGoHome}
                className="btn btn-primary btn-large"
              >
                {auth.user ? '📊 Go to Dashboard' : '🏠 Go to Homepage'}
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  };

  // Generate available dates for the next 7 days
  const getAvailableDates = () => {
    const dates = [];
    const today = new Date();
    
    for (let i = 1; i <= 7; i++) {
      const date = new Date(today);
      date.setDate(today.getDate() + i);
      dates.push(date);
    }
    
    return dates;
  };

  // Get technician count from real-time availability data
  const getTechnicianCount = (timeslotId: number) => {
    const availability = availabilityData[timeslotId];
    return availability ? availability.available_count : 0;
  };

  // Helper function to format date in local timezone (fixes timezone issues)
  const formatDateToLocalString = (date: Date): string => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  };

  // Format date for display
  const formatDate = (date: Date) => {
    const months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    
    return {
      month: months[date.getMonth()],
      day: date.getDate(),
      dayName: days[date.getDay()],
      fullDate: formatDateToLocalString(date)
    };
  };

  // Calendar helper functions
  const getCalendarDays = () => {
    const year = currentCalendarDate.getFullYear();
    const month = currentCalendarDate.getMonth();
    
    // First day of the month
    const firstDay = new Date(year, month, 1);
    // Last day of the month
    const lastDay = new Date(year, month + 1, 0);
    
    // Start from the first Sunday of the week containing the first day
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - startDate.getDay());
    
    // End at the last Saturday of the week containing the last day
    const endDate = new Date(lastDay);
    endDate.setDate(endDate.getDate() + (6 - endDate.getDay()));
    
    const days = [];
    const currentDate = new Date(startDate);
    
    while (currentDate <= endDate) {
      days.push(new Date(currentDate));
      currentDate.setDate(currentDate.getDate() + 1);
    }
    
    return days;
  };

  const isDateSelectable = (date: Date) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    date.setHours(0, 0, 0, 0);
    return date > today; // Changed from >= to > to make today unavailable
  };

  const formatCalendarMonth = (date: Date) => {
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                   'July', 'August', 'September', 'October', 'November', 'December'];
    return `${months[date.getMonth()]} ${date.getFullYear()}`;
  };

  const goToPreviousMonth = () => {
    setCurrentCalendarDate(prev => {
      const newDate = new Date(prev);
      newDate.setMonth(prev.getMonth() - 1);
      return newDate;
    });
  };

  const goToNextMonth = () => {
    setCurrentCalendarDate(prev => {
      const newDate = new Date(prev);
      newDate.setMonth(prev.getMonth() + 1);
      return newDate;
    });
  };

  // Helper functions for autofill suggestions
  const getProvinceSuggestions = (input: string) => {
    if (!input) return [];
    return availableProvinces.filter(province => 
      province.toLowerCase().includes(input.toLowerCase())
    );
  };

  const getMunicipalitySuggestions = (input: string, province: string) => {
    if (!input || !municipalities) return [];
    const provinceMunicipalities = municipalities[province] || [];
    return provinceMunicipalities.filter(municipality =>
      municipality.toLowerCase().includes(input.toLowerCase())
    );
  };

  const getBarangaySuggestions = (input: string, municipality: string, province: string) => {
    if (!input) return [];
    
    // Basic barangay suggestions - can be enhanced with real API data
    const commonBarangays = [
      'Poblacion', 'Central', 'San Jose', 'San Juan', 'Santa Rosa'
    ];
    
    return commonBarangays.filter(barangay =>
      barangay.toLowerCase().includes(input.toLowerCase())
    );
  };

  const handleAddressInputChange = (field: 'province' | 'municipality' | 'barangay', value: string) => {
    setBookingData(prev => ({ ...prev, [field]: value }));

    if (field === 'province') {
      setProvinceSuggestions(getProvinceSuggestions(value));
      setMunicipalitySuggestions([]);
      setBarangaySuggestions([]);
      // Clear dependent fields when province changes
      setBookingData(prev => ({ ...prev, municipality: '', barangay: '' }));
    } else if (field === 'municipality') {
      setMunicipalitySuggestions(getMunicipalitySuggestions(value, bookingData.province));
      setBarangaySuggestions([]);
      setBookingData(prev => ({ ...prev, barangay: '' }));
    } else if (field === 'barangay') {
      setBarangaySuggestions(getBarangaySuggestions(value, bookingData.municipality, bookingData.province));
    }
  };

  const selectSuggestion = (field: 'province' | 'municipality' | 'barangay', value: string) => {
    setBookingData(prev => ({ ...prev, [field]: value }));
    
    if (field === 'province') {
      setProvinceSuggestions([]);
      setMunicipalitySuggestions([]);
      setBarangaySuggestions([]);
      // Clear dependent fields for any province change
      setBookingData(prev => ({ ...prev, municipality: '', barangay: '' }));
    } else if (field === 'municipality') {
      setMunicipalitySuggestions([]);
      setBarangaySuggestions([]);
      setBookingData(prev => ({ ...prev, barangay: '' }));
    } else if (field === 'barangay') {
      setBarangaySuggestions([]);
    }
  };

  const renderStepIndicator = () => (
    <div className="step-indicator">
      <div className="step-indicator-container">
        {[1, 2, 3, 4].map((step) => (
          <div key={step} className={`step-indicator-item ${currentStep >= step ? 'active' : ''}`}>
            <div className="step-indicator-number">
              {currentStep > step ? <Check size={20} /> : step}
            </div>
            <div className="step-indicator-label">
              {step === 1 && 'Service Details'}
              {step === 2 && 'Date & Time'}
              {step === 3 && 'Contact Info'}
              {step === 4 && 'Confirmation'}
            </div>
          </div>
        ))}
      </div>
    </div>
  );

  const renderStep1 = () => (
    <div className="booking-step">
      <div className="step-header">
        <h2>Service and Unit Details</h2>
        <p>Please provide information about the service you need and your air conditioning units.</p>
      </div>
      
      <div className="form-grid">
        <div className="form-group">
          <label htmlFor="serviceType">Service Type *</label>
          <select
            id="serviceType"
            value={bookingData.serviceType}
            onChange={(e) => handleInputChange('serviceType', e.target.value)}
            className="form-select"
          >
            <option value="">Select a service</option>
            {availableServices.map((service) => (
              <option key={service.id} value={service.id}>{service.name}</option>
            ))}
          </select>
        </div>

        <div className="form-group">
          <label htmlFor="airconType">Aircon Type *</label>
          <select
            id="airconType"
            value={bookingData.airconType}
            onChange={(e) => handleInputChange('airconType', e.target.value)}
            className="form-select"
          >
            <option value="">Select aircon type</option>
            {availableAirconTypes.map((type) => (
              <option key={type.id} value={type.id}>{type.name}</option>
            ))}
          </select>
        </div>

        <div className="form-group">
          <label htmlFor="numberOfUnits">Number of Units <span style={{color: '#dc2626'}}>*</span></label>
          <input
            type="number"
            id="numberOfUnits"
            min="0"
            max="10"
            value={bookingData.numberOfUnits}
            onChange={(e) => handleInputChange('numberOfUnits', parseInt(e.target.value))}
            className="form-input"
            placeholder="Enter number of units"
          />
        </div>

        <div className="form-group">
          <label htmlFor="brand">Brand</label>
          <select
            id="brand"
            value={bookingData.brand}
            onChange={(e) => handleInputChange('brand', e.target.value)}
            className="form-select"
          >
            <option value="">Select brand (optional)</option>
            <option value="Unknown">I don't know the brand</option>
            {availableBrands.map((brand) => (
              <option key={brand} value={brand}>{brand}</option>
            ))}
          </select>
        </div>
      </div>
      
      {/* Enhanced Pricing Display */}
      {bookingData.serviceType && bookingData.airconType && bookingData.numberOfUnits > 0 && (
        <div className="pricing-preview" style={{
          background: 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%)',
          border: '2px solid #2563eb',
          borderRadius: '16px',
          padding: '24px',
          marginTop: '24px',
          boxShadow: '0 8px 25px rgba(37, 99, 235, 0.15)'
        }}>
          <div className="pricing-display">
            <div className="price-header" style={{
              display: 'flex',
              alignItems: 'center',
              gap: '12px',
              marginBottom: '16px'
            }}>
              <div style={{
                width: '48px',
                height: '48px',
                background: 'linear-gradient(135deg, #2563eb, #1d4ed8)',
                borderRadius: '12px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: '24px'
              }}>💰</div>
              <div>
                <h3 style={{
                  margin: '0',
                  fontSize: '1.125rem',
                  fontWeight: '600',
                  color: '#1e293b'
                }}>Estimated Service Pricing</h3>

              </div>
            </div>
            
            <div className="price-breakdown" style={{
              background: 'white',
              borderRadius: '12px',
              padding: '20px',
              border: '1px solid #e2e8f0'
            }}>
              <div className="price-calculation" style={{
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center',
                marginBottom: '12px',
                paddingBottom: '12px',
                borderBottom: '1px solid #f1f5f9'
              }}>
                <span style={{
                  color: '#374151',
                  fontSize: '0.875rem',
                  fontWeight: '500'
                }}>Base Price × {bookingData.numberOfUnits} unit{bookingData.numberOfUnits > 1 ? 's' : ''}</span>
                <span style={{
                  color: '#64748b',
                  fontSize: '0.875rem'
                }}>Calculation</span>
              </div>
              
              <div className="price-total" style={{
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center'
              }}>
                <span style={{
                  color: '#374151',
                  fontSize: '1.125rem',
                  fontWeight: '600'
                }}>Estimated Total:</span>
                <div style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px'
                }}>
                  <span style={{
                    fontSize: '1.75rem',
                    fontWeight: 'bold',
                    color: '#2563eb',
                    letterSpacing: '-0.025em'
                  }}>
                    ₱{estimatedCost > 0 ? estimatedCost.toLocaleString() : getEstimatedCost().toLocaleString()}
                  </span>
                  <div style={{
                    background: '#dbeafe',
                    color: '#2563eb',
                    fontSize: '0.75rem',
                    fontWeight: '500',
                    padding: '4px 8px',
                    borderRadius: '6px'
                  }}>PHP</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );

  const renderStep2 = () => (
    <div className="booking-step">
      <div className="step-header">
        <h2>When do you need the service?</h2>
        <p>Choose your preferred date and time, then select from our available technicians.</p>
      </div>
      
      {/* Calendar Section */}
      <div className="calendar-section">
        <div className="calendar-header">
          <button 
            type="button" 
            onClick={goToPreviousMonth}
            className="calendar-nav-btn"
          >
            <ChevronLeft size={20} />
          </button>
          <h3 className="calendar-title">{formatCalendarMonth(currentCalendarDate)}</h3>
          <button 
            type="button" 
            onClick={goToNextMonth}
            className="calendar-nav-btn"
          >
            <ChevronRight size={20} />
          </button>
        </div>
        
        <div className="calendar-grid">
          <div className="calendar-weekdays">
            {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(day => (
              <div key={day} className="calendar-weekday">{day}</div>
            ))}
          </div>
          
          <div className="calendar-days">
            {getCalendarDays().map((date, index) => {
              const isCurrentMonth = date.getMonth() === currentCalendarDate.getMonth();
              const isSelectable = isDateSelectable(date);
              const isSelected = bookingData.selectedDate === formatDateToLocalString(date);
              const isToday = date.toDateString() === new Date().toDateString();
              
              return (
                <div
                  key={index}
                  className={`calendar-day ${
                    !isCurrentMonth ? 'other-month' : ''
                  } ${
                    !isSelectable ? 'disabled' : ''
                  } ${
                    isSelected ? 'selected' : ''
                  } ${
                    isToday ? 'today' : ''
                  }`}
                  onClick={() => {
                    if (isSelectable && isCurrentMonth) {
                      handleInputChange('selectedDate', formatDateToLocalString(date));
                    }
                  }}
                >
                  {date.getDate()}
                </div>
              );
            })}
          </div>
        </div>
      </div>

      {/* Time Slot Selection */}
      {showTimeSlots && (
        <div className="timeslot-selection-section">
          <h3>Select a timeslot</h3>
          {loadingAvailability && (
            <div className="loading-indicator">
              <p>Checking availability...</p>
            </div>
          )}
          <div className="timeslots-grid">
            {timeslots.map((timeslot) => {
              const availability = availabilityData[timeslot.id];
              const techCount = availability ? availability.available_count : 0;
              const isAvailable = availability ? availability.is_available : false;
              const isSelected = bookingData.selectedTime.toString() === timeslot.id.toString();
              
              return (
                <div
                  key={timeslot.id}
                  className={`timeslot-card ${
                    isSelected ? 'selected' : ''
                  } ${
                    !isAvailable ? 'unavailable' : ''
                  }`}
                  onClick={() => isAvailable && handleInputChange('selectedTime', timeslot.id)}
                  style={{ 
                    opacity: isAvailable ? 1 : 0.5,
                    cursor: isAvailable ? 'pointer' : 'not-allowed'
                  }}
                >
                  <div className="timeslot-time">{timeslot.display_time}</div>
                  <div className="timeslot-availability">
                    {isAvailable 
                      ? `${techCount} technician${techCount !== 1 ? 's' : ''} available`
                      : 'No technicians available'
                    }
                  </div>
                </div>
              );
            })}
          </div>
          <p className="timeslot-note">Technician arrives within the 1st hour of a timeslot.</p>
        </div>
      )}

      {/* Technician Selection - AI Ranked */}
      {showTechnicians && (
        <div className="technicians-section">
          <h3>🏆 AI-Ranked Technicians (Greedy Algorithm)</h3>
          <p>Our technicians are ranked using AI: <strong>Service Rating (70%) + Availability (30%)</strong></p>
          
          {loadingTechnicians && (
            <div className="loading-indicator">
              <p>Ranking technicians using AI algorithm...</p>
            </div>
          )}
          
          <div className="technicians-list">
            {rankedTechnicians.length > 0 ? (
              rankedTechnicians.map((technician) => (
                <div
                  key={technician.id}
                  className={`technician-item ${bookingData.selectedTechnician === technician.id ? 'selected' : ''}`}
                  onClick={() => handleInputChange('selectedTechnician', technician.id)}
                >
                  <div className="technician-info">
                    <div className="technician-header">
                      <h4>#{technician.rank} {technician.name}</h4>
                    </div>
                    <div className="technician-rating">
                      <span className="rating-stars">{'★'.repeat(Math.floor(technician.rating))}</span>
                      <span className="rating-number">{technician.rating}</span>
                      <span className="review-count">({technician.service_review_count} reviews)</span>
                    </div>
                  </div>
                  <div className="technician-select">
                    <div className={`select-circle ${bookingData.selectedTechnician === technician.id ? 'selected' : ''}`}>
                      {bookingData.selectedTechnician === technician.id && <Check size={16} />}
                    </div>
                  </div>
                </div>
              ))
            ) : (
              !loadingTechnicians && (
                <div className="no-technicians">
                  <p>No technicians available for the selected service and time. Please try a different time slot.</p>
                </div>
              )
            )}
          </div>
          
          {rankedTechnicians.length > 0 && (
            <div className="algorithm-info">
              <p className="algorithm-note">
                ℹ️ <strong>How our AI ranking works:</strong> We use a Greedy Algorithm that combines service-specific ratings (70% weight) 
                with real-time availability (30% weight) to find the best technician for your specific service.
              </p>
            </div>
          )}
        </div>
      )}
    </div>
  );

  const renderStep3 = () => (
    <div className="booking-step">
      <div className="step-header">
        <h2>Contact and Address Details</h2>
        <p>Please provide your contact information and complete address for service delivery.</p>
        
        {/* Enhanced Customer Account Status */}
        {auth.user ? (
          <div className="customer-status logged-in" style={{
            background: '#f8fafc',
            border: '2px solid #2563eb',
            borderRadius: '12px',
            padding: '20px',
            marginBottom: '24px'
          }}>
            <div style={{
              display: 'flex',
              alignItems: 'center',
              gap: '16px',
              marginBottom: '16px'
            }}>
              <div style={{
                width: '48px',
                height: '48px',
                background: '#2563eb',
                borderRadius: '50%',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: '20px',
                color: 'white'
              }}>✓</div>
              <div>
                <h4 style={{
                  margin: '0',
                  fontSize: '1.125rem',
                  fontWeight: '600',
                  color: '#1e293b'
                }}>Welcome back, {auth.user.name}!</h4>
                <p style={{
                  margin: '0',
                  fontSize: '0.875rem',
                  color: '#64748b'
                }}>Your information has been pre-filled for convenience</p>
              </div>
            </div>
            
            {/* Address Toggle for Logged-in Users - Improved Design */}
            <div className="service-location-container" style={{
              background: '#f8fafc',
              borderRadius: '12px',
              padding: '20px',
              border: '1px solid #e2e8f0',
              boxShadow: '0 1px 3px rgba(0, 0, 0, 0.1)'
            }}>
              {/* Header Section with Better Alignment */}
              <div className="service-location-header" style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                marginBottom: '16px',
                flexWrap: 'wrap',
                gap: '16px'
              }}>
                <div style={{
                  flex: '1 1 auto',
                  minWidth: '0'
                }}>
                  <h5 style={{
                    margin: '0',
                    fontSize: '1.125rem',
                    fontWeight: '600',
                    color: '#374151',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '10px'
                  }}>
                    📍 Service Location
                  </h5>
                </div>
                
                {/* Enhanced Toggle Switch */}
                <div className="service-location-toggle" style={{
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  gap: '8px',
                  flexShrink: 0
                }}>
                  <label style={{
                    position: 'relative',
                    display: 'inline-block',
                    width: '64px',
                    height: '36px',
                    cursor: 'pointer'
                  }}>
                    <input
                      type="checkbox"
                      checked={bookingData.useCustomAddress}
                      onChange={(e) => handleAddressToggle(e.target.checked)}
                      style={{ opacity: 0, width: 0, height: 0 }}
                    />
                    <span style={{
                      position: 'absolute',
                      cursor: 'pointer',
                      top: 0,
                      left: 0,
                      right: 0,
                      bottom: 0,
                      backgroundColor: bookingData.useCustomAddress ? '#2563eb' : '#d1d5db',
                      borderRadius: '36px',
                      transition: 'all 0.3s ease',
                      border: bookingData.useCustomAddress ? '2px solid #1d4ed8' : '2px solid #9ca3af'
                    }}>
                      <div style={{
                        position: 'absolute',
                        content: '""',
                        height: '28px',
                        width: '28px',
                        left: bookingData.useCustomAddress ? '32px' : '4px',
                        bottom: '2px',
                        backgroundColor: 'white',
                        borderRadius: '50%',
                        transition: 'all 0.3s ease',
                        boxShadow: '0 2px 6px rgba(0,0,0,0.2)',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        fontSize: '12px'
                      }}>
                        {bookingData.useCustomAddress ? '✓' : '○'}
                      </div>
                    </span>
                  </label>
                  <div style={{
                    fontSize: '0.75rem',
                    color: bookingData.useCustomAddress ? '#2563eb' : '#6b7280',
                    fontWeight: '600',
                    textAlign: 'center',
                    transition: 'color 0.3s ease'
                  }}>
                    {bookingData.useCustomAddress ? 'Custom' : 'Saved'}
                  </div>
                </div>
              </div>
              
              {/* Address Status Display */}
              <div className="service-location-status" style={{
                background: bookingData.useCustomAddress ? '#fefce8' : '#eff6ff',
                border: `2px solid ${bookingData.useCustomAddress ? '#eab308' : '#3b82f6'}`,
                borderRadius: '8px',
                padding: '16px',
                marginBottom: '12px'
              }}>
                <div style={{
                  display: 'flex',
                  alignItems: 'flex-start',
                  gap: '12px'
                }}>
                  <div style={{
                    fontSize: '20px',
                    flexShrink: 0,
                    marginTop: '2px'
                  }}>
                    {bookingData.useCustomAddress ? '🏠' : '📋'}
                  </div>
                  <div>
                    <h6 style={{
                      margin: '0 0 6px 0',
                      fontSize: '1rem',
                      fontWeight: '600',
                      color: bookingData.useCustomAddress ? '#a16207' : '#1e40af'
                    }}>
                      {bookingData.useCustomAddress 
                        ? 'Different Address Mode' 
                        : 'Registered Address Mode'
                      }
                    </h6>
                    <p style={{
                      margin: '0',
                      fontSize: '0.875rem',
                      color: bookingData.useCustomAddress ? '#a16207' : '#1e40af',
                      lineHeight: '1.4'
                    }}>
                      {bookingData.useCustomAddress 
                        ? 'Service will be provided at a different location. You can enter any address below.'
                        : 'Service will be provided at your registered address. Fields are auto-filled from your profile.'
                      }
                    </p>
                  </div>
                </div>
              </div>
              
              {/* Detailed Toggle Instructions - Mobile Responsive */}
              <div className="service-location-instructions" style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))',
                gap: '12px'
              }}>
                {/* OFF Mode Instructions */}
                <div style={{
                  background: !bookingData.useCustomAddress ? '#dbeafe' : '#f8fafc',
                  border: `1px solid ${!bookingData.useCustomAddress ? '#3b82f6' : '#e2e8f0'}`,
                  borderRadius: '6px',
                  padding: '12px',
                  opacity: !bookingData.useCustomAddress ? 1 : 0.6,
                  transition: 'all 0.3s ease'
                }}>
                  <div style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    marginBottom: '6px'
                  }}>
                    <span style={{ fontSize: '14px' }}>🔒</span>
                    <strong style={{
                      fontSize: '0.875rem',
                      color: !bookingData.useCustomAddress ? '#1e40af' : '#6b7280'
                    }}>Toggle OFF (Default)</strong>
                  </div>
                  <p style={{
                    margin: '0',
                    fontSize: '0.75rem',
                    color: !bookingData.useCustomAddress ? '#1e40af' : '#9ca3af',
                    lineHeight: '1.3'
                  }}>
                    Uses your profile address. Address fields are protected and pre-filled automatically.
                  </p>
                </div>
                
                {/* ON Mode Instructions */}
                <div style={{
                  background: bookingData.useCustomAddress ? '#fefce8' : '#f8fafc',
                  border: `1px solid ${bookingData.useCustomAddress ? '#eab308' : '#e2e8f0'}`,
                  borderRadius: '6px',
                  padding: '12px',
                  opacity: bookingData.useCustomAddress ? 1 : 0.6,
                  transition: 'all 0.3s ease'
                }}>
                  <div style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    marginBottom: '6px'
                  }}>
                    <span style={{ fontSize: '14px' }}>✏️</span>
                    <strong style={{
                      fontSize: '0.875rem',
                      color: bookingData.useCustomAddress ? '#a16207' : '#6b7280'
                    }}>Toggle ON (Custom)</strong>
                  </div>
                  <p style={{
                    margin: '0',
                    fontSize: '0.75rem',
                    color: bookingData.useCustomAddress ? '#a16207' : '#9ca3af',
                    lineHeight: '1.3'
                  }}>
                    Clears address fields so you can enter a different location for this service only.
                  </p>
                </div>
              </div>
            </div>
          </div>
        ) : (
          <div className="customer-status guest" style={{
            background: '#f8fafc',
            border: '2px solid #e2e8f0',
            borderRadius: '12px',
            padding: '20px',
            marginBottom: '24px'
          }}>
            <div style={{
              display: 'flex',
              alignItems: 'center',
              gap: '16px'
            }}>
              <div style={{
                width: '48px',
                height: '48px',
                background: '#6b7280',
                borderRadius: '50%',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: '20px',
                color: 'white'
              }}>👤</div>
              <div>
                <h4 style={{
                  margin: '0',
                  fontSize: '1.125rem',
                  fontWeight: '600',
                  color: '#1e293b'
                }}>Guest Booking</h4>
                <p style={{
                  margin: '0',
                  fontSize: '0.875rem',
                  color: '#64748b'
                }}>Booking as a guest. <a href="/register" style={{ color: '#2563eb', fontWeight: '500' }}>Create an account</a> to save your information for faster future bookings.</p>
              </div>
            </div>
          </div>
        )}
      </div>
      
      <div className="form-grid">
        {/* Customer Name Field - Only for guest bookings */}
        {!auth.user && (
          <div className="form-group" style={{ gridColumn: '1 / -1' }}>
            <label htmlFor="customerName">Full Name <span style={{color: '#dc2626'}}>*</span></label>
            <div className="input-with-icon">
              <User size={20} className="input-icon" />
              <input
                type="text"
                id="customerName"
                value={bookingData.customerName}
                onChange={(e) => handleInputChange('customerName', e.target.value)}
                className="form-input"
                placeholder="Enter your full name"
                required
              />
            </div>
          </div>
        )}
        
        <div className="form-group">
          <label htmlFor="mobileNumber">Mobile Number <span style={{color: '#dc2626'}}>*</span></label>
          <div className="input-with-icon">
            <Phone size={20} className="input-icon" />
            <input
              type="tel"
              id="mobileNumber"
              value={bookingData.mobileNumber}
              onChange={(e) => handleInputChange('mobileNumber', e.target.value)}
              className="form-input"
              placeholder="09XX XXX XXXX"
            />
          </div>
        </div>

        <div className="form-group">
          <label htmlFor="province">Province <span style={{color: '#dc2626'}}>*</span></label>
          <div className="autocomplete-container">
            <input
              type="text"
              id="province"
              value={bookingData.province}
              onChange={(e) => handleAddressInputChange('province', e.target.value)}
              className="form-input"
              placeholder="Enter province (e.g., Bataan)"
              autoComplete="off"
              disabled={!!auth.user && !bookingData.useCustomAddress}
              style={{
                backgroundColor: auth.user && !bookingData.useCustomAddress ? '#f8fafc' : undefined,
                cursor: auth.user && !bookingData.useCustomAddress ? 'not-allowed' : undefined
              }}
            />
            {provinceSuggestions.length > 0 && (
              <div className="autocomplete-suggestions">
                {provinceSuggestions.map((suggestion, index) => (
                  <div
                    key={index}
                    className="autocomplete-item"
                    onClick={() => selectSuggestion('province', suggestion)}
                  >
                    {suggestion}
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        <div className="form-group">
          <label htmlFor="municipality">Municipality/City <span style={{color: '#dc2626'}}>*</span></label>
          <div className="autocomplete-container">
            <input
              type="text"
              id="municipality"
              value={bookingData.municipality}
              onChange={(e) => handleAddressInputChange('municipality', e.target.value)}
              className="form-input"
              placeholder={`Enter municipality (e.g., ${municipalities?.[bookingData.province]?.[0] || 'Municipality'})`}
              autoComplete="off"
              disabled={!!auth.user && !bookingData.useCustomAddress}
              style={{
                backgroundColor: auth.user && !bookingData.useCustomAddress ? '#f8fafc' : undefined,
                cursor: auth.user && !bookingData.useCustomAddress ? 'not-allowed' : undefined
              }}
            />
            {municipalitySuggestions.length > 0 && (
              <div className="autocomplete-suggestions">
                {municipalitySuggestions.map((suggestion, index) => (
                  <div
                    key={index}
                    className="autocomplete-item"
                    onClick={() => selectSuggestion('municipality', suggestion)}
                  >
                    {suggestion}
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        <div className="form-group">
          <label htmlFor="barangay">Barangay <span style={{color: '#dc2626'}}>*</span></label>
          <div className="autocomplete-container">
            <input
              type="text"
              id="barangay"
              value={bookingData.barangay}
              onChange={(e) => handleAddressInputChange('barangay', e.target.value)}
              className="form-input"
              placeholder="Enter barangay (e.g., Poblacion)"
              autoComplete="off"
              disabled={!!auth.user && !bookingData.useCustomAddress}
              style={{
                backgroundColor: auth.user && !bookingData.useCustomAddress ? '#f8fafc' : undefined,
                cursor: auth.user && !bookingData.useCustomAddress ? 'not-allowed' : undefined
              }}
            />
            {barangaySuggestions.length > 0 && (
              <div className="autocomplete-suggestions">
                {barangaySuggestions.map((suggestion, index) => (
                  <div
                    key={index}
                    className="autocomplete-item"
                    onClick={() => selectSuggestion('barangay', suggestion)}
                  >
                    {suggestion}
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        <div className="form-group">
          <label htmlFor="houseNumber">House No. & Street <span style={{color: '#dc2626'}}>*</span></label>
          <input
            type="text"
            id="houseNumber"
            value={bookingData.houseNumber}
            onChange={(e) => handleInputChange('houseNumber', e.target.value)}
            className="form-input"
            placeholder="e.g., 123 Main Street"
            disabled={!!auth.user && !bookingData.useCustomAddress}
            style={{
              backgroundColor: auth.user && !bookingData.useCustomAddress ? '#f8fafc' : undefined,
              cursor: auth.user && !bookingData.useCustomAddress ? 'not-allowed' : undefined
            }}
          />
        </div>

        <div className="form-group">
          <label htmlFor="nearestLandmark">Nearest Landmark <span style={{color: '#dc2626'}}>*</span></label>
          <input
            type="text"
            id="nearestLandmark"
            value={bookingData.nearestLandmark}
            onChange={(e) => handleInputChange('nearestLandmark', e.target.value)}
            className="form-input"
            placeholder="e.g., Near SM Mall, Behind Church"
            required
          />
        </div>
      </div>
    </div>
  );

  const renderStep4 = () => (
    <div className="booking-step">
      <div className="step-header">
        <h2>Booking Summary</h2>
        <p>Please review your booking details before confirming.</p>
      </div>
      
      <div className="booking-summary">
        <div className="summary-section">
          <h3>Service Details</h3>
          <div className="summary-grid">
            <div className="summary-item">
              <span className="summary-label">Service Type:</span>
              <span className="summary-value">
                {availableServices.find(s => s.id.toString() === bookingData.serviceType.toString())?.name || bookingData.serviceType}
              </span>
            </div>
            <div className="summary-item">
              <span className="summary-label">Aircon Type:</span>
              <span className="summary-value">
                {availableAirconTypes.find(t => t.id.toString() === bookingData.airconType.toString())?.name || bookingData.airconType}
              </span>
            </div>
            <div className="summary-item">
              <span className="summary-label">Brand:</span>
              <span className="summary-value">{bookingData.brand}</span>
            </div>
            <div className="summary-item">
              <span className="summary-label">Number of Units:</span>
              <span className="summary-value">{bookingData.numberOfUnits}</span>
            </div>
          </div>
        </div>

        <div className="summary-section">
          <h3>Schedule</h3>
          <div className="summary-grid">
            <div className="summary-item">
              <span className="summary-label">Date:</span>
              <span className="summary-value">
                {new Date(bookingData.selectedDate).toLocaleDateString()}
                {estimatedDays > 1 && (
                  <span className="multi-day-indicator">
                    {' '}- {new Date(new Date(bookingData.selectedDate).getTime() + (estimatedDays - 1) * 24 * 60 * 60 * 1000).toLocaleDateString()}
                    {' '}({estimatedDays} days)
                  </span>
                )}
              </span>
            </div>
            <div className="summary-item">
              <span className="summary-label">Time:</span>
              <span className="summary-value">
                {timeslots.find(t => t.id.toString() === bookingData.selectedTime.toString())?.display_time || bookingData.selectedTime}
                {estimatedDays > 1 && <span className="multi-day-note"> (same time daily)</span>}
              </span>
            </div>
            <div className="summary-item">
              <span className="summary-label">Technician:</span>
              <span className="summary-value">
                {rankedTechnicians.find(t => t.id === bookingData.selectedTechnician)?.name || 'Selected Technician'}
              </span>
            </div>
          </div>
        </div>

        <div className="summary-section">
          <h3>Customer Information</h3>
          <div className="summary-grid">
            {auth.user ? (
              <>
                <div className="summary-item">
                  <span className="summary-label">Customer:</span>
                  <span className="summary-value">{auth.user.name} (Registered)</span>
                </div>
                <div className="summary-item">
                  <span className="summary-label">Email:</span>
                  <span className="summary-value">{auth.user.email}</span>
                </div>
              </>
            ) : (
              <>
                <div className="summary-item">
                  <span className="summary-label">Customer:</span>
                  <span className="summary-value">{bookingData.customerName || 'Guest Customer'} (Guest)</span>
                </div>
                <div className="summary-item">
                  <span className="summary-label">Booking Type:</span>
                  <span className="summary-value">Guest Booking</span>
                </div>
              </>
            )}
            <div className="summary-item">
              <span className="summary-label">Mobile Number:</span>
              <span className="summary-value">{bookingData.mobileNumber}</span>
            </div>
          </div>
        </div>

        <div className="summary-section">
          <h3>Service Location</h3>
          <div className="summary-grid">
            <div className="summary-item">
              <span className="summary-label">Address:</span>
              <span className="summary-value">
                {bookingData.houseNumber}, {bookingData.street}, {bookingData.barangay}, {bookingData.municipality}, {bookingData.province}
              </span>
            </div>
            {bookingData.nearestLandmark && (
              <div className="summary-item">
                <span className="summary-label">Nearest Landmark:</span>
                <span className="summary-value">{bookingData.nearestLandmark}</span>
              </div>
            )}
          </div>
        </div>

        <div className="summary-section cost-section">
          <h3>Cost Estimate</h3>
          <div className="cost-display">
            <span className="cost-label">Estimated Total:</span>
            <span className="cost-amount">₱{getEstimatedCost().toLocaleString()}</span>
          </div>
          <p className="cost-note">* Final cost may vary based on actual service requirements and any additional materials needed.</p>
        </div>
      </div>
    </div>
  );

  const renderCurrentStep = () => {
    switch (currentStep) {
      case 1:
        return renderStep1();
      case 2:
        return renderStep2();
      case 3:
        return renderStep3();
      case 4:
        return renderStep4();
      default:
        return null;
    }
  };

  return (
    <>
      <Head title="Book Your Service" />
      
      {renderSuccessModal()}
      
      <div className="booking-page">
        <PublicNavigation />
        
        {error && (
          <div className="error-banner">
            <div className="error-container">
              <p className="error-message">{error}</p>
            </div>
          </div>
        )}
        
        <main className="booking-main">
        <div className="booking-hero-section">
          <div className="booking-hero-container">
            <h1 className="booking-hero-title">Book Your Service</h1>
            <p className="booking-hero-subtitle">
              Schedule your air conditioning service with our expert technicians
            </p>
          </div>
        </div>

        <div className="booking-container">
          {renderStepIndicator()}
          
          <div className="booking-form-container">
            {renderCurrentStep()}
            
            <div className="booking-navigation">
              {currentStep > 1 && (
                <button
                  type="button"
                  onClick={prevStep}
                  className="btn btn-secondary"
                >
                  Previous
                </button>
              )}
              
              {currentStep < 4 ? (
                <button
                  type="button"
                  onClick={nextStep}
                  disabled={!isStepValid()}
                  className="btn btn-primary"
                >
                  Next
                </button>
              ) : (
                <button
                  type="button"
                  onClick={handleSubmit}
                  className="btn btn-primary btn-large"
                >
                  Confirm Booking
                </button>
              )}
            </div>
          </div>
        </div>
      </main>
      
      <PublicFooter />
    </div>
    </>
  );
}
