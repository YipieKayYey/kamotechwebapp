import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { PublicNavigation } from '@/components/public-navigation';
import { PublicFooter } from '@/components/public-footer';
import { Star, Filter, ChevronDown } from 'lucide-react';
import { useState } from 'react';

interface Review {
    id: number;
    rating: number;
    text: string;
    author: string;
    avatar: string;
    service: string;
    technician: string;
    date: string;
    location: string;
}

interface ReviewStats {
    totalReviews: number;
    averageRating: number;
    fiveStarCount: number;
    fourStarCount: number;
}

interface ReviewsProps {
    auth: SharedData['auth'];
    allReviews: Review[];
    reviewStats: ReviewStats;
}

export default function Reviews({ auth, allReviews, reviewStats }: ReviewsProps) {
    const [filterBy, setFilterBy] = useState('all');
    const [sortBy, setSortBy] = useState('newest');

    const getFirstName = (fullName?: string | null): string => {
        if (!fullName) return '';
        const trimmed = String(fullName).trim();
        if (!trimmed) return '';
        return trimmed.split(' ')[0];
    };

    // Filter reviews based on selected criteria
    const filteredReviews = allReviews.filter(review => {
        if (filterBy === 'all') return true;
        if (filterBy === '5-star') return review.rating === 5;
        if (filterBy === '4-star') return review.rating === 4;
        // Exact match for service names
        return review.service === filterBy;
    });

    // Sort reviews
    const sortedReviews = [...filteredReviews].sort((a, b) => {
        if (sortBy === 'newest') return new Date(b.date).getTime() - new Date(a.date).getTime();
        if (sortBy === 'oldest') return new Date(a.date).getTime() - new Date(b.date).getTime();
        if (sortBy === 'rating') return b.rating - a.rating;
        return 0;
    });

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    // Use stats from props
    const averageRating = reviewStats.averageRating || 0;
    const totalReviews = reviewStats.totalReviews || 0;
    const fiveStarCount = reviewStats.fiveStarCount || 0;
    const fourStarCount = reviewStats.fourStarCount || 0;

    return (
        <>
            <Head title="Customer Reviews - Kamotech Air Conditioning Services">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            
            <div className="reviews-page">
                <PublicNavigation />
                
                {/* Hero Section */}
                <section className="reviews-hero-section">
                    <div className="reviews-hero-container">
                        <div className="reviews-hero-content">
                            <h1 className="reviews-hero-title">Customer Reviews</h1>
                            <p className="reviews-hero-subtitle">See what our satisfied customers say about Kamotech services</p>
                            
                            {/* Reviews Summary */}
                            <div className="reviews-summary">
                                <div className="summary-rating">
                                    <div className="summary-stars">
                                        {[1, 2, 3, 4, 5].map((star) => (
                                            <Star 
                                                key={star} 
                                                className={`summary-star ${star <= Math.round(averageRating) ? 'filled' : ''}`}
                                            />
                                        ))}
                                    </div>
                                    <span className="summary-score">{averageRating.toFixed(1)}</span>
                                </div>
                                <div className="summary-stats">
                                    <span className="total-reviews">{totalReviews} Reviews</span>
                                    <span className="rating-breakdown">{fiveStarCount} five-star • {fourStarCount} four-star</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Reviews Content */}
                <section className="reviews-content-section">
                    <div className="reviews-container">
                        {/* Filters and Sort */}
                        <div className="reviews-controls">
                            <div className="filter-group">
                                <label htmlFor="filter-select" className="control-label">
                                    <Filter size={18} />
                                    Filter by:
                                </label>
                                <select 
                                    id="filter-select"
                                    value={filterBy} 
                                    onChange={(e) => setFilterBy(e.target.value)}
                                    className="control-select"
                                >
                                    <option value="all">All Reviews</option>
                                    <option value="5-star">5 Star Reviews</option>
                                    <option value="4-star">4 Star Reviews</option>
                                    <option value="AC Cleaning">AC Cleaning</option>
                                    <option value="AC Repair">AC Repair</option>
                                    <option value="AC Installation">AC Installation</option>
                                    <option value="Freon Charging">Freon Charging</option>
                                    <option value="Troubleshooting">Troubleshooting</option>
                                    <option value="Repiping">Repiping</option>
                                    <option value="Relocation">Relocation</option>
                                </select>
                            </div>

                            <div className="sort-group">
                                <label htmlFor="sort-select" className="control-label">
                                    <ChevronDown size={18} />
                                    Sort by:
                                </label>
                                <select 
                                    id="sort-select"
                                    value={sortBy} 
                                    onChange={(e) => setSortBy(e.target.value)}
                                    className="control-select"
                                >
                                    <option value="newest">Newest First</option>
                                    <option value="oldest">Oldest First</option>
                                    <option value="rating">Highest Rating</option>
                                </select>
                            </div>

                            <div className="results-count">
                                Showing {sortedReviews.length} of {totalReviews} reviews
                            </div>
                        </div>

                        {/* Reviews Grid */}
                        <div className="reviews-grid">
                            {sortedReviews.length === 0 ? (
                                <div className="no-reviews">
                                    <p>No reviews found matching your criteria.</p>
                                </div>
                            ) : (
                                sortedReviews.map((review) => (
                                    <div key={review.id} className="review-card">
                                        <div className="review-header">
                                            <div className="review-stars">
                                                {[1, 2, 3, 4, 5].map((star) => (
                                                    <Star 
                                                        key={star} 
                                                        className={`star ${star <= review.rating ? 'filled' : ''}`}
                                                    />
                                                ))}
                                            </div>
                                            <div className="review-date">
                                                {formatDate(review.date)}
                                            </div>
                                        </div>
                                        
                                        <p className="review-text">"{review.text}"</p>
                                        
                                        <div className="review-author">
                                            <div className="author-avatar">{review.avatar}</div>
                                            <div className="author-info">
                                                <div className="author-name">{getFirstName(review.author)}</div>
                                                <div className="author-service">
                                                    {review.service} | Technician: {getFirstName(review.technician)}
                                                </div>
                                                <div className="author-location">{review.location}</div>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>

                        {/* Load More or Pagination could go here */}
                        <div className="reviews-footer">
                            <p className="reviews-note">
                                All reviews are from verified customers who have used our services.
                            </p>
                        </div>
                    </div>
                </section>

                <PublicFooter />
            </div>
        </>
    );
}
