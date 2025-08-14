import { Link } from '@inertiajs/react';
import { Phone, Mail, MapPin, Clock } from 'lucide-react';

export function PublicFooter() {
    return (
        <footer className="kamotech-footer">
            <div className="footer-container">
                <div className="footer-content">
                    {/* Company Info */}
                    <div className="footer-section">
                        <h4 className="footer-title">Kamotech</h4>
                        <p className="footer-description">
                            Your trusted partner for professional air-conditioning services. 
                            We provide expert installation, maintenance, and repair services 
                            for residential and commercial properties.
                        </p>
                        <div className="footer-contact-item">
                            
                        </div>
                    </div>

                    {/* Services */}
                    <div className="footer-section">
                        <h4 className="footer-subtitle">Our Services</h4>
                        <ul className="footer-links">
                            <li><a href="/services/cleaning" className="footer-link">Cleaning</a></li>
                            <li><a href="/services/repair" className="footer-link">Repair</a></li>
                            <li><a href="/services/installation" className="footer-link">Installation</a></li>
                            <li><a href="/services/freon-charging" className="footer-link">Freon Charging</a></li>
                            <li><a href="/services/repiping" className="footer-link">Repiping</a></li>
                            <li><a href="/services/troubleshooting" className="footer-link">Troubleshooting</a></li>
                            <li><a href="/services/relocation" className="footer-link">Relocation</a></li>
                        </ul>
                    </div>

                    {/* Quick Links */}
                    <div className="footer-section">
                        <h4 className="footer-subtitle">Quick Links</h4>
                        <ul className="footer-links">
                            <li><Link href={route('about')} className="footer-link">About Us</Link></li>
                            <li><a href="#support" className="footer-link">Support</a></li>
                            <li><Link href={route('contact')} className="footer-link">Contact Us</Link></li>
                            <li><Link href={route('login')} className="footer-link">Customer Login</Link></li>
                            <li><a href="#booking" className="footer-link">Book Service</a></li>
                        </ul>
                    </div>

                    {/* Contact Info */}
                    <div className="footer-section">
                        <h4 className="footer-subtitle">Contact Us</h4>
                        <div className="footer-contact">
                            <div className="footer-contact-item">
                                <Phone className="footer-icon" />
                                <a href="tel:09074452484"> 
                                    <span>0907 445 2484</span>
                                </a>
                            </div>
                            <div className="footer-contact-item">
                                <Mail className="footer-icon" />
                                <a href="mailto:kamotechairconditioningandserv@gmail.com">  
                                    <span>kamotechairconditioning<br />andserv@gmail.com</span>
                                </a>
                            </div>
                            <div className="footer-contact-item">
                                <MapPin className="footer-icon" />
                                <a href="https://maps.app.goo.gl/1234567890">
                                    <span>Bagong Silang<br />Balanga City, Bataan</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="footer-bottom">
                    <p>&copy; 2025 Kamotech Air-Conditioning Services. All Rights Reserved.</p>
                </div>
            </div>
        </footer>
    );
}