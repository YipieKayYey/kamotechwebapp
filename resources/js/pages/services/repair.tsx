import { type SharedData } from '@/types';
import { Head, usePage, Link } from '@inertiajs/react';
import { PublicNavigation } from '@/components/public-navigation';
import { PublicFooter } from '@/components/public-footer';
import { CheckCircle, Star, Wind, Snowflake, Settings, Home, Zap, Sparkles } from 'lucide-react';
import { route } from 'ziggy-js';

export default function RepairService() {
    const { auth } = usePage<SharedData>().props;

    const otherServices = [
        { name: 'AC Cleaning', href: '/services/cleaning', icon: Sparkles },
        { name: 'AC Installation', href: '/services/installation', icon: Settings },
        { name: 'Freon Charging', href: '/services/freon-charging', icon: Snowflake },
        { name: 'Repiping', href: '/services/repiping', icon: Wind },
        { name: 'Troubleshooting', href: '/services/troubleshooting', icon: Zap },
        { name: 'Relocation', href: '/services/relocation', icon: Home },
    ];

    return (
        <>
            <Head title="AC Repair Service - Professional Air Conditioner Repair | Kamotech">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            
            <div className="service-page">
                <PublicNavigation />
                
                {/* Hero Section */}
                <section className="service-hero-section">
                    <div className="service-hero-container">
                        <div className="service-hero-content">
                            <h1 className="service-hero-title">AC Repair Service</h1>
                            <p className="service-hero-subtitle">Expert repair solutions for all air conditioning issues</p>
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
                                    src="/images/ac-repair.jpg" 
                                    alt="Professional AC Repair Service" 
                                    className="service-image"
                                />
                            </div>

                            {/* Booking Details */}
                            <div className="booking-details-container">
                                <div className="service-price">
                                    <span className="service-price-label">Starting Price</span>
                                    <span className="service-price-value">₱ 800.00</span>
                                </div>
                                
                                <div className="service-duration">
                                    <span className="service-duration-label">Duration</span>
                                    <span className="service-duration-value">1-3 hours</span>
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
                            <h2 className="service-main-question">Is your AC not working properly?</h2>
                            
                            <p className="service-description">
                                When your air conditioner isn't working as it should, Kamotech is here to help. Our expert technicians 
                                quickly diagnose and repair issues with all types of AC units—whether its poor cooling, strange noises, 
                                leaks, or complete system failure. We use reliable tools and quality parts to restore your system's 
                                performance and ensure long-lasting results. Trust Kamotech to bring back your comfort with fast, 
                                dependable, and affordable AC repair solutions.
                            </p>

                            {/* What's Included Section */}
                            <div className="service-included-section">
                                <h3 className="service-section-title">
                                    <CheckCircle className="service-section-icon" size={24} />
                                    What's Included:
                                </h3>
                                <p className="service-included-text">
                                    Our AC repair service covers a full system inspection, identification of faults, and the replacement 
                                    or repair of defective parts such as capacitors, contactors, compressors, or motors. We use quality 
                                    tools and components to ensure long-term reliability.
                                </p>
                            </div>

                            {/* Benefits Section */}
                            <div className="service-benefits-section">
                                <h3 className="service-section-title">
                                    <Star className="service-section-icon" size={24} />
                                    Benefits:
                                </h3>
                                <p className="service-benefits-text">
                                    With prompt and professional repairs, you can restore your AC's full functionality without unnecessary 
                                    delays. Kamotech's repair service reduces the risk of further damage, minimizes downtime, and saves you 
                                    from costlier replacements by addressing issues early and effectively.
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