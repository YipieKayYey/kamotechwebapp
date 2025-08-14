import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { 
  Star, 
  Calendar, 
  Clock, 
  User, 
  Wrench, 
  MapPin,
  ArrowLeft,
  CheckCircle,
  MessageSquare,
  Send
} from 'lucide-react';

// Scoped styles for this component only
const styles = {
  page: {
    minHeight: '100vh',
    background: 'var(--background)',
    color: 'var(--foreground)',
    fontFamily: 'var(--font-sans)',
    lineHeight: '1.6',
    padding: '2rem'
  },
  successAlert: {
    position: 'fixed' as const,
    top: '2rem',
    left: '50%',
    transform: 'translateX(-50%)',
    zIndex: 1000,
    background: '#10b981',
    color: 'white',
    borderRadius: '0.75rem',
    boxShadow: '0 10px 25px rgba(16, 185, 129, 0.3)',
    animation: 'slideDown 0.3s ease-out'
  },
  successAlertContent: {
    display: 'flex',
    alignItems: 'center',
    gap: '0.75rem',
    padding: '1rem 1.5rem',
    fontWeight: '500',
    fontSize: '0.875rem'
  },
  header: {
    maxWidth: '800px',
    margin: '0 auto 2rem auto',
    textAlign: 'center' as const
  },
  backButton: {
    display: 'inline-flex',
    alignItems: 'center',
    gap: '0.5rem',
    padding: '0.5rem 1rem',
    background: 'var(--muted)',
    color: 'var(--foreground)',
    border: '1px solid var(--border)',
    borderRadius: '0.375rem',
    fontSize: '0.875rem',
    fontWeight: '500',
    cursor: 'pointer',
    transition: 'all 0.2s ease',
    textDecoration: 'none',
    marginBottom: '1.5rem'
  },
  pageTitle: {
    fontSize: '2.5rem',
    fontWeight: '700',
    color: '#083860',
    margin: '0 0 0.5rem 0',
    lineHeight: '1.2'
  },
  pageSubtitle: {
    fontSize: '1.125rem',
    color: 'var(--muted-foreground)',
    margin: '0',
    maxWidth: '600px',
    marginLeft: 'auto',
    marginRight: 'auto'
  },
  container: {
    maxWidth: '800px',
    margin: '0 auto',
    display: 'flex',
    flexDirection: 'column' as const,
    gap: '2rem'
  },
  card: {
    background: 'var(--card)',
    border: '1px solid var(--border)',
    borderRadius: 'var(--radius)',
    boxShadow: '0 2px 4px rgba(0, 0, 0, 0.05)',
    overflow: 'hidden'
  },
  cardHeader: {
    background: 'linear-gradient(135deg, #083860, #0C5F8F)',
    color: 'white',
    padding: '1.5rem',
    borderBottom: '1px solid var(--border)'
  },
  cardTitle: {
    display: 'flex',
    alignItems: 'center',
    gap: '0.75rem',
    fontSize: '1.25rem',
    fontWeight: '600',
    margin: '0'
  },
  detailsGrid: {
    padding: '1.5rem',
    display: 'grid',
    gridTemplateColumns: '1fr',
    gap: '1rem'
  },
  detailItem: {
    display: 'flex',
    alignItems: 'center',
    gap: '0.75rem',
    padding: '0.75rem',
    background: 'var(--muted)',
    borderRadius: '0.5rem',
    transition: 'all 0.2s ease'
  },
  detailLabel: {
    fontWeight: '600',
    color: '#374151',
    minWidth: '100px'
  },
  detailValue: {
    color: 'var(--foreground)'
  },
  sectionHeader: {
    background: 'linear-gradient(135deg, #f8fafc, #e2e8f0)',
    padding: '1.5rem',
    borderBottom: '1px solid var(--border)'
  },
  sectionTitle: {
    display: 'flex',
    alignItems: 'center',
    gap: '0.75rem',
    fontSize: '1.25rem',
    fontWeight: '600',
    color: '#083860',
    margin: '0 0 0.5rem 0'
  },
  sectionSubtitle: {
    color: 'var(--muted-foreground)',
    margin: '0',
    fontSize: '0.875rem'
  },
  ratingGrid: {
    padding: '1.5rem',
    display: 'grid',
    gridTemplateColumns: '1fr',
    gap: '1.5rem'
  },
  ratingItem: {
    display: 'flex',
    flexDirection: 'column' as const,
    gap: '0.75rem',
    padding: '1.25rem',
    background: 'var(--muted)',
    borderRadius: '0.75rem',
    border: '2px solid transparent',
    transition: 'all 0.2s ease'
  },
  ratingLabel: {
    fontWeight: '600',
    color: '#374151',
    fontSize: '1rem'
  },
  starRating: {
    display: 'flex',
    gap: '0.25rem'
  },
  star: {
    background: 'none',
    border: 'none',
    cursor: 'pointer',
    padding: '0.25rem',
    borderRadius: '0.25rem',
    transition: 'all 0.2s ease',
    color: '#d1d5db'
  },
  starActive: {
    color: '#f59e0b'
  },
  ratingText: {
    fontSize: '0.875rem',
    color: 'var(--muted-foreground)',
    fontStyle: 'italic'
  },
  feedbackField: {
    padding: '1.5rem',
    display: 'flex',
    flexDirection: 'column' as const,
    gap: '0.75rem'
  },
  fieldLabel: {
    fontWeight: '600',
    color: '#374151',
    fontSize: '1rem'
  },
  required: {
    color: '#dc2626'
  },
  textarea: {
    width: '100%',
    minHeight: '120px',
    padding: '1rem',
    border: '2px solid var(--border)',
    borderRadius: '0.5rem',
    fontFamily: 'inherit',
    fontSize: '0.875rem',
    lineHeight: '1.5',
    background: 'var(--background)',
    color: 'var(--foreground)',
    resize: 'vertical' as const,
    transition: 'all 0.2s ease'
  },
  characterCount: {
    fontSize: '0.75rem',
    color: 'var(--muted-foreground)',
    textAlign: 'right' as const
  },
  formActions: {
    padding: '2rem',
    background: 'var(--card)',
    border: '1px solid var(--border)',
    borderRadius: 'var(--radius)',
    textAlign: 'center' as const,
    display: 'flex',
    flexDirection: 'column' as const,
    alignItems: 'center',
    gap: '1rem'
  },
  submitButton: {
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    gap: '0.75rem',
    padding: '1rem 2rem',
    background: 'linear-gradient(135deg, #083860, #0C5F8F)',
    color: 'white',
    border: 'none',
    borderRadius: '0.75rem',
    fontSize: '1rem',
    fontWeight: '600',
    cursor: 'pointer',
    transition: 'all 0.2s ease',
    minWidth: '200px',
    boxShadow: '0 4px 8px rgba(8, 56, 96, 0.2)'
  },
  submitButtonDisabled: {
    background: 'var(--muted)',
    color: 'var(--muted-foreground)',
    cursor: 'not-allowed',
    boxShadow: 'none'
  },
  spinner: {
    width: '20px',
    height: '20px',
    border: '2px solid transparent',
    borderTop: '2px solid currentColor',
    borderRadius: '50%',
    animation: 'spin 1s linear infinite'
  },
  validationMessage: {
    color: '#dc2626',
    fontSize: '0.875rem',
    margin: '0',
    textAlign: 'center' as const,
    maxWidth: '400px'
  }
};

