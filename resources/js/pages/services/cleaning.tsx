import { type SharedData } from '@/types';
import { Head, usePage, Link } from '@inertiajs/react';
import { PublicNavigation } from '@/components/public-navigation';
import { PublicFooter } from '@/components/public-footer';
import { CheckCircle, Star, Wrench, Wind, Snowflake, Settings, Home, Zap } from 'lucide-react';
import { route } from 'ziggy-js';

export default function CleaningService() {
    const { auth } = usePage<SharedData>().props;

    const otherServices = [
        { name: 'AC Repair', href: '/services/repair', icon: Wrench },
        { name: 'AC Installation', href: '/services/installation', icon: Settings },
        { name: 'Freon Charging', href: '/services/freon-charging', icon: Snowflake },
        { name: 'Repiping', href: '/services/repiping', icon: Wind },
        { name: 'Troubleshooting', href: '/services/troubleshooting', icon: Zap },
        { name: 'Relocation', href: '/services/relocation', icon: Home },
    ];

    return (
        <>
            <Head title="AC Cleaning Service - Professional Air Conditioner Cleaning | Kamotech">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            
            <div className="service-page">
                <PublicNavigation />
                
                {/* Hero Section */}
                <section className="service-hero-section">
                    <div className="service-hero-container">
                        <div className="service-hero-content">
                            <h1 className="service-hero-title">AC Cleaning Service</h1>
                            <p className="service-hero-subtitle">Professional air conditioner cleaning for optimal performance</p>
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
                                    src="/images/ac-cleaning.jpg" 
                                    alt="Professional AC Cleaning Service" 
                                    className="service-image"
                                />
                            </div>

                            {/* Booking Details */}
                            <div className="booking-details-container">
                                <div className="service-price">
                                    <span className="service-price-label">Price</span>
                                    <span className="service-price-value">₱ 450.00</span>
                                </div>
                                
                                <div className="service-duration">
                                    <span className="service-duration-label">Duration</span>
                                    <span className="service-duration-value">60 mins per unit</span>
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
                            <h2 className="service-main-question">Is your AC not cooling like it used to?</h2>
                            
                            <p className="service-description">
                                Professional air conditioner cleaning service ensures your unit runs smoothly and efficiently. 
                                We use safe, effective methods to remove dust, dirt, and allergens that affect your air quality 
                                and system performance.
                            </p>

                            {/* What's Included Section */}
                            <div className="service-included-section">
                                <h3 className="service-section-title">
                                    <CheckCircle className="service-section-icon" size={24} />
                                    What's Included:
                                </h3>
                                <p className="service-included-text">
                                    Our AC cleaning service involves a detailed cleaning of key components including the air filters, 
                                    evaporator and condenser coils, drain pipe, fan, and blower. We also perform sanitization to 
                                    eliminate dust, mold, and bacteria buildup. Finally, we conduct a performance check to ensure 
                                    everything is working efficiently after cleaning.
                                </p>
                            </div>

                            {/* Benefits Section */}
                            <div className="service-benefits-section">
                                <h3 className="service-section-title">
                                    <Star className="service-section-icon" size={24} />
                                    Benefits:
                                </h3>
                                <p className="service-benefits-text">
                                    Regular cleaning improves airflow, enhances cooling performance, and maintains good indoor air quality. 
                                    It helps reduce energy consumption, prevents unpleasant odors, and extends the life of your AC unit. 
                                    Kamotech's cleaning service ensures your system runs smoothly and keeps your environment clean and comfortable.
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