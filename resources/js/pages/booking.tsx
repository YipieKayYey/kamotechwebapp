import { useState } from 'react';
import { PublicNavigation } from '@/components/public-navigation';
import { PublicFooter } from '@/components/public-footer';
import { ChevronLeft, ChevronRight, Check, Calendar, Clock, MapPin, Phone, User } from 'lucide-react';

interface BookingData {
  // Step 1: Service and Unit Details
  serviceType: string;
  airconType: string;
  numberOfUnits: number;
  brand: string;
  
  // Step 2: Date and Time
  selectedDate: string;
  selectedTime: string;
  selectedTechnician: string;
  
  // Step 3: Contact Details
  mobileNumber: string;
  province: string;
  municipality: string;
  barangay: string;
  houseNumber: string;
  street: string;
  nearestLandmark: string;
}

const serviceTypes = [
  'AC Cleaning',
  'AC Repair', 
  'AC Installation',
  'Freon Charging',
  'Repiping',
  'Troubleshooting',
  'AC Relocation'
];

const airconTypes = [
  'Split Type',
  'Window Type',
  'Floor Standing',
  'Ceiling Cassette',
  'Multi Split',
  'Ducted System'
];

const brands = [
  'Carrier',
  'Daikin',
  'LG',
  'Panasonic',
  'Samsung',
  'Sharp',
  'Hitachi',
  'Kolin',
  'Condura',
  'Koppel',
  'Fujidenzo'
];

const timeSlots = [
  '6 AM - 9 AM',
  '9 AM - 12 PM', 
  '12 PM - 3 PM',
  '3 PM - 6 PM'
];

const technicians = [
  { id: '1', name: 'Juan Dela Cruz', rating: 4.9, experience: '5 years', specializations: ['AC Cleaning', 'AC Repair'] },
  { id: '2', name: 'Pedro Santos', rating: 4.8, experience: '4 years', specializations: ['AC Installation', 'Freon Charging'] },
  { id: '3', name: 'Miguel Garcia', rating: 4.7, experience: '6 years', specializations: ['Repiping', 'Troubleshooting'] },
  { id: '4', name: 'Carlos Rodriguez', rating: 4.6, experience: '3 years', specializations: ['AC Relocation', 'AC Maintenance'] }
];

const provinces = [
  'Bataan', 'Pampanga', 'Bulacan', 'Nueva Ecija', 'Tarlac', 'Zambales'
];

const municipalitiesList = {
  'Bataan': ['Balanga', 'Mariveles', 'Dinalupihan', 'Hermosa', 'Orani', 'Samal', 'Abucay', 'Pilar', 'Orion', 'Limay', 'Bagac', 'Morong'],
  'Pampanga': ['Angeles', 'San Fernando', 'Mabalacat', 'Mexico', 'San Luis', 'Guagua', 'Apalit', 'Candaba', 'Floridablanca', 'Lubao', 'Masantol', 'Minalin', 'Porac', 'San Simon', 'Santa Ana', 'Santa Rita', 'Santo Tomas', 'Sasmuan'],
  'Bulacan': ['Malolos', 'Meycauayan', 'San Jose del Monte', 'Santa Maria', 'Bocaue', 'Marilao', 'Guiguinto', 'Pulilan', 'Plaridel', 'Obando', 'Pandi', 'Paombong', 'Hagonoy', 'Calumpit', 'Norzagaray', 'San Rafael', 'Angat', 'Doña Remedios Trinidad', 'San Miguel', 'San Ildefonso', 'Baliuag', 'Bustos', 'Calumpit', 'San Jose del Monte'],
  'Nueva Ecija': ['Cabanatuan', 'San Jose', 'Gapan', 'Santa Rosa', 'Talavera', 'Muñoz', 'Llanera', 'Quezon', 'Rizal', 'Talavera', 'Lupao', 'Guimba', 'San Antonio', 'Jaen', 'San Leonardo', 'Peñaranda', 'General Tinio', 'Carranglan', 'Nampicuan', 'Talugtug', 'Aliaga', 'Zaragoza', 'San Isidro', 'Cabiao', 'General Natividad', 'Palayan', 'Bongabon', 'Laur', 'Gabaldon', 'Santo Domingo', 'Santa Rosa', 'San Antonio', 'San Leonardo', 'Peñaranda', 'General Tinio', 'Carranglan', 'Nampicuan', 'Talugtug', 'Aliaga', 'Zaragoza', 'San Isidro', 'Cabiao', 'General Natividad', 'Palayan', 'Bongabon', 'Laur', 'Gabaldon', 'Santo Domingo'],
  'Tarlac': ['Tarlac City', 'Capas', 'Concepcion', 'Bamban', 'La Paz', 'Victoria', 'San Jose', 'Gerona', 'Paniqui', 'Moncada', 'San Manuel', 'Anao', 'Ramos', 'San Clemente', 'Mayantoc', 'Camiling', 'Sta. Ignacia', 'Pura', 'Bunagan', 'San Jose', 'Gerona', 'Paniqui', 'Moncada', 'San Manuel', 'Anao', 'Ramos', 'San Clemente', 'Mayantoc', 'Camiling', 'Sta. Ignacia', 'Pura', 'Bunagan'],
  'Zambales': ['Olongapo', 'Subic', 'Castillejos', 'San Marcelino', 'San Antonio', 'San Narciso', 'San Felipe', 'Cabangan', 'Botolan', 'Iba', 'Palauig', 'Candelaria', 'Masinloc', 'Sta. Cruz', 'Candelaria', 'Masinloc', 'Sta. Cruz']
};

