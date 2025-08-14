import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { PublicNavigation } from '@/components/public-navigation';
import { PublicFooter } from '@/components/public-footer';
import { Users, Calendar, Trophy, Heart, CheckCircle, Star, Shield, Clock } from 'lucide-react';

export default function About() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="About Us - Kamotech Air-Conditioning Services">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            
            <div className="about-page">
                <PublicNavigation />
                
                {/* Hero Section */}
                <section className="about-hero-section">
                    <div className="about-hero-container">
                        <div className="about-hero-content">
                            <h1 className="about-hero-title">About Kamotech</h1>
                            <p className="about-hero-subtitle">Your trusted partner in air-conditioning services since 2016</p>
                        </div>
                    </div>
                </section>

                {/* What is Kamotech Section */}
                <section className="about-what-section">
                    <div className="about-container">
                        <div className="about-content-wrapper">
                            <div className="about-section-header">
                                <h2 className="about-section-title">What is Kamotech?</h2>
                                <div className="about-title-underline"></div>
                            </div>
                            
                            <div className="about-kamotech-card">
                                <div className="about-company-name">
                                    <h3>KAMOTECH AIRCON SERVICES</h3>
                                </div>
                                
                                <div className="about-story">
                                    <p>
                                        Established in May 2016, is built on a legacy of hands-on experience, family tradition, 
                                        and a deep commitment to reliable service. Our story begins long before our company was founded. 
                                        When our owner was just a child, he would often accompany his father, a skilled air-conditioning 
                                        technician, on service calls. Growing up surrounded by hardworking family members in the same trade, 
                                        he was inspired to follow in their footsteps and eventually turn his passion into a profession.
                                    </p>
                                    
                                    <p>
                                        With a strong foundation in technical expertise and customer care, Kamotech has grown into a 
                                        dependable name in the air-conditioning service industry. Today, the company proudly operates 
                                        with a team of 8 dedicated personnel, including 5 experienced technicians (with the owner 
                                        still working in the field) and 3 reliable helpers, all working together to deliver efficient, 
                                        high-quality service.
                                    </p>
                                </div>
                                
                                <div className="about-establishment">
                                    <div className="about-establishment-item">
                                        <Calendar className="about-establishment-icon" />
                                        <span>Established May 2016</span>
                                    </div>
                                    <div className="about-establishment-item">
                                        <Users className="about-establishment-icon" />
                                        <span>8 Dedicated Personnel</span>
                                    </div>
                                    <div className="about-establishment-item">
                                        <Heart className="about-establishment-icon" />
                                        <span>Family Tradition</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* What We Do Section */}
                <section className="about-services-section">
                    <div className="about-container">
                        <div className="about-section-header">
                            <h2 className="about-section-title">What we do?</h2>
                            <div className="about-title-underline"></div>
                            <p className="about-section-subtitle">We provide affordable and reliable aircon services, including:</p>
                        </div>
                        
                        <div className="about-services-grid">
                            <div className="about-service-item">
                                <CheckCircle className="about-service-icon" />
                                <span>AC Cleaning</span>
                            </div>
                            <div className="about-service-item">
                                <CheckCircle className="about-service-icon" />
                                <span>AC Repair</span>
                            </div>
                            <div className="about-service-item">
                                <CheckCircle className="about-service-icon" />
                                <span>AC Installation</span>
                            </div>
                            <div className="about-service-item">
                                <CheckCircle className="about-service-icon" />
                                <span>AC Maintenance</span>
                            </div>
                            <div className="about-service-item">
                                <CheckCircle className="about-service-icon" />
                                <span>Freon Charging</span>
                            </div>
                            <div className="about-service-item">
                                <CheckCircle className="about-service-icon" />
                                <span>Repiping</span>
                            </div>
                            <div className="about-service-item">
                                <CheckCircle className="about-service-icon" />
                                <span>Troubleshooting</span>
                            </div>
                            <div className="about-service-item">
                                <CheckCircle className="about-service-icon" />
                                <span>Relocation</span>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Our Team Section */}
                <section className="about-team-section">
                    <div className="about-container">
                        <div className="about-section-header">
                            <h2 className="about-section-title">Our Team</h2>
                            <div className="about-title-underline"></div>
                        </div>
                        
                        <div className="about-team-content">
                            <div className="about-team-stats">
                                <div className="about-team-stat">
                                    <div className="about-team-number">5</div>
                                    <div className="about-team-label">Experienced Technicians</div>
                                    <div className="about-team-note">(including the owner)</div>
                                </div>
                                <div className="about-team-stat">
                                    <div className="about-team-number">3</div>
                                    <div className="about-team-label">Reliable Helpers</div>
                                    <div className="about-team-note">Support & Clean-up</div>
                                </div>
                            </div>
                            
                            <div className="about-team-message">
                                <p>
                                    We may be a small team, but we are committed to doing the job right, every time.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Why Choose Kamotech Section */}
                <section className="about-why-section">
                    <div className="about-container">
                        <div className="about-section-header">
                            <h2 className="about-section-title">Why choose Kamotech?</h2>
                            <div className="about-title-underline"></div>
                        </div>
                        
                        <div className="about-reasons-grid">
                            <div className="about-reason-card">
                                <Trophy className="about-reason-icon" />
                                <h3>Established Legacy</h3>
                                <p>Since 2016 with years of real-world experience</p>
                            </div>
                            
                            <div className="about-reason-card">
                                <Users className="about-reason-icon" />
                                <h3>Expert Technicians</h3>
                                <p>Skilled professionals with hands-on expertise</p>
                            </div>
                            
                            <div className="about-reason-card">
                                <Heart className="about-reason-icon" />
                                <h3>Family Values</h3>
                                <p>Family-driven values, professionally delivered service</p>
                            </div>
                            
                            <div className="about-reason-card">
                                <Clock className="about-reason-icon" />
                                <h3>Fast & Efficient</h3>
                                <p>Quick response and trustworthy service</p>
                            </div>
                            
                            <div className="about-reason-card">
                                <Star className="about-reason-icon" />
                                <h3>Cost-Effective</h3>
                                <p>Energy-efficient and budget-friendly solutions</p>
                            </div>
                            
                            <div className="about-reason-card">
                                <Shield className="about-reason-icon" />
                                <h3>Respectful Service</h3>
                                <p>We treat your home or business with care and respect</p>
                            </div>
                        </div>
                        
                        <div className="about-values-list">
                            <ul>
                                <li>We value your time and comfort</li>
                                <li>Our team is approachable, respectful, and always ready to help</li>
                                <li>From our family to yours, comfort you can count on</li>
                            </ul>
                        </div>
                    </div>
                </section>

                {/* Mission Statement Section */}
                <section className="about-mission-section">
                    <div className="about-container">
                        <div className="about-mission-content">
                            <h2 className="about-mission-title">Our Mission</h2>
                            <div className="about-mission-text">
                                <p>
                                    At Kamotech, we do more than just repair air-conditioning units, we provide services 
                                    you can rely on. We believe in honest work, clear communication, and building trust 
                                    with every client. Whether you're a homeowner or a business owner, our goal is to 
                                    keep your space cool, clean, and comfortable, with service that feels personal and professional.
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