import { type SharedData } from '@/types';
import { Head, usePage, Link } from '@inertiajs/react';
import { PublicNavigation } from '@/components/public-navigation';
import { PublicFooter } from '@/components/public-footer';
import { CheckCircle, Star, Wrench, Wind, Snowflake, Settings, Zap, Sparkles } from 'lucide-react';
import { route } from 'ziggy-js';

export default function RelocationService() {
    const { auth } = usePage<SharedData>().props;

    const otherServices = [
        { name: 'AC Cleaning', href: '/services/cleaning', icon: Sparkles },
        { name: 'AC Repair', href: '/services/repair', icon: Wrench },
        { name: 'AC Installation', href: '/services/installation', icon: Settings },
        { name: 'Freon Charging', href: '/services/freon-charging', icon: Snowflake },
        { name: 'Repiping', href: '/services/repiping', icon: Wind },
        { name: 'Troubleshooting', href: '/services/troubleshooting', icon: Zap },
    ];

    return (
        <>
            <Head title="AC Relocation Service - Professional Air Conditioner Moving | Kamotech">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            
            <div className="service-page">
                <PublicNavigation />
                
                {/* Hero Section */}
                <section className="service-hero-section">
                    <div className="service-hero-container">
                        <div className="service-hero-content">
                            <h1 className="service-hero-title">AC Relocation Service</h1>
                            <p className="service-hero-subtitle">Professional air conditioner moving and reinstallation</p>
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
                                    src="/images/ac-relocation.jpg" 
                                    alt="Professional AC Relocation Service" 
                                    className="service-image"
                                />
                            </div>

                            {/* Booking Details */}
                            <div className="booking-details-container">
                                <div className="service-price">
                                    <span className="service-price-label">Starting Price</span>
                                    <span className="service-price-value">₱ 4,000.00</span>
                                </div>
                                
                                <div className="service-duration">
                                    <span className="service-duration-label">Duration</span>
                                    <span className="service-duration-value">4-8 hours</span>
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
                            <h2 className="service-main-question">Moving to a new location?</h2>
                            
                            <p className="service-description">
                                Relocating an air conditioning unit requires care, precision, and technical expertise. Kamotech provides 
                                professional AC relocation services for homeowners, offices, and commercial spaces. We manage the safe 
                                disassembly, transport, and reinstallation of your AC unit while ensuring all components, including 
                                refrigerant lines and electrical connections, are handled properly. Whether you are renovating, moving 
                                to a new home, or simply repositioning your unit, we make sure it operates efficiently and safely in its 
                                new location, with minimal disruption to your routine.
                            </p>

                            {/* What's Included Section */}
                            <div className="service-included-section">
                                <h3 className="service-section-title">
                                    <CheckCircle className="service-section-icon" size={24} />
                                    What's Included:
                                </h3>
                                <p className="service-included-text">
                                    Our AC relocation service covers safe disassembly of the unit, refrigerant recovery, careful transport, 
                                    and full reinstallation at the new location. We inspect all components for damage and ensure the system 
                                    is tested and fully functional after reinstallation.
                                </p>
                            </div>

                            {/* Benefits Section */}
                            <div className="service-benefits-section">
                                <h3 className="service-section-title">
                                    <Star className="service-section-icon" size={24} />
                                    Benefits:
                                </h3>
                                <p className="service-benefits-text">
                                    Relocating your AC can be risky if not done properly. Kamotech's expert team ensures your system is 
                                    moved and reinstalled without damage or performance issues. Whether you are renovating or changing 
                                    room layouts, we make the transition seamless and stress-free.
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