export default function Booking() {
  const [currentStep, setCurrentStep] = useState(1);
  const [bookingData, setBookingData] = useState<BookingData>({
    serviceType: '',
    airconType: '',
    numberOfUnits: 1,
    brand: '',
    selectedDate: '',
    selectedTime: '',
    selectedTechnician: '',
    mobileNumber: '',
    province: '',
    municipality: '',
    barangay: '',
    houseNumber: '',
    street: '',
    nearestLandmark: ''
  });

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
    
    // Handle dependent dropdowns
    if (field === 'province') {
      const municipalities = municipalitiesList[value as keyof typeof municipalitiesList] || [];
      setAvailableMunicipalities(municipalities);
      setBookingData(prev => ({ ...prev, municipality: '', barangay: '' }));
    }
    
    if (field === 'municipality') {
      // Generate sample barangays for the selected municipality
      const barangays = [
        'Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4', 'Barangay 5',
        'Barangay 6', 'Barangay 7', 'Barangay 8', 'Barangay 9', 'Barangay 10'
      ];
      setAvailableBarangays(barangays);
      setBookingData(prev => ({ ...prev, barangay: '' }));
    }

    // Handle date selection to show time slots
    if (field === 'selectedDate') {
      setShowTimeSlots(true);
      setShowTechnicians(false);
      setBookingData(prev => ({ ...prev, selectedTime: '', selectedTechnician: '' }));
    }

    // Handle time selection to show technicians
    if (field === 'selectedTime') {
      setShowTechnicians(true);
      setBookingData(prev => ({ ...prev, selectedTechnician: '' }));
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
        return bookingData.serviceType && bookingData.airconType && bookingData.brand;
      case 2:
        return bookingData.selectedDate && bookingData.selectedTime && bookingData.selectedTechnician;
      case 3:
        return bookingData.mobileNumber && bookingData.province && bookingData.municipality && 
               bookingData.barangay && bookingData.houseNumber;
      default:
        return true;
    }
  };

  const getEstimatedCost = () => {
    const baseCosts = {
      'AC Cleaning': 800,
      'AC Repair': 1500,
      'AC Installation': 3000,
      'Freon Charging': 1200,
      'Repiping': 2000,
      'Troubleshooting': 500,
      'AC Relocation': 2500
    };
    
    const baseCost = baseCosts[bookingData.serviceType as keyof typeof baseCosts] || 0;
    return baseCost * bookingData.numberOfUnits;
  };

  const handleSubmit = () => {
    // Here you would typically send the booking data to your backend
    console.log('Booking submitted:', bookingData);
    alert('Booking submitted successfully! We will contact you soon to confirm your appointment.');
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

  // Get technician count for a specific time slot (always 5 technicians available)
  const getTechnicianCount = (timeSlot: string) => {
    return 5;
  };

  // Format date for display
  const formatDate = (date: Date) => {
    const months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    
    return {
      month: months[date.getMonth()],
      day: date.getDate(),
      dayName: days[date.getDay()],
      fullDate: date.toISOString().split('T')[0]
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
    const allProvinces = ['Bataan', 'Pampanga', 'Bulacan', 'Nueva Ecija', 'Tarlac', 'Zambales'];
    return allProvinces.filter(province => 
      province.toLowerCase().includes(input.toLowerCase())
    );
  };

  const getMunicipalitySuggestions = (input: string, province: string) => {
    if (!input) return [];
    if (province.toLowerCase() === 'bataan') {
      const bataanMunicipalities = municipalitiesList['Bataan'];
      return bataanMunicipalities.filter(municipality =>
        municipality.toLowerCase().includes(input.toLowerCase())
      );
    }
    return [];
  };

  const getBarangaySuggestions = (input: string, municipality: string, province: string) => {
    if (!input || province.toLowerCase() !== 'bataan') return [];
    
    // Sample barangays for Bataan municipalities
    const bataanBarangays = [
      'Central', 'Poblacion', 'San Jose', 'San Juan', 'Santa Rosa', 'Bagumbayan',
      'Balanga Proper', 'Ibayo', 'Malabia', 'Munting Batangas', 'Sibacan',
      'Tanato', 'Tortugas', 'Tuyo', 'Cabog-cabog', 'Camacho', 'Cataning',
      'Doña Francisca', 'Lote', 'Pto. Rivas Ibaba', 'Pto. Rivas Itaas',
      'Tenejero', 'Villa Angeles'
    ];
    
    return bataanBarangays.filter(barangay =>
      barangay.toLowerCase().includes(input.toLowerCase())
    );
  };

  const handleAddressInputChange = (field: 'province' | 'municipality' | 'barangay', value: string) => {
    setBookingData(prev => ({ ...prev, [field]: value }));

    if (field === 'province') {
      setProvinceSuggestions(getProvinceSuggestions(value));
      setMunicipalitySuggestions([]);
      setBarangaySuggestions([]);
      if (value.toLowerCase() !== 'bataan') {
        setBookingData(prev => ({ ...prev, municipality: '', barangay: '' }));
      }
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
      if (value.toLowerCase() !== 'bataan') {
        setBookingData(prev => ({ ...prev, municipality: '', barangay: '' }));
      }
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
            {serviceTypes.map((service) => (
              <option key={service} value={service}>{service}</option>
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
            {airconTypes.map((type) => (
              <option key={type} value={type}>{type}</option>
            ))}
          </select>
        </div>

        <div className="form-group">
          <label htmlFor="numberOfUnits">Number of Units</label>
          <input
            type="number"
            id="numberOfUnits"
            min="1"
            max="10"
            value={bookingData.numberOfUnits}
            onChange={(e) => handleInputChange('numberOfUnits', parseInt(e.target.value))}
            className="form-input"
          />
        </div>

        <div className="form-group">
          <label htmlFor="brand">Brand *</label>
          <select
            id="brand"
            value={bookingData.brand}
            onChange={(e) => handleInputChange('brand', e.target.value)}
            className="form-select"
          >
            <option value="">Select brand</option>
            {brands.map((brand) => (
              <option key={brand} value={brand}>{brand}</option>
            ))}
          </select>
        </div>
      </div>
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
              const isSelected = bookingData.selectedDate === date.toISOString().split('T')[0];
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
                      handleInputChange('selectedDate', date.toISOString().split('T')[0]);
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
          <div className="timeslots-grid">
            {timeSlots.map((timeSlot) => {
              const techCount = getTechnicianCount(timeSlot);
              const isSelected = bookingData.selectedTime === timeSlot;
              
              return (
                <div
                  key={timeSlot}
                  className={`timeslot-card ${isSelected ? 'selected' : ''}`}
                  onClick={() => handleInputChange('selectedTime', timeSlot)}
                >
                  <div className="timeslot-time">{timeSlot}</div>
                  <div className="timeslot-availability">
                    {techCount} technician{techCount !== 1 ? 's' : ''} left
                  </div>
                </div>
              );
            })}
          </div>
          <p className="timeslot-note">Technician arrives within the 1st hour of a timeslot.</p>
        </div>
      )}

      {/* Technician Selection */}
      {showTechnicians && (
        <div className="technicians-section">
          <h3>Available Technicians</h3>
          <p>Our technicians are ranked by rating and experience for your selected service.</p>
          
          <div className="technicians-list">
            {technicians
              .filter(tech => tech.specializations.includes(bookingData.serviceType))
              .sort((a, b) => b.rating - a.rating)
              .map((technician) => (
                <div
                  key={technician.id}
                  className={`technician-item ${bookingData.selectedTechnician === technician.id ? 'selected' : ''}`}
                  onClick={() => handleInputChange('selectedTechnician', technician.id)}
                >
                  <div className="technician-info">
                    <h4>{technician.name}</h4>
                    <div className="technician-rating">
                      <span className="rating-stars">{'★'.repeat(Math.floor(technician.rating))}</span>
                      <span className="rating-number">{technician.rating}</span>
                    </div>
                    <p className="technician-experience">{technician.experience} experience</p>
                    <div className="technician-specializations">
                      {technician.specializations.map((spec, index) => (
                        <span key={index} className="specialization-tag">{spec}</span>
                      ))}
                    </div>
                  </div>
                  <div className="technician-select">
                    <div className={`select-circle ${bookingData.selectedTechnician === technician.id ? 'selected' : ''}`}>
                      {bookingData.selectedTechnician === technician.id && <Check size={16} />}
                    </div>
                  </div>
                </div>
              ))}
          </div>
        </div>
      )}
    </div>
  );

  const renderStep3 = () => (
    <div className="booking-step">
      <div className="step-header">
        <h2>Contact and Address Details</h2>
        <p>Please provide your contact information and complete address for service delivery.</p>
      </div>
      
      <div className="form-grid">
        <div className="form-group">
          <label htmlFor="mobileNumber">Mobile Number *</label>
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
          <label htmlFor="province">Province *</label>
          <div className="autocomplete-container">
            <input
              type="text"
              id="province"
              value={bookingData.province}
              onChange={(e) => handleAddressInputChange('province', e.target.value)}
              className="form-input"
              placeholder="Enter province (e.g., Bataan)"
              autoComplete="off"
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
          <label htmlFor="municipality">Municipality/City *</label>
          <div className="autocomplete-container">
            <input
              type="text"
              id="municipality"
              value={bookingData.municipality}
              onChange={(e) => handleAddressInputChange('municipality', e.target.value)}
              className="form-input"
              placeholder={bookingData.province.toLowerCase() === 'bataan' ? "Enter municipality (e.g., Balanga)" : "Enter municipality"}
              autoComplete="off"
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
          <label htmlFor="barangay">Barangay *</label>
          <div className="autocomplete-container">
            <input
              type="text"
              id="barangay"
              value={bookingData.barangay}
              onChange={(e) => handleAddressInputChange('barangay', e.target.value)}
              className="form-input"
              placeholder={bookingData.province.toLowerCase() === 'bataan' ? "Enter barangay (e.g., Poblacion)" : "Enter barangay"}
              autoComplete="off"
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
          <label htmlFor="houseNumber">House No. & Street *</label>
          <input
            type="text"
            id="houseNumber"
            value={bookingData.houseNumber}
            onChange={(e) => handleInputChange('houseNumber', e.target.value)}
            className="form-input"
            placeholder="e.g., 123 Main Street"
          />
        </div>

        <div className="form-group">
          <label htmlFor="nearestLandmark">Nearest Landmark</label>
          <input
            type="text"
            id="nearestLandmark"
            value={bookingData.nearestLandmark}
            onChange={(e) => handleInputChange('nearestLandmark', e.target.value)}
            className="form-input"
            placeholder="e.g., Near SM Mall, Behind Church"
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
              <span className="summary-value">{bookingData.serviceType}</span>
            </div>
            <div className="summary-item">
              <span className="summary-label">Aircon Type:</span>
              <span className="summary-value">{bookingData.airconType}</span>
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
              <span className="summary-value">{new Date(bookingData.selectedDate).toLocaleDateString()}</span>
            </div>
            <div className="summary-item">
              <span className="summary-label">Time:</span>
              <span className="summary-value">{bookingData.selectedTime}</span>
            </div>
            <div className="summary-item">
              <span className="summary-label">Technician:</span>
              <span className="summary-value">
                {technicians.find(t => t.id === bookingData.selectedTechnician)?.name}
              </span>
            </div>
          </div>
        </div>

        <div className="summary-section">
          <h3>Contact Information</h3>
          <div className="summary-grid">
            <div className="summary-item">
              <span className="summary-label">Mobile Number:</span>
              <span className="summary-value">{bookingData.mobileNumber}</span>
            </div>
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
    <div className="booking-page">
      <PublicNavigation />
      
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
  );
}
