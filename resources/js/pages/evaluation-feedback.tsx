import { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
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
  Send,
  X
} from 'lucide-react';
import { 
  reviewApi, 
  handleApiError,
  type ReviewCategory,
  type BookingForReview,
  type CategoryScore
} from '@/services/customerApi';

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

interface EvaluationFeedbackProps {
  bookingId?: string;
}

interface PageProps {
  bookingId?: string;
  [key: string]: any;
}

export default function EvaluationFeedback() {
  const { props } = usePage<PageProps>();
  const [bookingData, setBookingData] = useState<BookingForReview | null>(null);
  const [categories, setCategories] = useState<ReviewCategory[]>([]);
  const [ratings, setRatings] = useState<Record<number, number>>({});
  const [writtenFeedback, setWrittenFeedback] = useState('');
  const [showSuccessAlert, setShowSuccessAlert] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  // Get booking ID from URL params or props
  const bookingId = props.bookingId || new URLSearchParams(window.location.search).get('bookingId');
  
  useEffect(() => {
    if (bookingId) {
      loadBookingAndCategories();
    } else {
      setError('No booking ID provided');
      setLoading(false);
    }
  }, [bookingId]);
  
  const loadBookingAndCategories = async () => {
    try {
      setLoading(true);
      const [booking, reviewCategories] = await Promise.all([
        reviewApi.getBookingForReview(parseInt(bookingId!)),
        reviewApi.getReviewCategories()
      ]);
      
      setBookingData(booking);
      setCategories(reviewCategories);
      
      // Initialize ratings state
      const initialRatings: Record<number, number> = {};
      reviewCategories.forEach(category => {
        initialRatings[category.id] = 0;
      });
      setRatings(initialRatings);
    } catch (error) {
      const errorMessage = handleApiError(error);
      setError(errorMessage);
      console.error('Error loading booking and categories:', errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const handleRatingChange = (categoryId: number, rating: number) => {
    setRatings(prev => ({
      ...prev,
      [categoryId]: rating
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!bookingData || !isFormValid()) {
      return;
    }
    
    setIsSubmitting(true);
    setError(null);

    try {
      // Prepare category scores for submission
      const categoryScores: CategoryScore[] = Object.entries(ratings)
        .filter(([_, score]) => score > 0)
        .map(([categoryId, score]) => ({
          category_id: parseInt(categoryId),
          score: score
        }));

      await reviewApi.submitReview(bookingData.id, {
        category_scores: categoryScores,
        review_text: writtenFeedback.trim() || undefined
      });
      
      setShowSuccessAlert(true);
      
      // Redirect to dashboard after 3 seconds
      setTimeout(() => {
        router.visit('/customer-dashboard');
      }, 3000);
      
    } catch (error) {
      const errorMessage = handleApiError(error);
      setError(errorMessage);
      console.error('Error submitting review:', errorMessage);
    } finally {
      setIsSubmitting(false);
    }
  };

  const goBack = () => {
    router.visit('/customer-dashboard');
  };

  const renderStars = (categoryId: number) => {
    return (
      <div style={styles.starRating}>
        {[1, 2, 3, 4, 5].map((star) => (
          <button
            key={star}
            type="button"
            style={{
              ...styles.star,
              ...(ratings[categoryId] >= star ? styles.starActive : {})
            }}
            onClick={() => handleRatingChange(categoryId, star)}
            onMouseEnter={(e) => {
              e.currentTarget.style.color = '#fbbf24';
              e.currentTarget.style.transform = 'scale(1.1)';
            }}
            onMouseLeave={(e) => {
              e.currentTarget.style.color = ratings[categoryId] >= star ? '#f59e0b' : '#d1d5db';
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
    const allRatingsProvided = categories.every(category => ratings[category.id] > 0);
    return allRatingsProvided && writtenFeedback.trim().length > 0;
  };
  
  if (loading) {
    return (
      <div style={styles.page}>
        <div style={{ ...styles.container, textAlign: 'center', padding: '4rem 2rem' }}>
          <div style={styles.spinner}></div>
          <p style={{ marginTop: '1rem', color: 'var(--muted-foreground)' }}>Loading review form...</p>
        </div>
      </div>
    );
  }
  
  if (error || !bookingData) {
    return (
      <div style={styles.page}>
        <div style={{ ...styles.container, textAlign: 'center', padding: '4rem 2rem' }}>
          <h1 style={{ color: '#dc2626', marginBottom: '1rem' }}>Error</h1>
          <p style={{ color: 'var(--muted-foreground)', marginBottom: '2rem' }}>
            {error || 'Booking not found or not eligible for review'}
          </p>
          <button 
            style={styles.backButton}
            onClick={goBack}
          >
            <ArrowLeft className="w-5 h-5" />
            Back to Dashboard
          </button>
        </div>
      </div>
    );
  }

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
              <span>Thank you for your feedback! Redirecting to dashboard...</span>
            </div>
          </div>
        )}
        
        {/* Error Alert */}
        {error && (
          <div style={{ ...styles.successAlert, background: '#dc2626' }}>
            <div style={styles.successAlertContent}>
              <X className="w-6 h-6" />
              <span>{error}</span>
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
                <span style={styles.detailValue}>{bookingData.service.name}</span>
              </div>
              <div style={styles.detailItem}>
                <Calendar className="w-4 h-4" />
                <span style={styles.detailLabel}>Scheduled Start:</span>
                <span style={styles.detailValue}>
                  {bookingData.scheduled_start_at 
                    ? new Date(bookingData.scheduled_start_at).toLocaleString()
                    : (bookingData.scheduled_date ? new Date(bookingData.scheduled_date).toLocaleDateString() : 'Not scheduled')
                  }
                </span>
              </div>
              <div style={styles.detailItem}>
                <Clock className="w-4 h-4" />
                <span style={styles.detailLabel}>Scheduled End:</span>
                <span style={styles.detailValue}>
                  {bookingData.scheduled_end_at 
                    ? new Date(bookingData.scheduled_end_at).toLocaleString()
                    : 'Not scheduled'
                  }
                </span>
              </div>
              <div style={styles.detailItem}>
                <User className="w-4 h-4" />
                <span style={styles.detailLabel}>Technician:</span>
                <span style={styles.detailValue}>{bookingData.technician.name}</span>
              </div>
              <div style={styles.detailItem}>
                <MapPin className="w-4 h-4" />
                <span style={styles.detailLabel}>Location:</span>
                <span style={styles.detailValue}>{bookingData.service_location}</span>
              </div>
              <div style={styles.detailItem}>
                <span style={styles.detailLabel}>AC Type:</span>
                <span style={styles.detailValue}>{bookingData.aircon_type.name} - {bookingData.number_of_units} unit{bookingData.number_of_units > 1 ? 's' : ''}</span>
              </div>
              {bookingData.ac_brand && (
                <div style={styles.detailItem}>
                  <span style={styles.detailLabel}>Brand:</span>
                  <span style={styles.detailValue}>{bookingData.ac_brand}</span>
                </div>
              )}
              <div style={styles.detailItem}>
                <span style={styles.detailLabel}>Total Amount:</span>
                <span style={styles.detailValue}>₱{bookingData.total_amount.toLocaleString()}</span>
              </div>
              <div style={styles.detailItem}>
                <span style={styles.detailLabel}>Completed:</span>
                <span style={styles.detailValue}>{new Date(bookingData.completed_date).toLocaleDateString()}</span>
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
                {categories.map((category) => (
                  <div 
                    key={category.id} 
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
                    <label style={styles.ratingLabel}>{category.name}</label>
                    {category.description && (
                      <p style={{ ...styles.ratingText, fontSize: '0.8rem', margin: '0.25rem 0' }}>
                        {category.description}
                      </p>
                    )}
                    {renderStars(category.id)}
                    <span style={styles.ratingText}>
                      {ratings[category.id] === 0 
                        ? 'Not rated' 
                        : `${ratings[category.id]} star${ratings[category.id] > 1 ? 's' : ''}`
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
