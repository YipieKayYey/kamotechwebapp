import { useState, useEffect } from 'react';
import { PublicNavigation } from '@/components/public-navigation';
import { AuthenticatedHeader } from '@/components/authenticated-header';
import { BookingHeader } from '@/components/booking-header';
import { PublicFooter } from '@/components/public-footer';
import { ChevronLeft, ChevronRight, Check, X, Calendar, Clock, MapPin, Phone, User, AlertCircle } from 'lucide-react';
import { router } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { ErrorBoundary } from '@/components/error-boundary';

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
  timeslots?: Array<{
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
      first_name?: string;
      middle_initial?: string;
      last_name?: string;
      email: string;
      phone: string;
      house_no_street?: string;
      barangay?: string;
      city_municipality?: string;
      province?: string;
      nearest_landmark?: string;
      full_address?: string;
    } | null;
  };
  csrf_token?: string;
  error?: string;
  booking?: {
    booking_number: string;
    id: number;
    total_amount: number;
    service_name: string;
    scheduled_start_at: string;
    scheduled_end_at: string;
  } | null;
  booking_success?: {
    booking_number: string;
    id: number;
    total_amount: number;
    service_name: string;
    scheduled_start_at: string;
    scheduled_end_at: string;
    message: string;
  } | null;
}

// Ranked technician data by service rating
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

// Promotion interface
interface Promotion {
  id: number;
  title: string;
  subtitle?: string;
  discount_type: 'percentage' | 'fixed' | 'free_service';
  discount_value: number;
  formatted_discount: string;
  promo_code: string | null;
  start_date: string;
  end_date: string;
  is_active: boolean;
}

interface BookingData {
  // Step 1: Service and Unit Details
  serviceType: number | string;
  airconType: number | string;
  numberOfUnits: number;
  brand: string;
  
  // Step 2: Dynamic Schedule (matching admin panel)
  scheduledStartAt: string; // Full datetime: "2025-01-05 09:00:00"
  scheduledEndAt: string;   // Full datetime: "2025-01-05 11:00:00"
  selectedTechnician: string;
  
  // Step 3: Contact Details
  customerName: string;
  useCustomAddress: boolean;
  mobileNumber: string;
  province: string;
  municipality: string;
  barangay: string;
  houseNumber: string;
  street: string;
  nearestLandmark: string;
  selectedPromotion: number | string;
}

// All data now comes from props - no hardcoded fallbacks

// Validation helpers to ensure data integrity
const isValidId = (id: any): boolean => {
  return id && !isNaN(Number(id)) && Number(id) > 0;
};

const isValidDateTime = (datetime: string): boolean => {
  if (!datetime) return false;
  const date = new Date(datetime);
  return date instanceof Date && !isNaN(date.getTime()) && Boolean(datetime.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/));
};

const isValidNumber = (num: any, min: number = 1): boolean => {
  return num && !isNaN(Number(num)) && Number(num) >= min;
};

// Format phone number to Philippine format (same logic used in account settings)
const formatPhilippinePhone = (input: string): string => {
  const digits = (input || '').replace(/\D/g, '');
  let formatted = digits;
  if (formatted.startsWith('63')) {
    formatted = formatted.substring(2);
  }
  if (formatted.length > 0 && formatted[0] === '9') {
    formatted = '0' + formatted;
  }
  formatted = formatted.substring(0, 11);
  if (formatted.length > 4 && formatted.length <= 7) {
    formatted = formatted.substring(0, 4) + '-' + formatted.substring(4);
  } else if (formatted.length > 7) {
    formatted = formatted.substring(0, 4) + '-' + formatted.substring(4, 7) + '-' + formatted.substring(7);
  }
  return formatted;
};

