import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { PublicNavigation } from '@/components/public-navigation';
import { PublicFooter } from '@/components/public-footer';
import { HeroSlider } from '@/components/hero-slider';
import { CheckCircle, Clock, Users, Wrench, Shield, Star, Phone } from 'lucide-react';
import { route } from 'ziggy-js';

interface WelcomePageProps extends SharedData {
    promotions?: Array<{
        id: number;
        welcome_text: string | null;
        title: string;
        subtitle: string | null;
        primary_button_text: string;
        primary_button_link: string;
        secondary_button_text: string | null;
        secondary_button_link: string | null;
        background_image: string | null;
        discount: string | null;
        promo_code: string | null;
    }>;
    latestReviews?: Array<{
        id: number;
        rating: number;
        text: string;
        author: string;
        avatar: string;
        service: string;
        technician: string;
    }>;
}

export default function Welcome() {
    const { auth, promotions, latestReviews } = usePage<WelcomePageProps>().props;
    const getFirstName = (fullName?: string | null): string => {
        if (!fullName) return '';
        const trimmed = String(fullName).trim();
        if (!trimmed) return '';
        return trimmed.split(' ')[0];
    };

    return (
        <>
            <Head title="Kamotech - Air-Conditioning Services">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            
            <div className="welcome-page">
                            <PublicNavigation />
            
            {/* Hero Slider Section */}
            <HeroSlider promotions={promotions} />

                {/* Company Hero Section */}
                <section className="company-hero-section">
                    <div className="section-container">
                        <div className="company-hero-content">
                            <h1 className="company-hero-title">
                                Air Conditioning Services by <span className="company-brand">Kamotech</span>
                            </h1>
                            
                            <div className="company-hero-card">
                                <div className="company-tagline">
                                    <span className="tagline-text">Find the affordable, Find your satisfaction!</span>
                                </div>
                                
                                <div className="company-description">
                                    <p className="description-title">Looking for a trusted team to take care of your air conditioning needs?</p>
                                    <p className="description-text">
                                        At Kamotech, we offer reliable, affordable, and professional AC services—from cleaning and 
                                        repair to installation and relocation. Proudly based in Balanga City, Bataan, our small but 
                                        dedicated team is ready to serve homes and businesses across the province of Bataan. We're 
                                        committed to keeping you cool and comfortable all year round!
                                    </p>
                                </div>
                                
                                <div className="company-cta">
                                    <button className="reliable-service-btn">
                                        Your Comfort, Our Priority
                                    </button>
                                </div>
                                
                                <div className="company-commitment">
                                    <p className="commitment-text">
                                        Your satisfaction and comfort are our top priorities. Every job we do reflects our 
                                        commitment to quality workmanship, fair pricing, and genuine customer care.
                                    </p>
                                </div>
                                
                                <div className="company-values">
                                    <div className="value-item">
                                        <div className="value-icon">
                                            <CheckCircle />
                                        </div>
                                        <span className="value-text">Quality Work</span>
                                    </div>
                                    <div className="value-item">
                                        <div className="value-icon">
                                            <Clock />
                                        </div>
                                        <span className="value-text">Timely Service</span>
                                    </div>
                                    <div className="value-item">
                                        <div className="value-icon">
                                            <span className="peso-symbol">₱</span>
                                        </div>
                                        <span className="value-text">Fair Pricing</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
                </section>
                
                {/* Choice of Services Section */}
                <section id="services" className="choice-services-section">
                    <div className="section-container">
                        <div className="section-header">
                            <h2 className="choice-title">
                                Kamotech <span className="choice-highlight">Choice</span> of Services
                            </h2>
                        </div>
                        
                        <div className="choice-services-grid">
                            <div className="choice-service-card">
                                <div className="choice-service-image">
                                    <img src="images/ac-cleaning.jpg" alt="AC Cleaning" />
                                </div>
                                <h3 className="choice-service-title">AC Cleaning</h3>
                                <p className="choice-service-description">Is your AC not cooling like it used to?</p>
                                <Link href={route('booking')} className="choice-book-btn">Book Now</Link>
                            </div>
                            
                            <div className="choice-service-card">
                                <div className="choice-service-image">
                                    <img src="/images/ac-repair.jpg" alt="AC Repair" />
                                </div>
                                <h3 className="choice-service-title">AC Repair</h3>
                                <p className="choice-service-description">Is your AC not working properly?</p>
                                <Link href={route('booking')} className="choice-book-btn">Book Now</Link>
                            </div>
                            
                            <div className="choice-service-card">
                                <div className="choice-service-image">
                                    <img src="/images/ac-installation.jpg" alt="AC Installation" />
                                </div>
                                <h3 className="choice-service-title">AC Installation</h3>
                                <p className="choice-service-description">Need a new AC installed?</p>
                                <Link href={route('booking')} className="choice-book-btn">Book Now</Link>
                            </div>

                            <div className="choice-service-card">
                                <div className="choice-service-image">
                                    <img src="/images/ac-freon.jpg" alt="AC Freon Charging" />
                                </div>
                                <h3 className="choice-service-title">AC Freon Charging</h3>
                                <p className="choice-service-description">Is your AC not cooling enough?</p>
                                <Link href={route('booking')} className="choice-book-btn">Book Now</Link>   
                            </div>
                        </div>
                    </div>
                </section>

                {/* Aircon Types Section */}
                <section className="aircon-types-section">
                    <div className="section-container">
                        <div className="section-header">
                            <h2 className="aircon-types-title">Aircon Types We Service</h2>
                        </div>
                        
                        <div className="aircon-types-grid">
                            <div className="aircon-type-card">
                                <div className="aircon-type-image">
                                    <img src="/images/window.png" alt="Window Type AC" />
                                </div>
                                <h3 className="aircon-type-title">Window Type</h3>
                            </div>
                            
                            <div className="aircon-type-card">
                                <div className="aircon-type-image">
                                    <img src="/images/split.png" alt="Split Type AC" />
                                </div>
                                <h3 className="aircon-type-title">Split Type</h3>
                            </div>
                            
                            <div className="aircon-type-card">
                                <div className="aircon-type-image">
                                    <img src="/images/tower.png" alt="Floor Standing AC" />
                                </div>
                                <h3 className="aircon-type-title">Floor Standing</h3>
                            </div>
                        </div>
                    </div>
                </section>

                {/* More Services Section */}
                <section className="more-services-section">
                    <div className="section-container">
                        <div className="section-header">
                            <h2 className="more-services-title">
                                More <span className="kamotech-highlight">Kamotech</span> Services
                            </h2>
                        </div>
                        
                        <div className="more-services-grid">
                            <div className="more-service-card">
                                <div className="more-service-image">
                                    <img src="/images/ac-repiping.jpg" alt="Repiping" />
                                </div>
                                <h3 className="more-service-title">Repiping</h3>
                                <p className="more-service-description">Need to replace your AC pipes?</p>
                                <Link href={route('booking')} className="more-book-btn">Book Now</Link>
                            </div>
                            
                            <div className="more-service-card">
                                <div className="more-service-image">
                                    <img src="/images/troubleshooting.jpg" alt="Troubleshooting" />
                                </div>
                                <h3 className="more-service-title">Troubleshooting</h3>
                                <p className="more-service-description">Is your AC not working properly?</p>
                                <Link href={route('booking')} className="more-book-btn">Book Now</Link>
                            </div>
                            
                            <div className="more-service-card">
                                <div className="more-service-image">
                                    <img src="/images/ac-relocation.jpg" alt="AC Relocation" />
                                </div>
                                <h3 className="more-service-title">AC Relocation</h3>
                                <p className="more-service-description">Moving to a new location?</p>
                                <Link href={route('booking')} className="more-book-btn">Book Now</Link>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Brands Section */}
                <section className="brands-section">
                    <div className="section-container">
                        <div className="section-header">
                            <h2 className="brands-title">
                                All <span className="brands-highlight">Brands</span> Serviced
                            </h2>
                            <p className="brands-subtitle">We offer essential services for all brands and units.</p>
                        </div>
                        
                        <div className="brands-grid">
                            <div className="brand-logo">
                                <img src="/images/brands/carrier.png" alt="Carrier" />
                            </div>
                            <div className="brand-logo">
                                <img src="/images/brands/kolin.png" alt="Kolin" />
                            </div>
                            <div className="brand-logo">
                                <img src="/images/brands/lg.png" alt="LG" />
                            </div>
                            <div className="brand-logo">
                                <img src="/images/brands/koppel.png" alt="Koppel" />
                            </div>
                            <div className="brand-logo">
                                <img src="/images/brands/panasonic.png" alt="Panasonic" />
                            </div>
                            <div className="brand-logo">
                                <img src="/images/brands/sharp.png" alt="Sharp" />
                            </div>
                            <div className="brand-logo">
                                <img src="/images/brands/condura.png" alt="Condura" />
                            </div>
                            <div className="brand-logo">
                                <img src="/images/brands/fujidenzo.jpg" alt="Fujidenzo" />
                            </div>
                            <div className="brand-logo">
                                <img src="/images/brands/samsung.png" alt="Samsung" />
                            </div>
                            <div className="brand-logo">
                                <img src="/images/brands/daikin.png" alt="Daikin" />
                            </div>
                            <div className="brand-logo">
                                <img src="/images/brands/hitachi.png" alt="Hitachi" />
                            </div>
                        </div>
                        
                        <div className="brands-contact">
                            <p>Not sure if we service your AC? <a href="#contact" className="contact-link">Contact Us!</a></p>
                        </div>
                    </div>
                </section>

                {/* Reviews Section */}
                <section className="reviews-section">
                    <div className="section-container">
                        <div className="section-header">
                            <h2 className="reviews-title">
                                Customer <span className="reviews-highlight">Reviews</span>
                            </h2>
                            <p className="reviews-subtitle">See what our customers say about our services</p>
                        </div>
                        
                        <div className="reviews-grid">
                            {latestReviews && latestReviews.length > 0 ? (
                                latestReviews.map((review) => (
                                    <div key={review.id} className="review-card">
                                        <div className="review-stars">
                                            {[1, 2, 3, 4, 5].map((star) => (
                                                <Star 
                                                    key={star} 
                                                    className={`star ${star <= review.rating ? 'filled' : ''}`} 
                                                />
                                            ))}
                                        </div>
                                        <p className="review-text">
                                            "{review.text}"
                                        </p>
                                        <div className="review-author">
                                            <div className="author-avatar">{review.avatar}</div>
                                            <div className="author-info">
                                                <div className="author-name">{getFirstName(review.author)}</div>
                                                <div className="author-service">{review.service} | Technician: {getFirstName(review.technician)}</div>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                // Fallback to static reviews if no reviews in database
                                <>
                                    <div className="review-card">
                                        <div className="review-stars">
                                            <Star className="star filled" />
                                            <Star className="star filled" />
                                            <Star className="star filled" />
                                            <Star className="star filled" />
                                            <Star className="star filled" />
                                        </div>
                                        <p className="review-text">
                                            "Excellent service! The technician was professional and fixed my AC quickly."
                                        </p>
                                        <div className="review-author">
                                            <div className="author-avatar">C</div>
                                            <div className="author-info">
                                                <div className="author-name">Customer</div>
                                                <div className="author-service">AC Repair | Technician: Kamotech Team</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="review-card">
                                        <div className="review-stars">
                                            <Star className="star filled" />
                                            <Star className="star filled" />
                                            <Star className="star filled" />
                                            <Star className="star filled" />
                                            <Star className="star filled" />
                                        </div>
                                        <p className="review-text">
                                            "Very satisfied with the cleaning service. My AC is running like new again!"
                                        </p>
                                        <div className="review-author">
                                            <div className="author-avatar">H</div>
                                            <div className="author-info">
                                                <div className="author-name">Happy Client</div>
                                                <div className="author-service">AC Cleaning | Technician: Kamotech Team</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="review-card">
                                        <div className="review-stars">
                                            <Star className="star filled" />
                                            <Star className="star filled" />
                                            <Star className="star filled" />
                                            <Star className="star filled" />
                                            <Star className="star filled" />
                                        </div>
                                        <p className="review-text">
                                            "Professional installation service. The team was knowledgeable and efficient."
                                        </p>
                                        <div className="review-author">
                                            <div className="author-avatar">S</div>
                                            <div className="author-info">
                                                <div className="author-name">Satisfied Customer</div>
                                                <div className="author-service">AC Installation | Technician: Kamotech Team</div>
                                            </div>
                                        </div>
                                    </div>
                                </>
                            )}
                        </div>
                        
                        <div className="reviews-action">
                            <Link href="/reviews" className="view-all-reviews-btn">View All Reviews</Link>
                        </div>
                </div>
                </section>

                <PublicFooter />
            </div>
        </>
    );
}