interface BookingDetails {
  id: string;
  serviceType: string;
  date: string;
  time: string;
  technician: string;
  location: string;
  acType: string;
  units: number;
  brand: string;
}

interface EvaluationCriteria {
  punctuality: number;
  qualityOfWork: number;
  professionalism: number;
  communication: number;
  problemSolving: number;
  cleanliness: number;
  attentionToDetail: number;
  friendliness: number;
  serviceSpeed: number;
  reliability: number;
  safetyPractices: number;
  preparedness: number;
  likelihoodToRecommend: number;
}

interface EvaluationFeedbackProps {
  booking: BookingDetails;
}

const criteriaLabels = {
  punctuality: 'Punctuality',
  qualityOfWork: 'Quality of Work',
  professionalism: 'Professionalism',
  communication: 'Communication',
  problemSolving: 'Problem-Solving Ability',
  cleanliness: 'Cleanliness & Work Area Handling',
  attentionToDetail: 'Attention to Detail',
  friendliness: 'Friendliness',
  serviceSpeed: 'Service Speed',
  reliability: 'Reliability',
  safetyPractices: 'Safety Practices',
  preparedness: 'Preparedness',
  likelihoodToRecommend: 'Likelihood to Recommend'
};

export default function EvaluationFeedback({ booking }: EvaluationFeedbackProps) {
  const [ratings, setRatings] = useState<EvaluationCriteria>({
    punctuality: 0,
    qualityOfWork: 0,
    professionalism: 0,
    communication: 0,
    problemSolving: 0,
    cleanliness: 0,
    attentionToDetail: 0,
    friendliness: 0,
    serviceSpeed: 0,
    reliability: 0,
    safetyPractices: 0,
    preparedness: 0,
    likelihoodToRecommend: 0
  });

  const [writtenFeedback, setWrittenFeedback] = useState('');
  const [suggestions, setSuggestions] = useState('');
  const [showSuccessAlert, setShowSuccessAlert] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleRatingChange = (criterion: keyof EvaluationCriteria, rating: number) => {
    setRatings(prev => ({
      ...prev,
      [criterion]: rating
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    // Simulate API call
    setTimeout(() => {
      setShowSuccessAlert(true);
      setIsSubmitting(false);
      
      // Hide success alert after 5 seconds
      setTimeout(() => {
        setShowSuccessAlert(false);
      }, 5000);
    }, 1000);
  };

  const goBack = () => {
    router.visit('/customer-dashboard');
  };

  const renderStars = (criterion: keyof EvaluationCriteria) => {
    return (
      <div style={styles.starRating}>
        {[1, 2, 3, 4, 5].map((star) => (
          <button
            key={star}
            type="button"
            style={{
              ...styles.star,
              ...(ratings[criterion] >= star ? styles.starActive : {})
            }}
            onClick={() => handleRatingChange(criterion, star)}
            onMouseEnter={(e) => {
              e.currentTarget.style.color = '#fbbf24';
              e.currentTarget.style.transform = 'scale(1.1)';
            }}
            onMouseLeave={(e) => {
              e.currentTarget.style.color = ratings[criterion] >= star ? '#f59e0b' : '#d1d5db';
              e.currentTarget.style.transform = 'scale(1)';
            }}
          >
            <Star className="w-6 h-6" />
          </button>
        ))}
      </div>
    );
  };

  const isFormValid = () => {
    const allRatingsProvided = Object.values(ratings).every(rating => rating > 0);
    return allRatingsProvided && writtenFeedback.trim().length > 0;
  };

  return (
    <>
      <Head title="Service Evaluation & Feedback" />
      
      {/* Add CSS animations */}
      <style>{`
        @keyframes slideDown {
          from {
            opacity: 0;
            transform: translateX(-50%) translateY(-20px);
          }
          to {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
          }
        }
        
        @keyframes spin {
          to {
            transform: rotate(360deg);
          }
        }
        
        @media (min-width: 640px) {
          .responsive-grid-2 {
            grid-template-columns: repeat(2, 1fr) !important;
          }
        }
        
        @media (max-width: 640px) {
          .responsive-page {
            padding: 1rem !important;
          }
          .responsive-title {
            font-size: 2rem !important;
          }
          .responsive-subtitle {
            font-size: 1rem !important;
          }
        }
        
        @media (min-width: 768px) {
          .responsive-page-lg {
            padding: 3rem !important;
          }
          .responsive-title-lg {
            font-size: 3rem !important;
          }
          .responsive-subtitle-lg {
            font-size: 1.25rem !important;
          }
        }
      `}</style>
      
      <div style={styles.page} className="responsive-page responsive-page-lg">
        {/* Success Alert */}
        {showSuccessAlert && (
          <div style={styles.successAlert}>
            <div style={styles.successAlertContent}>
              <CheckCircle className="w-6 h-6" />
              <span>Thank you for your feedback! Your input helps us improve our services.</span>
            </div>
          </div>
        )}

        {/* Header */}
        <div style={styles.header}>
          <button 
            style={styles.backButton} 
            onClick={goBack}
            onMouseEnter={(e) => {
              e.currentTarget.style.background = 'var(--accent)';
              e.currentTarget.style.transform = 'translateX(-2px)';
            }}
            onMouseLeave={(e) => {
              e.currentTarget.style.background = 'var(--muted)';
              e.currentTarget.style.transform = 'translateX(0)';
            }}
          >
            <ArrowLeft className="w-5 h-5" />
            Back to Dashboard
          </button>
          <h1 style={styles.pageTitle} className="responsive-title responsive-title-lg">Service Evaluation & Feedback</h1>
          <p style={styles.pageSubtitle} className="responsive-subtitle responsive-subtitle-lg">Help us improve our services by sharing your experience</p>
        </div>

        <div style={styles.container}>
          {/* Service Details Card */}
          <div style={styles.card}>
            <div style={styles.cardHeader}>
              <h2 style={styles.cardTitle}>
                <Wrench className="w-5 h-5" />
                Service Details
              </h2>
            </div>
            <div style={styles.detailsGrid} className="responsive-grid-2">
              <div style={styles.detailItem}>
                <span style={styles.detailLabel}>Service Type:</span>
                <span style={styles.detailValue}>{booking.serviceType}</span>
              </div>
              <div style={styles.detailItem}>
                <Calendar className="w-4 h-4" />
                <span style={styles.detailLabel}>Date:</span>
                <span style={styles.detailValue}>{new Date(booking.date).toLocaleDateString()}</span>
              </div>
              <div style={styles.detailItem}>
                <Clock className="w-4 h-4" />
                <span style={styles.detailLabel}>Time:</span>
                <span style={styles.detailValue}>{booking.time}</span>
              </div>
              <div style={styles.detailItem}>
                <User className="w-4 h-4" />
                <span style={styles.detailLabel}>Technician:</span>
                <span style={styles.detailValue}>{booking.technician}</span>
              </div>
              <div style={styles.detailItem}>
                <MapPin className="w-4 h-4" />
                <span style={styles.detailLabel}>Location:</span>
                <span style={styles.detailValue}>{booking.location}</span>
              </div>
              <div style={styles.detailItem}>
                <span style={styles.detailLabel}>AC Type:</span>
                <span style={styles.detailValue}>{booking.acType} - {booking.units} unit{booking.units > 1 ? 's' : ''}</span>
              </div>
              <div style={styles.detailItem}>
                <span style={styles.detailLabel}>Brand:</span>
                <span style={styles.detailValue}>{booking.brand}</span>
              </div>
            </div>
          </div>

          {/* Evaluation Form */}
          <form style={{display: 'flex', flexDirection: 'column', gap: '2rem'}} onSubmit={handleSubmit}>
            {/* Rating Section */}
            <div style={styles.card}>
              <div style={styles.sectionHeader}>
                <h2 style={styles.sectionTitle}>
                  <Star className="w-5 h-5" />
                  Service Rating
                </h2>
                <p style={styles.sectionSubtitle}>Please rate each aspect of the service (1-5 stars)</p>
              </div>
              
              <div style={styles.ratingGrid} className="responsive-grid-2">
                {Object.entries(criteriaLabels).map(([key, label]) => (
                  <div 
                    key={key} 
                    style={styles.ratingItem}
                    onMouseEnter={(e) => {
                      e.currentTarget.style.background = 'var(--accent)';
                      e.currentTarget.style.borderColor = '#083860';
                      e.currentTarget.style.transform = 'translateY(-2px)';
                      e.currentTarget.style.boxShadow = '0 4px 8px rgba(8, 56, 96, 0.1)';
                    }}
                    onMouseLeave={(e) => {
                      e.currentTarget.style.background = 'var(--muted)';
                      e.currentTarget.style.borderColor = 'transparent';
                      e.currentTarget.style.transform = 'translateY(0)';
                      e.currentTarget.style.boxShadow = 'none';
                    }}
                  >
                    <label style={styles.ratingLabel}>{label}</label>
                    {renderStars(key as keyof EvaluationCriteria)}
                    <span style={styles.ratingText}>
                      {ratings[key as keyof EvaluationCriteria] === 0 
                        ? 'Not rated' 
                        : `${ratings[key as keyof EvaluationCriteria]} star${ratings[key as keyof EvaluationCriteria] > 1 ? 's' : ''}`
                      }
                    </span>
                  </div>
                ))}
              </div>
            </div>

            {/* Written Feedback Section */}
            <div style={styles.card}>
              <div style={styles.sectionHeader}>
                <h2 style={styles.sectionTitle}>
                  <MessageSquare className="w-5 h-5" />
                  Written Feedback
                </h2>
                <p style={styles.sectionSubtitle}>Share your detailed experience with our service</p>
              </div>
              
              <div style={styles.feedbackField}>
                <label htmlFor="written-feedback" style={styles.fieldLabel}>
                  Your Feedback <span style={styles.required}>*</span>
                </label>
                <textarea
                  id="written-feedback"
                  style={styles.textarea}
                  placeholder="Please describe your experience with our service. What did you like? What could we improve?"
                  value={writtenFeedback}
                  onChange={(e) => setWrittenFeedback(e.target.value)}
                  rows={5}
                  required
                  onFocus={(e) => {
                    e.currentTarget.style.borderColor = '#083860';
                    e.currentTarget.style.boxShadow = '0 0 0 3px rgba(8, 56, 96, 0.1)';
                  }}
                  onBlur={(e) => {
                    e.currentTarget.style.borderColor = 'var(--border)';
                    e.currentTarget.style.boxShadow = 'none';
                  }}
                />
                <div style={styles.characterCount}>
                  {writtenFeedback.length} characters
                </div>
              </div>
            </div>

            {/* Suggestions Section */}
            <div style={styles.card}>
              <div style={styles.sectionHeader}>
                <h2 style={styles.sectionTitle}>
                  <MessageSquare className="w-5 h-5" />
                  Suggestions for Improvement
                </h2>
                <p style={styles.sectionSubtitle}>Help us serve you better (optional)</p>
              </div>
              
              <div style={styles.feedbackField}>
                <label htmlFor="suggestions" style={styles.fieldLabel}>
                  Your Suggestions
                </label>
                <textarea
                  id="suggestions"
                  style={styles.textarea}
                  placeholder="Any suggestions on how we can improve our services?"
                  value={suggestions}
                  onChange={(e) => setSuggestions(e.target.value)}
                  rows={4}
                  onFocus={(e) => {
                    e.currentTarget.style.borderColor = '#083860';
                    e.currentTarget.style.boxShadow = '0 0 0 3px rgba(8, 56, 96, 0.1)';
                  }}
                  onBlur={(e) => {
                    e.currentTarget.style.borderColor = 'var(--border)';
                    e.currentTarget.style.boxShadow = 'none';
                  }}
                />
                <div style={styles.characterCount}>
                  {suggestions.length} characters
                </div>
              </div>
            </div>

            {/* Submit Button */}
            <div style={styles.formActions}>
              <button
                type="submit"
                style={{
                  ...styles.submitButton,
                  ...((!isFormValid() || isSubmitting) ? styles.submitButtonDisabled : {})
                }}
                disabled={!isFormValid() || isSubmitting}
                onMouseEnter={(e) => {
                  if (isFormValid() && !isSubmitting) {
                    e.currentTarget.style.transform = 'translateY(-2px)';
                    e.currentTarget.style.boxShadow = '0 6px 12px rgba(8, 56, 96, 0.3)';
                    e.currentTarget.style.background = 'linear-gradient(135deg, #0C5F8F, #083860)';
                  }
                }}
                onMouseLeave={(e) => {
                  if (isFormValid() && !isSubmitting) {
                    e.currentTarget.style.transform = 'translateY(0)';
                    e.currentTarget.style.boxShadow = '0 4px 8px rgba(8, 56, 96, 0.2)';
                    e.currentTarget.style.background = 'linear-gradient(135deg, #083860, #0C5F8F)';
                  }
                }}
              >
                {isSubmitting ? (
                  <>
                    <div style={styles.spinner}></div>
                    Submitting...
                  </>
                ) : (
                  <>
                    <Send className="w-5 h-5" />
                    Submit Feedback
                  </>
                )}
              </button>
              
              {!isFormValid() && (
                <p style={styles.validationMessage}>
                  Please provide ratings for all criteria and written feedback before submitting.
                </p>
              )}
            </div>
          </form>
        </div>
      </div>
    </>
  );
}

// Default booking data for demo purposes - this would come from props in real implementation
EvaluationFeedback.defaultProps = {
  booking: {
    id: 'BK002',
    serviceType: 'AC Repair',
    date: '2024-01-28',
    time: '2:00 PM - 5:00 PM',
    technician: 'Miguel Garcia',
    location: '123 Main St, Poblacion, Balanga, Bataan',
    acType: 'Window Type',
    units: 1,
    brand: 'LG'
  }
};
