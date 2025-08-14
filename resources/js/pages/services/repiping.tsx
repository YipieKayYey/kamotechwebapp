import { type SharedData } from '@/types';
import { Head, usePage, Link } from '@inertiajs/react';
import { PublicNavigation } from '@/components/public-navigation';
import { PublicFooter } from '@/components/public-footer';
import { CheckCircle, Star, Wrench, Snowflake, Settings, Home, Zap, Sparkles } from 'lucide-react';
import { route } from 'ziggy-js';

export default function RepipingService() {
    const { auth } = usePage<SharedData>().props;

    const otherServices = [
        { name: 'AC Cleaning', href: '/services/cleaning', icon: Sparkles },
        { name: 'AC Repair', href: '/services/repair', icon: Wrench },
        { name: 'AC Installation', href: '/services/installation', icon: Settings },
        { name: 'Freon Charging', href: '/services/freon-charging', icon: Snowflake },
        { name: 'Troubleshooting', href: '/services/troubleshooting', icon: Zap },
        { name: 'Relocation', href: '/services/relocation', icon: Home },
    ];

    return (
        <>
            <Head title="AC Repiping Service - Professional Refrigerant Line Replacement | Kamotech">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            
            <div className="service-page">
                <PublicNavigation />
                
                {/* Hero Section */}
                <section className="service-hero-section">
                    <div className="service-hero-container">
                        <div className="service-hero-content">
                            <h1 className="service-hero-title">AC Repiping Service</h1>
                            <p className="service-hero-subtitle">Professional refrigerant line replacement and upgrading</p>
                        </div>
                    </div>
                </section>

                {/* Service Content Section */}
                <section className="service-content-section">
                    <div className="service-container">
                        <div className="service-content-grid">
                            {/* Service Image */}
                            <div className="service-image-container">
                                <img 
                                    src="/images/ac-repiping.jpg" 
                                    alt="Professional AC Repiping Service" 
                                    className="service-image"
                                />
                            </div>

                            {/* Booking Details */}
                            <div className="booking-details-container">
                                <div className="service-price">
                                    <span className="service-price-label">Starting Price</span>
                                    <span className="service-price-value">₱ 3,500.00</span>
                                </div>
                                
                                <div className="service-duration">
                                    <span className="service-duration-label">Duration</span>
                                    <span className="service-duration-value">3-6 hours</span>
                                </div>
                                
                                <Link href={route('booking')} className="service-book-btn">
                                    Book Now
                                </Link>

                                {/* Other Services Section */}
                                <div className="service-other-services-compact">
                                    <h3>Other Services</h3>
                                    <div className="service-other-grid-compact">
                                        {otherServices.map((service, index) => {
                                            const IconComponent = service.icon;
                                            return (
                                                <Link 
                                                    key={index}
                                                    href={service.href} 
                                                    className="service-other-item-compact"
                                                >
                                                    <IconComponent size={18} style={{ color: '#083860' }} />
                                                    <span>{service.name}</span>
                                                </Link>
                                            );
                                        })}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Service Information Section */}
                <section className="service-info-section">
                    <div className="service-container">
                        <div className="service-info-content">
                            <h2 className="service-main-question">Need to replace your AC pipes?</h2>
                            
                            <p className="service-description">
                                Worn-out or damaged refrigerant lines can severely affect your air conditioners efficiency and cause 
                                long-term damage. Kamotech provides professional repiping services to replace or upgrade these critical 
                                components. Using high-quality, insulated copper tubing, our team ensures that refrigerant flows 
                                efficiently and safely between indoor and outdoor units. Whether it's due to corrosion, leaks, or unit 
                                relocation, our repiping service is performed with precision and care to restore and maintain your AC 
                                systems peak performance.
                            </p>

                            {/* What's Included Section */}
                            <div className="service-included-section">
                                <h3 className="service-section-title">
                                    <CheckCircle className="service-section-icon" size={24} />
                                    What's Included:
                                </h3>
                                <p className="service-included-text">
                                    Kamotech's repiping service includes removing old or damaged refrigerant pipes and replacing them 
                                    with high-quality, insulated copper tubing. We seal and test all connections to ensure leak-free 
                                    and efficient operation.
                                </p>
                            </div>

                            {/* Benefits Section */}
                            <div className="service-benefits-section">
                                <h3 className="service-section-title">
                                    <Star className="service-section-icon" size={24} />
                                    Benefits:
                                </h3>
                                <p className="service-benefits-text">
                                    New piping improves refrigerant flow, enhances cooling performance, and prevents potential leaks 
                                    that could damage your unit. Repiping is essential during system upgrades or relocations, and 
                                    Kamotech ensures a clean, secure, and professional installation that supports long-term system health.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <PublicFooter />
            </div>
        </>
    );
}