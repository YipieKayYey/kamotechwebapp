import { type SharedData } from '@/types';
import { Head, usePage, Link } from '@inertiajs/react';
import { PublicNavigation } from '@/components/public-navigation';
import { PublicFooter } from '@/components/public-footer';
import { CheckCircle, Star, Wrench, Wind, Snowflake, Settings, Home, Sparkles } from 'lucide-react';
import { route } from 'ziggy-js';

export default function TroubleshootingService() {
    const { auth } = usePage<SharedData>().props;

    const otherServices = [
        { name: 'AC Cleaning', href: '/services/cleaning', icon: Sparkles },
        { name: 'AC Repair', href: '/services/repair', icon: Wrench },
        { name: 'AC Installation', href: '/services/installation', icon: Settings },
        { name: 'Freon Charging', href: '/services/freon-charging', icon: Snowflake },
        { name: 'Repiping', href: '/services/repiping', icon: Wind },
        { name: 'Relocation', href: '/services/relocation', icon: Home },
    ];

    return (
        <>
            <Head title="AC Troubleshooting Service - Professional Air Conditioner Diagnostics | Kamotech">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            
            <div className="service-page">
                <PublicNavigation />
                
                {/* Hero Section */}
                <section className="service-hero-section">
                    <div className="service-hero-container">
                        <div className="service-hero-content">
                            <h1 className="service-hero-title">AC Troubleshooting Service</h1>
                            <p className="service-hero-subtitle">Expert diagnostics and problem-solving for AC issues</p>
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
                                    src="/images/troubleshooting.jpg" 
                                    alt="Professional AC Troubleshooting Service" 
                                    className="service-image"
                                />
                            </div>

                            {/* Booking Details */}
                            <div className="booking-details-container">
                                <div className="service-price">
                                    <span className="service-price-label">Starting Price</span>
                                    <span className="service-price-value">₱ 500.00</span>
                                </div>
                                
                                <div className="service-duration">
                                    <span className="service-duration-label">Duration</span>
                                    <span className="service-duration-value">1-2 hours</span>
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
                                Unexpected AC problems can be frustrating and uncomfortable, especially during hot weather. Kamotech 
                                offers a comprehensive troubleshooting service to identify and resolve all types of air conditioning 
                                issues. Our experienced technicians perform a detailed assessment of your system, including electrical 
                                checks, airflow testing, and refrigerant analysis. Once the root cause is found, we provide clear 
                                recommendations and fast solutions to get your system back in working order. Whether it's strange noises, 
                                weak airflow, or complete failure, Kamotech has the expertise to fix it.
                            </p>

                            {/* What's Included Section */}
                            <div className="service-included-section">
                                <h3 className="service-section-title">
                                    <CheckCircle className="service-section-icon" size={24} />
                                    What's Included:
                                </h3>
                                <p className="service-included-text">
                                    This service includes a comprehensive inspection of all AC components—electrical systems, thermostat, 
                                    motors, refrigerant levels, and airflow. We identify the root cause of problems and provide expert 
                                    recommendations for resolution.
                                </p>
                            </div>

                            {/* Benefits Section */}
                            <div className="service-benefits-section">
                                <h3 className="service-section-title">
                                    <Star className="service-section-icon" size={24} />
                                    Benefits:
                                </h3>
                                <p className="service-benefits-text">
                                    Kamotech's troubleshooting helps you quickly pinpoint issues without guesswork. It prevents unnecessary 
                                    repairs, saves time, and ensures that the right fix is applied the first time. Our goal is to restore 
                                    your comfort and system efficiency with accurate diagnostics.
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