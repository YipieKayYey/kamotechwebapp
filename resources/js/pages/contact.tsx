import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { PublicNavigation } from '@/components/public-navigation';
import { PublicFooter } from '@/components/public-footer';
import { Phone, Mail, Clock, MapPin, Facebook, MessageCircle, Map } from 'lucide-react';

export default function Contact() {
    const { auth } = usePage<SharedData>().props;

    const coverageAreas = [
        'Abucay', 'Bagac', 'Balanga City', 'Dinalupihan', 
        'Hermosa', 'Limay', 'Mariveles', 'Morong', 
        'Orani', 'Orion', 'Pilar', 'Samal'
    ];

    return (
        <>
            <Head title="Contact Us - Kamotech Air-Conditioning Services">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            
            <div className="contact-page">
                <PublicNavigation />
                
                {/* Hero Section */}
                <section className="contact-hero-section">
                    <div className="contact-hero-container">
                        <div className="contact-hero-content">
                            <h1 className="contact-hero-title">Contact Us</h1>
                            <p className="contact-hero-subtitle">Get in touch with Kamotech for all your air-conditioning needs</p>
                        </div>
                    </div>
                </section>

                {/* Contact Information Section */}
                <section className="contact-info-section">
                    <div className="contact-container">
                        <div className="contact-grid">
                            {/* Business Hours */}
                            <div className="contact-card">
                                <div className="contact-card-icon">
                                    <Clock />
                                </div>
                                <h3 className="contact-card-title">Business Hours</h3>
                                <div className="contact-card-content">
                                    <p className="contact-hours">6AM - 6PM daily</p>
                                    <p className="contact-note">We're here to serve you every day of the week!</p>
                                </div>
                            </div>

                            {/* Phone Contact */}
                            <div className="contact-card">
                                <div className="contact-card-icon">
                                    <Phone />
                                </div>
                                <h3 className="contact-card-title">Cellphone Number</h3>
                                <div className="contact-card-content">
                                    <a href="tel:09074452484" className="contact-link">
                                        0907 445 2484
                                    </a>
                                    <p className="contact-note">Call us for immediate assistance</p>
                                </div>
                            </div>

                            {/* Email Contact */}
                            <div className="contact-card">
                                <div className="contact-card-icon">
                                    <Mail />
                                </div>
                                <h3 className="contact-card-title">Email Address</h3>
                                <div className="contact-card-content">
                                    <a href="mailto:Kamotechairconditioningandserv@gmail.com" className="contact-link">
                                        Kamotechairconditioningandserv@gmail.com
                                    </a>
                                    <p className="contact-note">Send us your inquiries anytime</p>
                                </div>
                            </div>

                            {/* Facebook */}
                            <div className="contact-card">
                                <div className="contact-card-icon">
                                    <Facebook />
                                </div>
                                <h3 className="contact-card-title">Facebook Account</h3>
                                <div className="contact-card-content">
                                    <span className="contact-social">Kamotech Aircon Services</span>
                                    <p className="contact-note">Follow us for updates and tips</p>
                                </div>
                            </div>

                            {/* Support */}
                            <div className="contact-card">
                                <div className="contact-card-icon">
                                    <MessageCircle />
                                </div>
                                <h3 className="contact-card-title">Support</h3>
                                <div className="contact-card-content">
                                    <button className="contact-support-btn">
                                        Click here!
                                    </button>
                                    <p className="contact-note">Get technical support and assistance</p>
                                </div>
                            </div>

                            {/* Office Address */}
                            <div className="contact-card">
                                <div className="contact-card-icon">
                                    <MapPin />
                                </div>
                                <h3 className="contact-card-title">Office Address</h3>
                                <div className="contact-card-content">
                                    <address className="contact-address">
                                        Bagong Silang<br />
                                        Balanga City, Bataan<br />
                                        Philippines
                                    </address>
                                    <p className="contact-note">Visit us at our main office</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Coverage Area & Map Section */}
                <section className="contact-coverage-map-section">
                    <div className="contact-container">
                        <div className="contact-section-header">
                            <h2 className="contact-section-title">Coverage Area & Service Map</h2>
                            <div className="contact-title-underline"></div>
                            <p className="contact-section-subtitle">We proudly serve the following areas across Bataan Province</p>
                        </div>
                        
                        <div className="contact-coverage-map-grid">
                            {/* Coverage Areas */}
                            <div className="contact-coverage-content">
                                <h3 className="contact-coverage-subtitle">Service Areas</h3>
                                <div className="contact-coverage-grid">
                                    {coverageAreas.map((area, index) => (
                                        <div key={index} className="contact-coverage-item">
                                            <MapPin className="contact-coverage-icon" />
                                            <span>{area}</span>
                                        </div>
                                    ))}
                                </div>
                                
                                <div className="contact-coverage-note">
                                    <p>Bringing comfort to Bataan — efficiently, one service at a time!</p>
                                </div>
                            </div>
                            
                            {/* Service Coverage Map */}
                            <div className="contact-map-content">
                                <h3 className="contact-map-subtitle">Service Coverage Map</h3>
                                <div className="contact-map-container">
                                    <div className="contact-map-wrapper">
                                        <img 
                                            src="/images/bataan-map.png.png" 
                                            alt="Bataan Service Coverage Map" 
                                            className="contact-map-image"
                                        />
                                        <div className="contact-map-overlay">
                                            <Map className="contact-map-overlay-icon" />
                                            <span>Bataan Province Coverage</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <PublicFooter />
            </div>
        </>
    );
}