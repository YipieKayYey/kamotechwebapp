import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { PublicNavigation } from '@/components/public-navigation';
import { PublicFooter } from '@/components/public-footer';
import { Star, Filter, ChevronDown } from 'lucide-react';
import { useState } from 'react';

export default function Reviews() {
    const { auth } = usePage<SharedData>().props;
    const [filterBy, setFilterBy] = useState('all');
    const [sortBy, setSortBy] = useState('newest');

    // Comprehensive review data
    const allReviews = [
        {
            id: 1,
            rating: 5,
            text: "Excellent service! The technician arrived on time and fixed our AC quickly. Very professional and clean work. Our aircon is cooling better than before!",
            author: "Maria Santos",
            avatar: "M",
            service: "Repair",
            technician: "Mark Anthony Reyes",
            date: "2024-01-15",
            location: "Balanga, Bataan"
        },
        {
            id: 2,
            rating: 5,
            text: "Ayos ang pagkakalagay-gawa. Malamig na ulit, ang sara na ng tulog neto. Salamat!",
            author: "Francis Karl",
            avatar: "F",
            service: "Repair",
            technician: "Mark Anthony Reyes",
            date: "2024-01-12",
            location: "Mariveles, Bataan"
        },
        {
            id: 3,
            rating: 5,
            text: "Ang linis ng pagkakalagay. Ang lamig ulit ng aircon namin pagkatapos nindan. Masayos kausap ang mga staff at mabilis din silang natapos. Ganda ng serbisyo!",
            author: "Sample Customer",
            avatar: "S",
            service: "Cleaning",
            technician: "Mark Anthony Reyes",
            date: "2024-01-10",
            location: "Orani, Bataan"
        },
        {
            id: 4,
            rating: 5,
            text: "Professional installation service. The team was very knowledgeable and explained everything clearly. Our new split-type AC is working perfectly!",
            author: "Roberto Cruz",
            avatar: "R",
            service: "Installation",
            technician: "John Carlo Mendoza",
            date: "2024-01-08",
            location: "Dinalupihan, Bataan"
        },
        {
            id: 5,
            rating: 4,
            text: "Good service overall. The freon charging was done efficiently. Only took about an hour. AC is cooling much better now. Will definitely recommend Kamotech.",
            author: "Anna Reyes",
            avatar: "A",
            service: "Freon Charging",
            technician: "Mark Anthony Reyes",
            date: "2024-01-05",
            location: "Hermosa, Bataan"
        },
        {
            id: 6,
            rating: 5,
            text: "Amazing troubleshooting skills! They quickly identified the problem and fixed it. Very reasonable pricing and excellent customer service.",
            author: "Jose Martinez",
            avatar: "J",
            service: "Troubleshooting",
            technician: "John Carlo Mendoza",
            date: "2024-01-03",
            location: "Bagac, Bataan"
        },
        {
            id: 7,
            rating: 5,
            text: "Perfect repiping job! The old pipes were completely replaced with high-quality materials. No more leaks and the cooling is so much better.",
            author: "Carmen Torres",
            avatar: "C",
            service: "Repiping",
            technician: "Mark Anthony Reyes",
            date: "2024-01-01",
            location: "Morong, Bataan"
        },
        {
            id: 8,
            rating: 5,
            text: "Smooth relocation service. They carefully moved our AC unit to the new room without any damage. Works perfectly in the new location!",
            author: "Miguel Garcia",
            avatar: "M",
            service: "Relocation",
            technician: "John Carlo Mendoza",
            date: "2023-12-28",
            location: "Samal, Bataan"
        },
        {
            id: 9,
            rating: 4,
            text: "Very satisfied with the cleaning service. The team was thorough and professional. My AC is running quieter and cooler now.",
            author: "Linda Flores",
            avatar: "L",
            service: "Cleaning",
            technician: "Mark Anthony Reyes",
            date: "2023-12-25",
            location: "Abucay, Bataan"
        },
        {
            id: 10,
            rating: 5,
            text: "Outstanding repair work! They fixed our old AC that other companies said was beyond repair. Great value for money. Highly recommended!",
            author: "David Villanueva",
            avatar: "D",
            service: "Repair",
            technician: "John Carlo Mendoza",
            date: "2023-12-22",
            location: "Orion, Bataan"
        },
        {
            id: 11,
            rating: 5,
            text: "Professional installation from start to finish. Clean work, no mess left behind. The AC is cooling our entire living room perfectly.",
            author: "Grace Mendoza",
            avatar: "G",
            service: "Installation",
            technician: "Mark Anthony Reyes",
            date: "2023-12-20",
            location: "Pilar, Bataan"
        },
        {
            id: 12,
            rating: 4,
            text: "Quick and efficient freon charging service. The technician explained what was needed and completed the work promptly. Good experience overall.",
            author: "Pedro Aquino",
            avatar: "P",
            service: "Freon Charging",
            technician: "John Carlo Mendoza",
            date: "2023-12-18",
            location: "Limay, Bataan"
        }
    ];

    // Filter reviews based on selected criteria
    const filteredReviews = allReviews.filter(review => {
        if (filterBy === 'all') return true;
        if (filterBy === '5-star') return review.rating === 5;
        if (filterBy === '4-star') return review.rating === 4;
        return review.service.toLowerCase() === filterBy.toLowerCase();
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

    const averageRating = allReviews.reduce((sum, review) => sum + review.rating, 0) / allReviews.length;
    const totalReviews = allReviews.length;
    const fiveStarCount = allReviews.filter(r => r.rating === 5).length;
    const fourStarCount = allReviews.filter(r => r.rating === 4).length;

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
                                    <option value="cleaning">AC Cleaning</option>
                                    <option value="repair">AC Repair</option>
                                    <option value="installation">AC Installation</option>
                                    <option value="freon charging">Freon Charging</option>
                                    <option value="troubleshooting">Troubleshooting</option>
                                    <option value="repiping">Repiping</option>
                                    <option value="relocation">Relocation</option>
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
                            {sortedReviews.map((review) => (
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
                                            <div className="author-name">{review.author}</div>
                                            <div className="author-service">
                                                {review.service} | Technician: {review.technician}
                                            </div>
                                            <div className="author-location">{review.location}</div>
                                        </div>
                                    </div>
                                </div>
                            ))}
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