export default function Booking({
  services, 
  airconTypes, 
  brands, 
  provinces, 
  municipalities,
  auth,
  csrf_token,
  error,
  booking,
  booking_success
}: BookingPageProps) {
  const MAX_UNITS = 10;
  const userHasAddress = Boolean(
    (auth.user?.house_no_street && auth.user.house_no_street.trim()) ||
    (auth.user?.barangay && auth.user.barangay.trim()) ||
    (auth.user?.city_municipality && auth.user.city_municipality.trim()) ||
    (auth.user?.province && auth.user.province.trim())
  );
  const [currentStep, setCurrentStep] = useState(1);
  const [showExitWarning, setShowExitWarning] = useState(false);
  const [pendingNavigation, setPendingNavigation] = useState<string | null>(null);
  const [bookingData, setBookingData] = useState<BookingData>({
    serviceType: '',
    airconType: '',
    numberOfUnits: 0,
    brand: 'Unknown',
    scheduledStartAt: '',
    scheduledEndAt: '',
    selectedTechnician: '',
    customerName: auth.user?.name || '',
    // If user has no saved address, allow custom address entry by default
    useCustomAddress: !userHasAddress,
    mobileNumber: auth.user?.phone || '',
    province: auth.user?.province || '',
    municipality: auth.user?.city_municipality || '',
    barangay: auth.user?.barangay || '',
    houseNumber: auth.user?.house_no_street || '',
    street: '',
    nearestLandmark: auth.user?.nearest_landmark || '',
    selectedPromotion: ''
  });

  // Dynamic scheduling state
  const [isAvailable, setIsAvailable] = useState(false);
  const [availableTechnicianCount, setAvailableTechnicianCount] = useState(0);
  const [loadingAvailability, setLoadingAvailability] = useState(false);
  const [estimatedCost, setEstimatedCost] = useState(0);
  const [estimatedDays, setEstimatedDays] = useState(1);
  const [estimatedDuration, setEstimatedDuration] = useState(0);
  
  // Add loading states for each API call
  const [loadingEndTime, setLoadingEndTime] = useState(false);
  const [loadingPricing, setLoadingPricing] = useState(false);

  // AI Technician Ranking state
  const [rankedTechnicians, setRankedTechnicians] = useState<RankedTechnician[]>([]);
  const [loadingTechnicians, setLoadingTechnicians] = useState(false);
  
  // Promotions state
  const [availablePromotions, setAvailablePromotions] = useState<Promotion[]>([]);
  const [loadingPromotions, setLoadingPromotions] = useState(false);
  const [discountedPrice, setDiscountedPrice] = useState(0);
  
  // Date navigation state
  const [dateOffset, setDateOffset] = useState(0);

  // Booking success modal state
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  const [bookingNumber, setBookingNumber] = useState<string>('');
  const [bookingSuccess, setBookingSuccess] = useState(booking_success || null);

  // Set up axios defaults on component mount
  useEffect(() => {
    // Set CSRF token for all axios requests
    const token = csrf_token || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
      axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
  }, [csrf_token]);

  // Check for booking success on component mount
  useEffect(() => {
    if (booking_success) {
      setBookingNumber(booking_success.booking_number);
      setShowSuccessModal(true);
      setBookingSuccess(booking_success);
    }
  }, [booking_success]);

  // Auto-fill customer data if logged in
  useEffect(() => {
    if (auth.user) {
      setBookingData(prev => ({
        ...prev,
        customerName: auth.user?.name || prev.customerName,
        mobileNumber: auth.user?.phone || prev.mobileNumber,
        // Auto-fill address from user profile if not using custom address
        province: !prev.useCustomAddress && auth.user?.province ? auth.user.province : prev.province,
        municipality: !prev.useCustomAddress && auth.user?.city_municipality ? auth.user.city_municipality : prev.municipality,
        barangay: !prev.useCustomAddress && auth.user?.barangay ? auth.user.barangay : prev.barangay,
        houseNumber: !prev.useCustomAddress && auth.user?.house_no_street ? auth.user.house_no_street : prev.houseNumber,
        nearestLandmark: !prev.useCustomAddress && auth.user?.nearest_landmark ? auth.user.nearest_landmark : prev.nearestLandmark,
      }));
    }
  }, [auth.user]);

  // Calculate pricing when service details are complete
  useEffect(() => {
    // Only calculate if we have valid numeric values
    if (bookingData.serviceType && Number(bookingData.serviceType) > 0 && 
        bookingData.airconType && Number(bookingData.airconType) > 0 && 
        bookingData.numberOfUnits && Number(bookingData.numberOfUnits) > 0) {
      calculateDynamicPricing(bookingData.serviceType, bookingData.airconType, bookingData.numberOfUnits);
      // Fetch available promotions
      fetchAvailablePromotions(bookingData.serviceType, bookingData.airconType);
    } else {
      setAvailablePromotions([]);
      setDiscountedPrice(0);
    }
  }, [bookingData.serviceType, bookingData.airconType, bookingData.numberOfUnits]);

  // Update discounted price when promotion is selected
  useEffect(() => {
    if (estimatedCost > 0) {
      calculateDiscountedPrice(estimatedCost, bookingData.selectedPromotion);
    }
  }, [bookingData.selectedPromotion, estimatedCost]);

  // Use props data directly
  const availableServices = services || [];
  const availableAirconTypes = airconTypes || [];
  const availableBrands = brands || [];
  const availableProvinces = provinces || [];

  // Check availability for dynamic time window
  const checkDynamicAvailability = async (startAt: string, endAt: string) => {
    // Comprehensive validation
    if (!isValidDateTime(startAt) || !isValidDateTime(endAt)) {
      console.warn('Invalid datetime format for availability check', { startAt, endAt });
      return;
    }
    
    // Ensure end time is after start time
    if (new Date(startAt) >= new Date(endAt)) {
      console.warn('End time must be after start time');
      return;
    }
    
    // Prevent multiple simultaneous calls
    if (loadingAvailability) {
      console.log('Availability check already in progress');
      return;
    }
    
    setLoadingAvailability(true);
    try {
      const requestData = {
        start_datetime: startAt,
        end_datetime: endAt,
        service_id: isValidId(bookingData.serviceType) ? Number(bookingData.serviceType) : null
      };
      
      console.log('Checking availability with data:', requestData);
      
      const response = await axios.post('/api/booking/availability', requestData);
      
      if (response.data.success) {
        const data = response.data.data;
        setAvailableTechnicianCount(data.available_count || 0);
        setIsAvailable(data.is_available || false);
        
        // Auto-fetch technicians if available
        if (data.is_available && isValidId(bookingData.serviceType)) {
          await getDynamicRankedTechnicians(startAt, endAt);
        }
      }
    } catch (error: any) {
      console.error('Error checking availability:', error);
      if (error.response?.status === 419) {
        console.error('CSRF token mismatch. Refreshing token.');
        const freshToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (freshToken) {
          axios.defaults.headers.common['X-CSRF-TOKEN'] = freshToken;
        }
      } else if (error.response?.status === 410) {
        console.error('API endpoint not available (410). Please check server configuration.');
      }
      setIsAvailable(false);
      setAvailableTechnicianCount(0);
    } finally {
      setLoadingAvailability(false);
    }
  };

  // Get ranked technicians for dynamic time window
  const getDynamicRankedTechnicians = async (startAt: string, endAt: string) => {
    // Comprehensive validation
    if (!isValidId(bookingData.serviceType) || !isValidDateTime(startAt) || !isValidDateTime(endAt)) {
      console.warn('Invalid data for technician ranking', { 
        serviceType: bookingData.serviceType, 
        startAt, 
        endAt 
      });
      return;
    }
    
    // Prevent multiple simultaneous calls
    if (loadingTechnicians) {
      console.log('Technician ranking already in progress');
      return;
    }
    
    setLoadingTechnicians(true);
    try {
      const requestData = {
        start_datetime: startAt,
        end_datetime: endAt,
        service_id: Number(bookingData.serviceType)
      };
      
      console.log('Getting ranked technicians with data:', requestData);
      
      const response = await axios.post('/api/booking/technicians', requestData);
      
      if (response.data.success) {
        setRankedTechnicians(response.data.data.technicians || []);
      }
    } catch (error: any) {
      console.error('Failed to get technicians:', error);
      if (error.response?.status === 419) {
        console.error('CSRF token mismatch. Refreshing token.');
        const freshToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (freshToken) {
          axios.defaults.headers.common['X-CSRF-TOKEN'] = freshToken;
        }
      } else if (error.response?.status === 410) {
        console.error('API endpoint not available (410). Please check server configuration.');
      }
      setRankedTechnicians([]);
    } finally {
      setLoadingTechnicians(false);
    }
  };

  // Calculate end time based on service duration
  const calculateEndTime = async (startDateTime: string) => {
    // Comprehensive validation
    if (!isValidDateTime(startDateTime)) {
      console.warn('Invalid start datetime for calculateEndTime:', startDateTime);
      return '';
    }
    
    if (!isValidId(bookingData.serviceType)) {
      console.warn('Invalid service type for calculateEndTime:', bookingData.serviceType);
      return '';
    }
    
    if (!isValidNumber(bookingData.numberOfUnits, 1)) {
      console.warn('Invalid number of units for calculateEndTime:', bookingData.numberOfUnits);
      return '';
    }
    
    // Prevent multiple simultaneous calls
    if (loadingEndTime) {
      console.log('End time calculation already in progress');
      return '';
    }
    
    setLoadingEndTime(true);
    try {
      const requestData = {
        start_datetime: startDateTime,
        service_id: Number(bookingData.serviceType),
        number_of_units: Number(bookingData.numberOfUnits)
      };
      
      console.log('Calculating end time with validated data:', requestData);
      
      const response = await axios.post('/api/booking/calculate-end-time', requestData);
      
      if (response.data?.success && response.data?.data) {
        const data = response.data.data;
        setEstimatedDuration(data.duration_minutes || 0);
        setEstimatedDays(data.estimated_days || 1);
        return data.end_datetime || '';
      } else {
        console.warn('Unexpected response format from calculateEndTime:', response.data);
        return '';
      }
    } catch (error: any) {
      console.error('Failed to calculate end time:', error);
      if (error.response?.status === 419) {
        console.error('CSRF token mismatch. Page may need to be refreshed.');
        // Try to get a fresh CSRF token
        const freshToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (freshToken) {
          axios.defaults.headers.common['X-CSRF-TOKEN'] = freshToken;
        }
      } else if (error.response?.status === 410) {
        console.error('API endpoint not available (410). Please check server configuration.');
      } else if (error.response?.data) {
        console.error('Error response:', error.response.data);
      }
      // Return empty string to prevent cascading errors
      return '';
    } finally {
      setLoadingEndTime(false);
    }
  };

  // Validate if time slot is within business hours (8AM-12PM, 1PM-5PM)
  const isWithinBusinessHours = (hour: number): boolean => {
    // Morning shift: 8AM - 12PM
    if (hour >= 8 && hour < 12) return true;
    // Afternoon shift: 1PM - 5PM  
    if (hour >= 13 && hour < 17) return true;
    // Lunch break (12PM - 1PM) and after hours
    return false;
  };

  // Format datetime for display
  const formatDateTime = (dateTimeStr: string): string => {
    if (!dateTimeStr) return '';
    const dt = new Date(dateTimeStr);
    const year = dt.getFullYear();
    const month = String(dt.getMonth() + 1).padStart(2, '0');
    const day = String(dt.getDate()).padStart(2, '0');
    const hours = String(dt.getHours()).padStart(2, '0');
    const minutes = String(dt.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}:00`;
  };

  // Format datetime for user-friendly display
  const formatReadableDateTime = (dateTimeStr: string): string => {
    if (!dateTimeStr) return '';
    const dt = new Date(dateTimeStr);
    const options: Intl.DateTimeFormatOptions = {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    };
    return dt.toLocaleString('en-US', options);
  };

  // Handle schedule change
  const handleScheduleChange = async (type: 'start' | 'end', value: string) => {
    if (type === 'start') {
      setBookingData(prev => ({ ...prev, scheduledStartAt: value }));
      
      // Auto-calculate end time
      if (bookingData.serviceType && bookingData.numberOfUnits) {
        const endTime = await calculateEndTime(value);
        if (endTime) {
          setBookingData(prev => ({ ...prev, scheduledEndAt: endTime }));
          // Check availability for the new window
          await checkDynamicAvailability(value, endTime);
        }
      }
    } else {
      setBookingData(prev => ({ ...prev, scheduledEndAt: value }));
      if (bookingData.scheduledStartAt) {
        await checkDynamicAvailability(bookingData.scheduledStartAt, value);
      }
    }
  };

  // Update end time when service or units change
  useEffect(() => {
    const updateEndTime = async () => {
      // Only update if we have a valid start time and all required data
      if (bookingData.scheduledStartAt && bookingData.scheduledStartAt.length > 0 && 
          bookingData.serviceType && Number(bookingData.serviceType) > 0 && 
          bookingData.numberOfUnits && Number(bookingData.numberOfUnits) > 0) {
        const endTime = await calculateEndTime(bookingData.scheduledStartAt);
        if (endTime) {
          setBookingData(prev => ({ ...prev, scheduledEndAt: endTime }));
        }
      }
    };
    updateEndTime();
  }, [bookingData.serviceType, bookingData.numberOfUnits]);

  // Format promotion duration
  const formatPromotionDuration = (startDate: string, endDate: string) => {
    // Handle missing or invalid dates
    if (!startDate || !endDate) {
      return 'No expiration';
    }
    
    // Parse dates - handle both ISO format and YYYY-MM-DD format
    const start = new Date(startDate);
    const end = new Date(endDate);
    const now = new Date();
    
    // Check if dates are valid
    if (isNaN(start.getTime()) || isNaN(end.getTime())) {
      console.warn('Invalid promotion dates:', { startDate, endDate });
      return 'Check promotion details';
    }
    
    // Calculate days remaining
    const daysRemaining = Math.ceil((end.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
    
    if (daysRemaining <= 0) {
      return 'Expired';
    } else if (daysRemaining === 1) {
      return 'Expires today';
    } else if (daysRemaining <= 7) {
      return `${daysRemaining} days left`;
    } else {
      try {
        return `Valid until ${end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
      } catch (error) {
        console.warn('Error formatting date:', error);
        return `Valid for ${daysRemaining} more days`;
      }
    }
  };

  // Auto-select smallest percentage discount
  const autoSelectSmallestPercentageDiscount = (promotions: Promotion[]) => {
    // Filter percentage discounts and find the smallest one
    const percentageDiscounts = promotions.filter(p => p.discount_type === 'percentage');
    if (percentageDiscounts.length > 0) {
      const smallestDiscount = percentageDiscounts.reduce((smallest, current) => 
        current.discount_value < smallest.discount_value ? current : smallest
      );
      setBookingData(prev => ({ ...prev, selectedPromotion: smallestDiscount.id }));
      return smallestDiscount.id;
    }
    return '';
  };

  // Fetch available promotions for selected service and aircon type
  const fetchAvailablePromotions = async (serviceId: number | string, airconTypeId: number | string) => {
    if (!isValidId(serviceId) || !isValidId(airconTypeId)) {
      setAvailablePromotions([]);
      return;
    }
    
    setLoadingPromotions(true);
    try {
      const params = {
        service_id: Number(serviceId),
        aircon_type_id: Number(airconTypeId)
      };
      
      const response = await axios.get('/api/booking/promotions', { params });
      
      if (response.data?.success && response.data?.data) {
        const promotions = response.data.data.promotions || [];
        setAvailablePromotions(promotions);
        
        // Auto-select smallest percentage discount if no promotion is currently selected
        if (promotions.length > 0 && !bookingData.selectedPromotion) {
          autoSelectSmallestPercentageDiscount(promotions);
        }
      } else {
        setAvailablePromotions([]);
      }
    } catch (error: any) {
      console.error('Error fetching promotions:', error);
      setAvailablePromotions([]);
    } finally {
      setLoadingPromotions(false);
    }
  };

  // Calculate discounted price based on selected promotion
  const calculateDiscountedPrice = (originalPrice: number, promotionId: number | string) => {
    if (!promotionId || originalPrice <= 0) {
      setDiscountedPrice(originalPrice);
      return originalPrice;
    }
    
    const promotion = availablePromotions.find(p => p.id.toString() === promotionId.toString());
    if (!promotion) {
      setDiscountedPrice(originalPrice);
      return originalPrice;
    }
    
    let discounted = originalPrice;
    
    switch (promotion.discount_type) {
      case 'percentage':
        discounted = originalPrice * (1 - promotion.discount_value / 100);
        break;
      case 'fixed':
        discounted = Math.max(0, originalPrice - promotion.discount_value);
        break;
      case 'free_service':
        discounted = 0;
        break;
    }
    
    setDiscountedPrice(Math.round(discounted * 100) / 100);
    return discounted;
  };

  // Calculate simple pricing without discounts
  const calculateDynamicPricing = async (serviceId: number | string, airconTypeId: number | string, numberOfUnits: number) => {
    // Comprehensive validation
    if (!isValidId(serviceId) || !isValidId(airconTypeId) || !isValidNumber(numberOfUnits, 1)) {
      console.warn('Invalid params for calculateDynamicPricing', { serviceId, airconTypeId, numberOfUnits });
      setEstimatedCost(0);
      return;
    }
    
    // Prevent multiple simultaneous calls
    if (loadingPricing) {
      console.log('Pricing calculation already in progress');
      return;
    }
    
    setLoadingPricing(true);
    try {
      const params = { 
        service_id: Number(serviceId),
        aircon_type_id: Number(airconTypeId),
        number_of_units: Number(numberOfUnits)
      };
      
      console.log('Calculating pricing with validated params:', params);
      
      const response = await axios.get('/api/booking/pricing', { params });
      
      if (response.data?.success && response.data?.data) {
        const totalAmount = response.data.data.total_amount;
        if (typeof totalAmount === 'number' && totalAmount >= 0) {
          setEstimatedCost(totalAmount);
          // Calculate estimated days based on service and units
          const estimatedDays = calculateEstimatedDays(serviceId, numberOfUnits);
          setEstimatedDays(estimatedDays);
        } else {
          console.warn('Invalid total_amount received:', totalAmount);
          setEstimatedCost(0);
        }
      } else {
        console.warn('Unexpected response format from pricing API:', response.data);
        setEstimatedCost(0);
      }
    } catch (error: any) {
      console.error('Error calculating pricing:', error);
      if (error.response?.status === 419) {
        console.error('CSRF token mismatch. Refreshing token.');
        const freshToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (freshToken) {
          axios.defaults.headers.common['X-CSRF-TOKEN'] = freshToken;
        }
      } else if (error.response?.status === 410) {
        console.error('API endpoint not available (410). Please check server configuration.');
      } else if (error.response?.data) {
        console.error('Pricing error response:', error.response.data);
      }
      setEstimatedCost(0);
    } finally {
      setLoadingPricing(false);
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
  const [showTechnicians, setShowTechnicians] = useState(false);
  const [currentCalendarDate, setCurrentCalendarDate] = useState(new Date());
  const [provinceSuggestions, setProvinceSuggestions] = useState<string[]>([]);
  const [municipalitySuggestions, setMunicipalitySuggestions] = useState<string[]>([]);
  const [barangaySuggestions, setBarangaySuggestions] = useState<string[]>([]);

  // DB-backed autosuggest helpers
  const fetchProvinces = async (q: string) => {
    if (!q || q.length < 1) return [] as string[];
    const res = await axios.get('/internal/locations/search', { params: { type: 'province', q, limit: 8 } });
    return (res.data as { id: number; text: string }[]).map(r => r.text);
  };

  const fetchCities = async (q: string, provinceName: string) => {
    if (!q || !provinceName) return [] as string[];
    // Resolve province id by name (minimal resolver via a quick province query)
    const provRes = await axios.get('/internal/locations/search', { params: { type: 'province', q: provinceName, limit: 1 } });
    const provinceId = (provRes.data?.[0]?.id) as number | undefined;
    if (!provinceId) return [] as string[];
    const res = await axios.get('/internal/locations/search', { params: { type: 'city', parent_id: provinceId, q, limit: 10 } });
    return (res.data as { id: number; text: string }[]).map(r => r.text);
  };

  const fetchBarangays = async (q: string, cityName: string, provinceName: string) => {
    if (!q || !cityName) return [] as string[];
    // Resolve province -> city id chain
    const provRes = await axios.get('/internal/locations/search', { params: { type: 'province', q: provinceName, limit: 1 } });
    const provinceId = (provRes.data?.[0]?.id) as number | undefined;
    if (!provinceId) return [] as string[];
    const cityRes = await axios.get('/internal/locations/search', { params: { type: 'city', parent_id: provinceId, q: cityName, limit: 1 } });
    const cityId = (cityRes.data?.[0]?.id) as number | undefined;
    if (!cityId) return [] as string[];
    const res = await axios.get('/internal/locations/search', { params: { type: 'barangay', parent_id: cityId, q, limit: 12 } });
    return (res.data as { id: number; text: string }[]).map(r => r.text);
  };

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

    // Dynamic scheduling is now handled by handleScheduleChange function
  };

  // Strict handler for units to prevent excessively large values or non-digits
  const handleUnitsChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    // Allow only digits, clamp to MAX_UNITS, treat empty as 0
    const digitsOnly = e.target.value.replace(/\D/g, '');
    const parsed = digitsOnly === '' ? 0 : parseInt(digitsOnly, 10);
    const clamped = Math.min(MAX_UNITS, Math.max(0, parsed));
    setBookingData(prev => ({ ...prev, numberOfUnits: clamped }));
  };

  const incrementUnits = () => {
    setBookingData(prev => ({
      ...prev,
      numberOfUnits: Math.min(MAX_UNITS, (prev.numberOfUnits || 0) + 1)
    }));
  };

  const decrementUnits = () => {
    setBookingData(prev => ({
      ...prev,
      numberOfUnits: Math.max(0, (prev.numberOfUnits || 0) - 1)
    }));
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
        // NEW: Validate dynamic datetime fields
        return bookingData.scheduledStartAt && bookingData.scheduledEndAt && bookingData.selectedTechnician;
      case 3:
        // For guest users, customer name is required
        const nameValid = auth.user || bookingData.customerName.trim();
        return nameValid && bookingData.mobileNumber && bookingData.province && bookingData.municipality && 
               bookingData.barangay && bookingData.houseNumber && bookingData.nearestLandmark.trim();
      case 4:
        // Final validation - all required fields must be filled
        const nameValidFinal = auth.user || bookingData.customerName.trim();
        return nameValidFinal && bookingData.serviceType && bookingData.airconType &&
               bookingData.numberOfUnits > 0 && bookingData.scheduledStartAt && bookingData.scheduledEndAt &&
               bookingData.selectedTechnician && bookingData.mobileNumber && bookingData.province &&
               bookingData.municipality && bookingData.barangay && bookingData.houseNumber && bookingData.nearestLandmark.trim();
      default:
        return true;
    }
  };
  
  // Check if user has made any progress in the booking
  const hasBookingProgress = () => {
    return (
      bookingData.serviceType !== '' ||
      bookingData.airconType !== '' ||
      bookingData.numberOfUnits > 0 ||
      bookingData.scheduledStartAt !== '' ||
      bookingData.selectedTechnician !== '' ||
      (bookingData.province !== '' && bookingData.province !== auth.user?.province) ||
      (bookingData.municipality !== '' && bookingData.municipality !== auth.user?.city_municipality) ||
      (bookingData.barangay !== '' && bookingData.barangay !== auth.user?.barangay) ||
      (bookingData.houseNumber !== '' && bookingData.houseNumber !== auth.user?.house_no_street) ||
      (bookingData.nearestLandmark !== '' && bookingData.nearestLandmark !== auth.user?.nearest_landmark) ||
      (!auth.user && bookingData.customerName !== '')
    );
  };
  
  // Handle navigation with warning
  const handleNavigation = (destination: string) => {
    if (hasBookingProgress()) {
      setPendingNavigation(destination);
      setShowExitWarning(true);
    } else {
      router.get(destination);
    }
  };
  
  // Confirm navigation
  const confirmNavigation = () => {
    if (pendingNavigation) {
      router.get(pendingNavigation);
    }
    setShowExitWarning(false);
    setPendingNavigation(null);
  };
  
  // Cancel navigation
  const cancelNavigation = () => {
    setShowExitWarning(false);
    setPendingNavigation(null);
  };

  // Dynamic cost calculation using API
  const getEstimatedCost = () => {
    // If we have a promotion selected, return discounted price
    if (bookingData.selectedPromotion && discountedPrice > 0) {
      return discountedPrice;
    }
    
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
    // Prepare booking data for submission with dynamic scheduling
    const submissionData = {
      _token: csrf_token || '',
      service_id: bookingData.serviceType,
      aircon_type_id: bookingData.airconType,
      number_of_units: bookingData.numberOfUnits,
      ac_brand: bookingData.brand,
      // NEW: Dynamic datetime fields
      scheduled_start_at: bookingData.scheduledStartAt,
      scheduled_end_at: bookingData.scheduledEndAt,
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

    // Submit using Inertia with preserveState to handle redirect properly
    router.post('/booking', submissionData, {
      preserveState: false,
      preserveScroll: false,
      onSuccess: () => {
        // Success is handled by the redirect with flash data
        // The page will reload with booking_success data
      },
      onError: (errors) => {
        console.error('Booking submission errors:', errors);
        alert('Failed to create booking. Please check your information and try again.');
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
      // NEW: Reset dynamic datetime fields
      scheduledStartAt: '',
      scheduledEndAt: '',
      selectedTechnician: '',
      customerName: auth.user?.name || '',
      useCustomAddress: true,
      mobileNumber: auth.user?.phone || '',
      province: '',
      municipality: '',
      barangay: '',
      houseNumber: '',
      street: '',
      nearestLandmark: '',
      selectedPromotion: ''
    });
    setRankedTechnicians([]);
    setEstimatedCost(0);
    setDiscountedPrice(0);
    setAvailablePromotions([]);
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
        // Since users no longer have saved addresses, always use custom address entry
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
      }
    });
  };

  // Exit Warning Modal Component
  const renderExitWarningModal = () => {
    if (!showExitWarning) return null;
    
    return (
      <div className="modal-overlay" style={{ zIndex: 9999 }}>
        <div className="modal-container" style={{ maxWidth: '500px' }}>
          <div className="modal-content" style={{ padding: '2rem' }}>
            <div className="warning-icon" style={{
              display: 'flex',
              justifyContent: 'center',
              marginBottom: '1.5rem'
            }}>
              <div style={{
                width: '64px',
                height: '64px',
                background: 'linear-gradient(135deg, #fee2e2, #fecaca)',
                borderRadius: '50%',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                border: '3px solid #ef4444'
              }}>
                <AlertCircle size={32} style={{ color: '#dc2626' }} />
              </div>
            </div>
            
            <div style={{ textAlign: 'center', marginBottom: '2rem' }}>
              <h2 style={{
                fontSize: '1.5rem',
                fontWeight: '700',
                color: '#1e293b',
                marginBottom: '0.75rem'
              }}>
                Leave Booking?
              </h2>
              <p style={{
                fontSize: '1rem',
                color: '#64748b',
                lineHeight: '1.5'
              }}>
                You have unsaved booking progress. If you leave now, all your entered information will be lost.
              </p>
            </div>
            
            <div style={{
              display: 'flex',
              gap: '1rem',
              justifyContent: 'center'
            }}>
              <button
                onClick={cancelNavigation}
                className="btn btn-secondary"
                style={{
                  minWidth: '140px',
                  padding: '0.75rem 1.5rem',
                  fontSize: '1rem',
                  fontWeight: '600'
                }}
              >
                Stay Here
              </button>
              <button
                onClick={confirmNavigation}
                className="btn btn-primary"
                style={{
                  minWidth: '140px',
                  padding: '0.75rem 1.5rem',
                  fontSize: '1rem',
                  fontWeight: '600',
                  background: '#dc2626',
                  borderColor: '#dc2626'
                }}
              >
                Leave Anyway
              </button>
            </div>
          </div>
        </div>
      </div>
    );
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
                      {bookingSuccess?.service_name || availableServices.find(s => s.id.toString() === bookingData.serviceType.toString())?.name || 'Selected Service'}
                    </span>
                  </div>
                  <div className="summary-item">
                    <span className="summary-label">Date & Time:</span>
                    <span className="summary-value">
                      {formatReadableDateTime(bookingSuccess?.scheduled_start_at || bookingData.scheduledStartAt)} 
                      {estimatedDays > 1 && (
                        <span className="multi-day-indicator">
                          {' '}(Multi-day: {estimatedDays} days)
                        </span>
                      )}
                    </span>
                  </div>
                </div>
              </div>
              
              <div className="next-steps">
                <h3>What's Next?</h3>
                <ul>
                  <li>📞 Our team will contact you shortly to confirm the appointment</li>
                  <li>💬 You'll receive SMS notification if your booking is confirmed</li>
                  <li>🔧 Your technician will arrive within the first hour of the scheduled start time </li>
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
    // This function is no longer used with dynamic scheduling
    return 0;
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
  const getProvinceSuggestions = async (input: string) => {
    if (!input) return [] as string[];
    try {
      return await fetchProvinces(input);
    } catch {
      return [] as string[];
    }
  };

  const getMunicipalitySuggestions = async (input: string, province: string) => {
    if (!input || !province) return [] as string[];
    try {
      return await fetchCities(input, province);
    } catch {
      return [] as string[];
    }
  };

  const getBarangaySuggestions = async (input: string, municipality: string, province: string) => {
    if (!input) return [] as string[];
    try {
      return await fetchBarangays(input, municipality, province);
    } catch {
      return [] as string[];
    }
  };

  const handleAddressInputChange = async (field: 'province' | 'municipality' | 'barangay', value: string) => {
    setBookingData(prev => ({ ...prev, [field]: value }));

    if (field === 'province') {
      setProvinceSuggestions(await getProvinceSuggestions(value));
      setMunicipalitySuggestions([]);
      setBarangaySuggestions([]);
      // Clear dependent fields when province changes
      setBookingData(prev => ({ ...prev, municipality: '', barangay: '' }));
    } else if (field === 'municipality') {
      setMunicipalitySuggestions(await getMunicipalitySuggestions(value, bookingData.province));
      setBarangaySuggestions([]);
      setBookingData(prev => ({ ...prev, barangay: '' }));
    } else if (field === 'barangay') {
      setBarangaySuggestions(await getBarangaySuggestions(value, bookingData.municipality, bookingData.province));
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
          <div className="units-stepper">
            <button
              type="button"
              className="stepper-btn"
              aria-label="Decrease"
              onClick={decrementUnits}
              disabled={bookingData.numberOfUnits <= 0}
            >
              −
            </button>
            <input
              type="text"
              id="numberOfUnits"
              value={bookingData.numberOfUnits}
              onChange={handleUnitsChange}
              className="form-input stepper-input"
              inputMode="none"
              readOnly
              aria-live="polite"
            />
            <button
              type="button"
              className="stepper-btn"
              aria-label="Increase"
              onClick={incrementUnits}
              disabled={bookingData.numberOfUnits >= MAX_UNITS}
            >
              +
            </button>
          </div>
          <div className="stepper-hint">Max {MAX_UNITS} units</div>
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
            
            {/* Enhanced Promotions Selection */}
            {availablePromotions.length > 0 && (
              <>
                <div style={{
                  marginTop: '16px',
                  paddingTop: '16px',
                  borderTop: '1px solid #f1f5f9'
                }}>
                  <div className="form-group" style={{ marginBottom: '16px' }}>
                    <label style={{
                      fontSize: '0.875rem',
                      fontWeight: '600',
                      color: '#374151',
                      marginBottom: '12px',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '8px'
                    }}>
                      🎁 Available Promotions
                      {loadingPromotions && (
                        <div style={{
                          width: '16px',
                          height: '16px',
                          border: '2px solid #e2e8f0',
                          borderTop: '2px solid #2563eb',
                          borderRadius: '50%',
                          animation: 'spin 1s linear infinite'
                        }}></div>
                      )}
                    </label>
                    
                    {/* Custom Dropdown with Enhanced Design */}
                    <div className="custom-promotion-dropdown" style={{
                      position: 'relative',
                      width: '100%'
                    }}>
                      <select
                        id="promotion"
                        value={bookingData.selectedPromotion}
                        onChange={(e) => handleInputChange('selectedPromotion', e.target.value)}
                        style={{
                          width: '100%',
                          padding: '12px 16px',
                          fontSize: '0.875rem',
                          fontWeight: '500',
                          borderRadius: '12px',
                          border: '2px solid #e2e8f0',
                          background: 'white',
                          color: '#1e293b',
                          cursor: 'pointer',
                          transition: 'all 0.2s',
                          appearance: 'none',
                          backgroundImage: `url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e")`,
                          backgroundPosition: 'right 12px center',
                          backgroundRepeat: 'no-repeat',
                          backgroundSize: '16px',
                          paddingRight: '48px'
                        }}
                        onFocus={(e) => {
                          e.target.style.borderColor = '#2563eb';
                          e.target.style.boxShadow = '0 0 0 3px rgba(37, 99, 235, 0.1)';
                        }}
                        onBlur={(e) => {
                          e.target.style.borderColor = '#e2e8f0';
                          e.target.style.boxShadow = 'none';
                        }}
                      >
                        <option value="" style={{ padding: '8px' }}>
                          No discount applied
                        </option>
                        {availablePromotions.map((promotion) => (
                          <option key={promotion.id} value={promotion.id} style={{ padding: '8px' }}>
                            {promotion.title} - {promotion.formatted_discount} • {formatPromotionDuration(promotion.start_date, promotion.end_date)}
                          </option>
                        ))}
                      </select>
                    </div>
                    
                    {/* Promotion Details Display */}
                    {bookingData.selectedPromotion && (
                      <div style={{
                        marginTop: '12px',
                        padding: '12px',
                        background: 'linear-gradient(135deg, #f0f9ff, #e0f2fe)',
                        borderRadius: '8px',
                        border: '1px solid #0ea5e9',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '12px'
                      }}>
                        <div style={{
                          width: '32px',
                          height: '32px',
                          background: '#0ea5e9',
                          borderRadius: '50%',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          fontSize: '16px'
                        }}>🎉</div>
                        <div style={{ flex: 1 }}>
                          {(() => {
                            const selectedPromo = availablePromotions.find(p => p.id.toString() === bookingData.selectedPromotion.toString());
                            return selectedPromo ? (
                              <div>
                                <div style={{
                                  fontSize: '0.875rem',
                                  fontWeight: '600',
                                  color: '#0c4a6e',
                                  marginBottom: '2px'
                                }}>
                                  {selectedPromo.title}
                                </div>
                                <div style={{
                                  fontSize: '0.75rem',
                                  color: '#0369a1',
                                  display: 'flex',
                                  alignItems: 'center',
                                  gap: '8px'
                                }}>
                                  <span>{selectedPromo.formatted_discount}</span>
                                  <span>•</span>
                                  <span>{formatPromotionDuration(selectedPromo.start_date, selectedPromo.end_date)}</span>
                                </div>
                              </div>
                            ) : null;
                          })()}
                        </div>
                      </div>
                    )}
                  </div>
                  
                  {/* Show discounted price if promotion is selected */}
                  {bookingData.selectedPromotion && (
                    <div style={{
                      background: '#dcfce7',
                      borderRadius: '8px',
                      padding: '12px',
                      marginTop: '12px',
                      border: '1px solid #16a34a'
                    }}>
                      <div style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        marginBottom: '8px'
                      }}>
                        <span style={{
                          color: '#166534',
                          fontSize: '0.875rem',
                          fontWeight: '500'
                        }}>Original Price:</span>
                        <span style={{
                          color: '#166534',
                          fontSize: '1rem',
                          textDecoration: 'line-through',
                          opacity: 0.7
                        }}>₱{estimatedCost.toLocaleString()}</span>
                      </div>
                      <div style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center'
                      }}>
                        <span style={{
                          color: '#166534',
                          fontSize: '1rem',
                          fontWeight: '600'
                        }}>Discounted Price:</span>
                        <div style={{
                          display: 'flex',
                          alignItems: 'center',
                          gap: '8px'
                        }}>
                          <span style={{
                            fontSize: '1.5rem',
                            fontWeight: 'bold',
                            color: '#16a34a'
                          }}>₱{discountedPrice.toLocaleString()}</span>
                          <div style={{
                            background: '#16a34a',
                            color: 'white',
                            fontSize: '0.75rem',
                            fontWeight: '500',
                            padding: '4px 8px',
                            borderRadius: '6px'
                          }}>{availablePromotions.find(p => p.id.toString() === bookingData.selectedPromotion.toString())?.formatted_discount}</div>
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              </>
            )}
          </div>
        </div>
      )}
    </div>
  );

  const renderStep2 = () => {
    // Date pagination config - show 7 days per page (one week)
    const daysPerPage = 7;
    
    // Generate days based on offset
    const getVisibleDays = () => {
      const days = [];
      const today = new Date();
      const startDay = dateOffset + 1; // Start from tomorrow
      
      for (let i = startDay; i < startDay + daysPerPage; i++) {
        const date = new Date();
        date.setDate(today.getDate() + i);
        days.push(date);
      }
      return days;
    };
    
    // Time slots matching admin panel business hours (8AM-12PM, 1PM-5PM, lunch break 12PM-1PM)
    const timeSlots = [
      { hour: 8, display: '8:00 AM', available: true },
      { hour: 9, display: '9:00 AM', available: true },
      { hour: 10, display: '10:00 AM', available: true },
      { hour: 11, display: '11:00 AM', available: true },
      // { hour: 12, display: '12:00 PM', available: false }, // Lunch break - removed
      { hour: 13, display: '1:00 PM', available: true },
      { hour: 14, display: '2:00 PM', available: true },
      { hour: 15, display: '3:00 PM', available: true },
      { hour: 16, display: '4:00 PM', available: true },
    ];
    
    const availableDays = getVisibleDays();
    const selectedDate = bookingData.scheduledStartAt ? new Date(bookingData.scheduledStartAt).toDateString() : '';
    const selectedHour = bookingData.scheduledStartAt ? new Date(bookingData.scheduledStartAt).getHours() : null;
    
    // Navigation handlers - allow up to 365 days (1 year)
    const canGoBack = dateOffset > 0;
    const canGoForward = dateOffset < 358; // Show up to 365 days total (1 year)
    
    return (
      <div className="booking-step">
        <div className="step-header">
          <h2>When do you need the service?</h2>
          <p>Choose your preferred date and time</p>
        </div>
        
        {/* Date Selection - Boxed Calendar Style with Navigation */}
        <div className="date-selection-section" style={{ marginBottom: '32px' }}>
          <div style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            marginBottom: '16px'
          }}>
            <div>
              <h3 style={{ 
                fontSize: '1rem', 
                fontWeight: '600',
                color: '#1e293b',
                display: 'flex',
                alignItems: 'center',
                gap: '8px',
                margin: 0
              }}>
                <Calendar size={18} style={{ color: '#2563eb' }} />
                Select Date
              </h3>
              <p style={{
                fontSize: '0.875rem',
                color: '#64748b',
                margin: '4px 0 0 0'
              }}>
                Showing {availableDays[0]?.toLocaleDateString('en-US', { month: 'short', year: 'numeric' })} - {availableDays[availableDays.length - 1]?.toLocaleDateString('en-US', { month: 'short', year: 'numeric' })}
              </p>
            </div>
            <div style={{
              display: 'flex',
              gap: '8px',
              alignItems: 'center'
            }}>
              <button
                type="button"
                onClick={() => setDateOffset(Math.max(0, dateOffset - daysPerPage))}
                disabled={!canGoBack}
                style={{
                  width: '32px',
                  height: '32px',
                  borderRadius: '8px',
                  border: '1px solid #e2e8f0',
                  background: canGoBack ? 'white' : '#f8fafc',
                  color: canGoBack ? '#1e293b' : '#cbd5e1',
                  cursor: canGoBack ? 'pointer' : 'not-allowed',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  transition: 'all 0.2s'
                }}
                className="date-nav-button"
                title="Previous week"
              >
                <ChevronLeft size={16} />
              </button>
              <button
                type="button"
                onClick={() => setDateOffset(Math.min(358, dateOffset + daysPerPage))}
                disabled={!canGoForward}
                style={{
                  width: '32px',
                  height: '32px',
                  borderRadius: '8px',
                  border: '1px solid #e2e8f0',
                  background: canGoForward ? 'white' : '#f8fafc',
                  color: canGoForward ? '#1e293b' : '#cbd5e1',
                  cursor: canGoForward ? 'pointer' : 'not-allowed',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  transition: 'all 0.2s'
                }}
                className="date-nav-button"
                title="Next week"
              >
                <ChevronRight size={16} />
              </button>
              <div style={{
                fontSize: '0.75rem',
                color: '#64748b',
                marginLeft: '8px',
                padding: '4px 8px',
                background: '#f1f5f9',
                borderRadius: '4px'
              }}>
                {Math.floor(dateOffset / daysPerPage) + 1} of {Math.floor(358 / daysPerPage) + 1}
              </div>
            </div>
          </div>
          <div style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fill, minmax(110px, 1fr))',
            gap: '10px'
          }}>
            {availableDays.map((date) => {
              const isSelected = selectedDate === date.toDateString();
              const dayName = date.toLocaleDateString('en-US', { weekday: 'short' });
              const monthName = date.toLocaleDateString('en-US', { month: 'short' });
              const dayNum = date.getDate();
              
              return (
                <div
                  key={date.toISOString()}
                  onClick={() => {
                    // Set date portion of scheduledStartAt
                    const newDate = new Date(date);
                    if (selectedHour !== null) {
                      newDate.setHours(selectedHour, 0, 0, 0);
                      const formatted = formatDateTime(newDate.toISOString());
                      handleScheduleChange('start', formatted);
                    } else {
                      // Store date temporarily until time is selected
                      setBookingData(prev => ({ 
                        ...prev, 
                        scheduledStartAt: formatDateTime(date.toISOString()).split(' ')[0] + ' 00:00:00'
                      }));
                    }
                  }}
                  style={{
                    border: `2px solid ${isSelected ? '#2563eb' : '#e2e8f0'}`,
                    borderRadius: '12px',
                    padding: '16px',
                    cursor: 'pointer',
                    textAlign: 'center',
                    background: isSelected ? '#eff6ff' : 'white',
                    transition: 'all 0.2s'
                  }}
                  className="date-box"
                >
                  <div style={{ 
                    fontSize: '0.875rem', 
                    color: '#64748b',
                    marginBottom: '4px'
                  }}>
                    {dayName}
                  </div>
                  <div style={{ 
                    fontSize: '1.5rem', 
                    fontWeight: 'bold',
                    color: isSelected ? '#2563eb' : '#1e293b',
                    marginBottom: '4px'
                  }}>
                    {dayNum}
                  </div>
                  <div style={{ 
                    fontSize: '0.75rem', 
                    color: '#64748b'
                  }}>
                    {monthName}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
        
        {/* Time Selection - Button Grid Style */}
        <div className="time-selection-section" style={{ marginBottom: '32px' }}>
          <h3 style={{ 
            fontSize: '1rem', 
            fontWeight: '600', 
            marginBottom: '16px',
            color: '#1e293b',
            display: 'flex',
            alignItems: 'center',
            gap: '8px'
          }}>
            <Clock size={18} style={{ color: '#2563eb' }} />
            Select Time
          </h3>
          <div style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fill, minmax(100px, 1fr))',
            gap: '10px'
          }}>
            {timeSlots.map((slot) => {
              const isSelected = selectedHour === slot.hour;
              const isDisabled = !bookingData.scheduledStartAt || bookingData.scheduledStartAt === '00:00:00' || !slot.available;
              
              return (
                <button
                  key={slot.hour}
                  type="button"
                  onClick={() => {
                    if (bookingData.scheduledStartAt && slot.available) {
                      const datePart = bookingData.scheduledStartAt.split(' ')[0];
                      const newDateTime = `${datePart} ${slot.hour.toString().padStart(2, '0')}:00:00`;
                      handleScheduleChange('start', newDateTime);
                    }
                  }}
                  disabled={isDisabled}
                  style={{
                    padding: '12px',
                    border: `2px solid ${isSelected ? '#2563eb' : '#e2e8f0'}`,
                    borderRadius: '8px',
                    background: isSelected ? '#2563eb' : isDisabled ? '#f8fafc' : 'white',
                    color: isSelected ? 'white' : isDisabled ? '#cbd5e1' : '#1e293b',
                    fontWeight: isSelected ? '600' : '500',
                    cursor: isDisabled ? 'not-allowed' : 'pointer',
                    opacity: isDisabled ? 0.5 : 1,
                    transition: 'all 0.2s'
                  }}
                  className="time-button"
                  title={!slot.available ? 'Lunch break (12PM-1PM)' : ''}
                >
                  {slot.display}
                </button>
              );
            })}
          </div>
          {(!bookingData.scheduledStartAt || bookingData.scheduledStartAt.includes('00:00:00')) && (
            <p style={{
              marginTop: '12px',
              fontSize: '0.875rem',
              color: '#ef4444'
            }}>
              Please select a date first
            </p>
          )}
        </div>
        
        {/* Schedule Summary - Show when both date and time are selected */}
        {bookingData.scheduledStartAt && bookingData.scheduledEndAt && selectedHour !== null && (
          <div className="schedule-summary" style={{
            background: 'linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)',
            border: '2px solid #e2e8f0',
            borderRadius: '16px',
            padding: '24px',
            marginBottom: '32px'
          }}>
            <h3 style={{
              fontSize: '1rem',
              fontWeight: '600',
              marginBottom: '16px',
              color: '#1e293b',
              display: 'flex',
              alignItems: 'center',
              gap: '8px'
            }}>
              <Check size={18} style={{ color: '#16a34a' }} />
              Service Schedule 
            </h3>
            
            <div style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
              gap: '20px'
            }}>
              {/* Start Time */}
              <div>
                <label style={{
                  display: 'block',
                  fontSize: '0.75rem',
                  color: '#64748b',
                  marginBottom: '4px',
                  textTransform: 'uppercase',
                  letterSpacing: '0.05em'
                }}>
                  Start Time
                </label>
                <div style={{
                  fontSize: '1.125rem',
                  fontWeight: '600',
                  color: '#1e293b'
                }}>
                  {formatReadableDateTime(bookingData.scheduledStartAt)}
                </div>
              </div>
              
              {/* End Time (Read-only) */}
              <div>
                <label style={{
                  display: 'block',
                  fontSize: '0.75rem',
                  color: '#64748b',
                  marginBottom: '4px',
                  textTransform: 'uppercase',
                  letterSpacing: '0.05em'
                }}>
                  Estimated End Time
                </label>
                <div style={{
                  fontSize: '1.125rem',
                  fontWeight: '600',
                  color: '#1e293b',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px'
                }}>
                  {formatReadableDateTime(bookingData.scheduledEndAt)}
                </div>
              </div>
              
              {/* Duration */}
              {estimatedDuration > 0 && (
                <div>
                  <label style={{
                    display: 'block',
                    fontSize: '0.75rem',
                    color: '#64748b',
                    marginBottom: '4px',
                    textTransform: 'uppercase',
                    letterSpacing: '0.05em'
                  }}>
                    Service Duration
                  </label>
                  <div style={{
                    fontSize: '1.125rem',
                    fontWeight: '600',
                    color: '#1e293b'
                  }}>
                    {Math.floor(estimatedDuration / 60)} hour{Math.floor(estimatedDuration / 60) !== 1 ? 's' : ''}
                    {estimatedDuration % 60 > 0 && ` ${estimatedDuration % 60} minutes`}
                  </div>
                </div>
              )}
            </div>
            
          </div>
        )}
        
        {/* Real-time Availability Check */}
        {bookingData.scheduledStartAt && bookingData.scheduledEndAt && (
          <div className="availability-check" style={{ marginBottom: '32px' }}>
            {loadingAvailability ? (
              <div className="loading-indicator" style={{
                textAlign: 'center',
                padding: '20px',
                background: '#f8fafc',
                borderRadius: '8px'
              }}>
                <p>Checking technician availability...</p>
              </div>
            ) : (
              <div className={`availability-status ${isAvailable ? 'available' : 'unavailable'}`} style={{
                padding: '20px',
                borderRadius: '12px',
                background: isAvailable ? '#dcfce7' : '#fee2e2',
                border: `2px solid ${isAvailable ? '#16a34a' : '#dc2626'}`,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between'
              }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                  {isAvailable ? (
                    <Check size={24} style={{ color: '#16a34a' }} />
                  ) : (
                    <X size={24} style={{ color: '#dc2626' }} />
                  )}
                  <div>
                    <p style={{ 
                      margin: 0, 
                      fontWeight: '600', 
                      color: isAvailable ? '#166534' : '#991b1b',
                      fontSize: '1.125rem'
                    }}>
                      {isAvailable 
                        ? 'Technicians Available'
                        : 'No technicians available for this time'}
                    </p>
                    <p style={{ 
                      margin: '4px 0 0', 
                      fontSize: '0.875rem', 
                      color: isAvailable ? '#166534' : '#991b1b' 
                    }}>
                      {isAvailable 
                        ? 'You can proceed with technician selection'
                        : 'Please select a different time'}
                    </p>
                  </div>
                </div>
              </div>
            )}
          </div>
        )}
        
        {/* Technician Selection - Top Available */}
        {bookingData.scheduledStartAt && bookingData.scheduledEndAt && isAvailable && (
        <div className="technicians-section">
          <h3>⭐ Available Technician</h3>
          <p>Our Recommended technician for <strong>{availableServices.find(s => s.id.toString() === bookingData.serviceType)?.name || 'your service'}</strong>. Click to choose the technician.</p>
          
          {loadingTechnicians && (
            <div className="loading-indicator">
              <p>Finding the best technician for you...</p>
            </div>
          )}
          
          <div className="technicians-list">
            {rankedTechnicians.length > 0 ? (
              <>
                {/* Show only the top 1 technician */}
                {rankedTechnicians.slice(0, 1).map((technician) => (
                  <div
                    key={technician.id}
                    className={`technician-item ${bookingData.selectedTechnician === technician.id ? 'selected' : ''}`}
                    onClick={() => handleInputChange('selectedTechnician', technician.id)}
                  >
                    <div className="technician-info">
                      <div className="technician-header">
                        <h4>{technician.name}</h4>
                      </div>
                      <div className="technician-rating">
                        <span className="rating-stars">{'★'.repeat(Math.floor(technician.rating))}</span>
                        <span className="rating-number">{technician.rating}</span>
                      </div>
                    </div>
                    <div className="technician-select">
                    <div className={`select-circle ${bookingData.selectedTechnician === technician.id ? 'selected' : ''}`} style={{
                      width: '28px',
                      height: '28px',
                      borderRadius: '50%',
                      border: bookingData.selectedTechnician === technician.id ? '3px solid #2563eb' : '3px solid #94a3b8',
                      background: bookingData.selectedTechnician === technician.id ? '#2563eb' : 'white',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      boxShadow: '0 0 0 4px rgba(37,99,235,0.15)'
                    }}>
                      {bookingData.selectedTechnician === technician.id && <Check size={16} color="white" />}
                    </div>
                    </div>
                  </div>
                ))}
                
                {/* Show dropdown for other available technicians if there are more */}
                {rankedTechnicians.length > 1 && (
                  <div className="other-technicians-dropdown" style={{
                    marginTop: '16px',
                    border: '2px solid #e2e8f0',
                    borderRadius: '12px',
                    overflow: 'hidden',
                    background: '#f8fafc'
                  }}>
                    <details>
                      <summary style={{
                        padding: '16px',
                        cursor: 'pointer',
                        background: 'white',
                        borderBottom: '1px solid #e2e8f0',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        fontWeight: '600',
                        color: '#1e293b',
                        userSelect: 'none'
                      }}>
                        <span>Choose other available technicians</span>
                        <ChevronRight size={20} style={{ transition: 'transform 0.2s' }} className="dropdown-icon" />
                      </summary>
                      <div style={{ padding: '8px' }}>
                        {rankedTechnicians.slice(1).map((technician) => (
                          <div
                            key={technician.id}
                            className={`technician-item other-technician ${bookingData.selectedTechnician === technician.id ? 'selected' : ''}`}
                            onClick={() => handleInputChange('selectedTechnician', technician.id)}
                            style={{
                              margin: '8px',
                              padding: '16px',
                              borderRadius: '8px',
                              border: `2px solid ${bookingData.selectedTechnician === technician.id ? '#2563eb' : '#e2e8f0'}`,
                              background: bookingData.selectedTechnician === technician.id ? '#eff6ff' : 'white',
                              cursor: 'pointer',
                              transition: 'all 0.2s',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'space-between'
                            }}
                          >
                            <div className="technician-info">
                              <div className="technician-header">
                                <h4 style={{ margin: 0, fontSize: '1rem', color: '#1e293b' }}>{technician.name}</h4>
                              </div>
                            {/* Stars removed for other available technicians as requested */}
                            </div>
                            <div className="technician-select">
                              <div className={`select-circle ${bookingData.selectedTechnician === technician.id ? 'selected' : ''}`} style={{
                                width: '28px',
                                height: '28px',
                                borderRadius: '50%',
                                border: `3px solid ${bookingData.selectedTechnician === technician.id ? '#2563eb' : '#94a3b8'}`,
                                background: bookingData.selectedTechnician === technician.id ? '#2563eb' : 'white',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                transition: 'all 0.2s',
                                boxShadow: '0 0 0 4px rgba(37,99,235,0.15)'
                              }}>
                                {bookingData.selectedTechnician === technician.id && <Check size={16} color="white" />}
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    </details>
                  </div>
                )}
              </>
            ) : (
              !loadingTechnicians && (
                <div className="no-technicians">
                  <p>No technicians available for the selected service and time. Please try a different time slot.</p>
                </div>
              )
            )}
          </div>
          </div>
        )}
      </div>
    );
  };

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
              flexDirection: 'column',
              alignItems: 'center',
              textAlign: 'center',
              gap: '12px'
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
                  fontSize: '1.25rem',
                  fontWeight: '600',
                  color: '#1e293b'
                }}>Welcome back, {auth.user.name}!</h4>
                <p style={{
                  margin: '8px 0 0 0',
                  fontSize: '0.875rem',
                  color: '#64748b'
                }}>Ready to book your service - just enter the service address below</p>
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
      
      {/* Address Toggle for Logged-in Users */}
      {auth.user && (
        <div className="address-toggle-container" style={{
          marginBottom: '24px',
          padding: '16px',
          background: '#f8fafc',
          borderRadius: '12px',
          border: '1px solid #e2e8f0'
        }}>
          <label className="toggle-switch" style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            cursor: 'pointer'
          }}>
            <div>
              <div style={{ fontWeight: '600', marginBottom: '4px' }}>Use a different address for this booking?</div>
              <div style={{ fontSize: '0.875rem', color: '#64748b' }}>
                {bookingData.useCustomAddress 
                  ? 'Enter a custom address for this service'
                  : (auth.user?.house_no_street || auth.user?.barangay || auth.user?.city_municipality || auth.user?.province)
                    ? `Using your registered address: ${auth.user.full_address || `${auth.user.house_no_street || ''}${auth.user.barangay ? ', ' + auth.user.barangay : ''}${auth.user.city_municipality ? ', ' + auth.user.city_municipality : ''}${auth.user.province ? ', ' + auth.user.province : ''}`}`
                    : 'No saved address found. Please enter your address for this booking.'
                }
              </div>
            </div>
            <div style={{ position: 'relative' }}>
              <input
                type="checkbox"
                checked={bookingData.useCustomAddress}
                onChange={(e) => {
                  const useCustom = e.target.checked;
                  setBookingData(prev => ({
                    ...prev,
                    useCustomAddress: useCustom,
                    // Reset to registered address if switching back
                    province: !useCustom && auth.user?.province ? auth.user.province : prev.province,
                    municipality: !useCustom && auth.user?.city_municipality ? auth.user.city_municipality : prev.municipality,
                    barangay: !useCustom && auth.user?.barangay ? auth.user.barangay : prev.barangay,
                    houseNumber: !useCustom && auth.user?.house_no_street ? auth.user.house_no_street : prev.houseNumber,
                    nearestLandmark: !useCustom && auth.user?.nearest_landmark ? auth.user.nearest_landmark : prev.nearestLandmark,
                  }));
                }}
                style={{ display: 'none' }}
              />
              <div className="toggle-slider" style={{
                width: '48px',
                height: '24px',
                background: bookingData.useCustomAddress ? '#2563eb' : '#cbd5e1',
                borderRadius: '12px',
                position: 'relative',
                transition: 'background-color 0.3s'
              }}>
                <div style={{
                  width: '20px',
                  height: '20px',
                  background: 'white',
                  borderRadius: '10px',
                  position: 'absolute',
                  top: '2px',
                  left: bookingData.useCustomAddress ? '26px' : '2px',
                  transition: 'left 0.3s',
                  boxShadow: '0 2px 4px rgba(0,0,0,0.2)'
                }}></div>
              </div>
            </div>
          </label>
        </div>
      )}
      
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
              inputMode="numeric"
              pattern="\\d*"
              value={bookingData.mobileNumber}
              onChange={(e) => handleInputChange('mobileNumber', formatPhilippinePhone(e.target.value))}
              className="form-input"
              placeholder="09XX-XXX-XXXX"
              maxLength={13}
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
              <span className="summary-label">Start:</span>
              <span className="summary-value">
                {bookingData.scheduledStartAt ? formatReadableDateTime(bookingData.scheduledStartAt) : 'Not scheduled'}
              </span>
            </div>
            <div className="summary-item">
              <span className="summary-label">End:</span>
              <span className="summary-value">
                {bookingData.scheduledEndAt ? formatReadableDateTime(bookingData.scheduledEndAt) : 'Not scheduled'}
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
          {bookingData.selectedPromotion && availablePromotions.length > 0 ? (
            <>
              <div className="cost-display" style={{ marginBottom: '8px' }}>
                <span className="cost-label">Original Price:</span>
                <span className="cost-amount" style={{ textDecoration: 'line-through', opacity: 0.7 }}>₱{estimatedCost.toLocaleString()}</span>
              </div>
              <div className="cost-display" style={{ marginBottom: '8px' }}>
                <span className="cost-label">Promotion Applied:</span>
                <span className="cost-amount" style={{ color: '#16a34a', fontWeight: '600' }}>
                  {availablePromotions.find(p => p.id.toString() === bookingData.selectedPromotion.toString())?.title} - 
                  {availablePromotions.find(p => p.id.toString() === bookingData.selectedPromotion.toString())?.formatted_discount}
                </span>
              </div>
              <div className="cost-display">
                <span className="cost-label" style={{ fontSize: '1.125rem', fontWeight: '700' }}>Discounted Total:</span>
                <span className="cost-amount" style={{ fontSize: '1.5rem', color: '#16a34a' }}>₱{discountedPrice.toLocaleString()}</span>
              </div>
            </>
          ) : (
            <div className="cost-display">
              <span className="cost-label">Estimated Total:</span>
              <span className="cost-amount">₱{getEstimatedCost().toLocaleString()}</span>
            </div>
          )}
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
    <ErrorBoundary fallbackMessage="Something went wrong with the booking page. Please refresh and try again.">
      <Head title="Book Your Service" />
      
      {renderExitWarningModal()}
      {renderSuccessModal()}
      
      <div className="booking-page">
        <BookingHeader onNavigate={handleNavigation} />
        
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
    </ErrorBoundary>
  );
}
