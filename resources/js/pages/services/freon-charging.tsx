import { type SharedData } from '@/types';
import { Head, usePage, Link } from '@inertiajs/react';
import { PublicNavigation } from '@/components/public-navigation';
import { PublicFooter } from '@/components/public-footer';
import { CheckCircle, Star, Wrench, Wind, Settings, Home, Zap, Sparkles } from 'lucide-react';
import { route } from 'ziggy-js';

export default function FreonChargingService() {
    const { auth } = usePage<SharedData>().props;

    const otherServices = [
        { name: 'AC Cleaning', href: '/services/cleaning', icon: Sparkles },
        { name: 'AC Repair', href: '/services/repair', icon: Wrench },
        { name: 'AC Installation', href: '/services/installation', icon: Settings },
        { name: 'Repiping', href: '/services/repiping', icon: Wind },
        { name: 'Troubleshooting', href: '/services/troubleshooting', icon: Zap },
        { name: 'Relocation', href: '/services/relocation', icon: Home },
    ];

    return (
        <>
            <Head title="Freon Charging Service - Professional AC Refrigerant Service | Kamotech">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            
            <div className="service-page">
                <PublicNavigation />
                
                {/* Hero Section */}
                <section className="service-hero-section">
                    <div className="service-hero-container">
                        <div className="service-hero-content">
                            <h1 className="service-hero-title">Freon Charging Service</h1>
                            <p className="service-hero-subtitle">Professional refrigerant charging for optimal cooling performance</p>
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
                                    src="/images/ac-freon.jpg" 
                                    alt="Professional Freon Charging Service" 
                                    className="service-image"
                                />
                            </div>

                            {/* Booking Details */}
                            <div className="booking-details-container">
                                <div className="service-price">
                                    <span className="service-price-label">Starting Price</span>
                                    <span className="service-price-value">₱ 1,200.00</span>
                                </div>
                                
                                <div className="service-duration">
                                    <span className="service-duration-label">Duration</span>
                                    <span className="service-duration-value">45-90 minutes</span>
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
                            <h2 className="service-main-question">Is your AC not cooling enough?</h2>
                            
                            <p className="service-description">
                                A properly charged air conditioning system is critical for effective cooling. Kamotech offers accurate 
                                and safe Freon charging services to restore your unit's performance. Low refrigerant levels can lead 
                                to poor cooling, higher energy use, and even compressor damage. Our technicians use the correct type 
                                and amount of refrigerant for your specific system, following strict safety procedures to prevent leaks 
                                and ensure optimal operation. We also check for potential causes of Freon loss to provide a complete 
                                and reliable solution.
                            </p>

                            {/* What's Included Section */}
                            <div className="service-included-section">
                                <h3 className="service-section-title">
                                    <CheckCircle className="service-section-icon" size={24} />
                                    What's Included:
                                </h3>
                                <p className="service-included-text">
                                    This service includes measuring the current refrigerant level, locating possible leaks, and charging 
                                    the unit with the appropriate type and amount of Freon (refrigerant). We also test the cooling output 
                                    to confirm optimal performance.
                                </p>
                            </div>

                            {/* Benefits Section */}
                            <div className="service-benefits-section">
                                <h3 className="service-section-title">
                                    <Star className="service-section-icon" size={24} />
                                    Benefits:
                                </h3>
                                <p className="service-benefits-text">
                                    Proper refrigerant levels are crucial for cooling efficiency. Our Freon charging service restores 
                                    the system's ability to cool your space effectively, protects the compressor, and reduces energy usage. 
                                    Kamotech ensures your AC runs smoothly and safely with the right refrigerant balance.
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