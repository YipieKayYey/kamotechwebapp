import { type SharedData } from '@/types';
import { Head, usePage, Link } from '@inertiajs/react';
import { PublicNavigation } from '@/components/public-navigation';
import { PublicFooter } from '@/components/public-footer';
import { CheckCircle, Star, Wrench, Wind, Snowflake, Home, Zap, Sparkles } from 'lucide-react';
import { route } from 'ziggy-js';

export default function InstallationService() {
    const { auth } = usePage<SharedData>().props;

    const otherServices = [
        { name: 'AC Cleaning', href: '/services/cleaning', icon: Sparkles },
        { name: 'AC Repair', href: '/services/repair', icon: Wrench },
        { name: 'Freon Charging', href: '/services/freon-charging', icon: Snowflake },
        { name: 'Repiping', href: '/services/repiping', icon: Wind },
        { name: 'Troubleshooting', href: '/services/troubleshooting', icon: Zap },
        { name: 'Relocation', href: '/services/relocation', icon: Home },
    ];

    return (
        <>
            <Head title="AC Installation Service - Professional Air Conditioner Installation | Kamotech">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            
            <div className="service-page">
                <PublicNavigation />
                
                {/* Hero Section */}
                <section className="service-hero-section">
                    <div className="service-hero-container">
                        <div className="service-hero-content">
                            <h1 className="service-hero-title">AC Installation Service</h1>
                            <p className="service-hero-subtitle">Expert air conditioning installation for homes and businesses</p>
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
                                    src="/images/ac-installation.jpg" 
                                    alt="Professional AC Installation Service" 
                                    className="service-image"
                                />
                            </div>

                            {/* Booking Details */}
                            <div className="booking-details-container">
                                <div className="service-price">
                                    <span className="service-price-label">Starting Price</span>
                                    <span className="service-price-value">₱ 2,500.00</span>
                                </div>
                                
                                <div className="service-duration">
                                    <span className="service-duration-label">Duration</span>
                                    <span className="service-duration-value">2-4 hours</span>
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
                            <h2 className="service-main-question">Need a new AC installed?</h2>
                            
                            <p className="service-description">
                                Kamotech provides expert air conditioning installation services designed to meet the specific needs 
                                of homes and businesses. Our skilled technicians follow industry standards to ensure each unit is 
                                properly installed—from secure mounting and electrical connections to correct refrigerant line setup. 
                                We handle a wide range of AC systems including split-type, window-type, and floor standing units. 
                                Our goal is to deliver safe, efficient, and long-lasting performance from the moment your unit is 
                                installed. With Kamotech, you can trust that your AC is set up for maximum comfort and reliability.
                            </p>

                            {/* What's Included Section */}
                            <div className="service-included-section">
                                <h3 className="service-section-title">
                                    <CheckCircle className="service-section-icon" size={24} />
                                    What's Included:
                                </h3>
                                <p className="service-included-text">
                                    We provide complete installation for various AC units including split-type, window-type, and floor 
                                    standing units. The service includes mounting the unit, installing refrigerant piping, connecting 
                                    electrical components, and testing the system to ensure proper operation.
                                </p>
                            </div>

                            {/* Benefits Section */}
                            <div className="service-benefits-section">
                                <h3 className="service-section-title">
                                    <Star className="service-section-icon" size={24} />
                                    Benefits:
                                </h3>
                                <p className="service-benefits-text">
                                    A properly installed air conditioning system ensures efficient performance and reduces the chance 
                                    of early malfunctions. Kamotech guarantees professional installation that meets safety and 
                                    manufacturer standards, helping you enjoy a cool and worry-free indoor environment from day one.
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