import { Link, usePage, router } from '@inertiajs/react';
import { Menu, X, ChevronDown, LogOut } from 'lucide-react';
import { useState, useEffect, useRef } from 'react';
import { route } from 'ziggy-js';
import { type SharedData } from '@/types';
import React from 'react';

interface BookingHeaderProps {
  onNavigate: (destination: string) => void;
}

export function BookingHeader({ onNavigate }: BookingHeaderProps) {
    const { auth } = usePage<SharedData>().props;
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [isServicesDropdownOpen, setIsServicesDropdownOpen] = useState(false);
    const dropdownRef = useRef<HTMLLIElement>(null);

    const toggleMenu = () => {
        setIsMenuOpen(!isMenuOpen);
    };

    const showServicesDropdown = () => {
        setIsServicesDropdownOpen(true);
    };

    const hideServicesDropdown = () => {
        setIsServicesDropdownOpen(false);
    };

    // Close dropdown when clicking outside
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent | TouchEvent) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
                setIsServicesDropdownOpen(false);
            }
        };

        const handleMobileClickOutside = (event: MouseEvent | TouchEvent) => {
            const nav = document.querySelector('.nav-mobile');
            const toggle = document.querySelector('.nav-mobile-toggle');
            
            if (isMenuOpen && nav && toggle && 
                !nav.contains(event.target as Node) && 
                !toggle.contains(event.target as Node)) {
                setIsMenuOpen(false);
                setIsServicesDropdownOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('touchstart', handleClickOutside);
        document.addEventListener('mousedown', handleMobileClickOutside);
        document.addEventListener('touchstart', handleMobileClickOutside);
        
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('touchstart', handleClickOutside);
            document.removeEventListener('mousedown', handleMobileClickOutside);
            document.removeEventListener('touchstart', handleMobileClickOutside);
        };
    }, [isMenuOpen]);

    const services = [
        { name: 'AC Cleaning', href: route('services.cleaning') },
        { name: 'AC Repair', href: route('services.repair') },
        { name: 'AC Installation', href: route('services.installation') },
        { name: 'Freon Charging', href: route('services.freon-charging') },
        { name: 'Repiping', href: route('services.repiping') },
        { name: 'Troubleshooting', href: route('services.troubleshooting') },
        { name: 'AC Relocation', href: route('services.relocation') },
    ];

    const handleLinkClick = (e: React.MouseEvent<HTMLAnchorElement>, href: string) => {
        e.preventDefault();
        onNavigate(href);
    };

    const handleLogout = () => {
        // Logout doesn't need warning as it's an explicit action
        router.post(route('logout'));
    };

    return (
        <nav className="kamotech-nav">
            <div className="nav-container">
                {/* Logo */}
                <div className="nav-logo">
                    <a href="/" onClick={(e) => handleLinkClick(e, '/')} className="logo-link">
                        <img src="/images/logo-main.png" alt="Kamotech Logo" className="logo-image" />
                    </a>
                </div>

                {/* Desktop Navigation */}
                <div className="nav-desktop">
                    <ul className="nav-links">
                        <li className="nav-dropdown" ref={dropdownRef} onMouseEnter={showServicesDropdown} onMouseLeave={hideServicesDropdown}>
                            <button 
                                className="nav-link nav-dropdown-toggle"
                                aria-expanded={isServicesDropdownOpen}
                            >
                                Services
                                <ChevronDown 
                                    size={16} 
                                    className={`nav-dropdown-icon ${isServicesDropdownOpen ? 'open' : ''}`} 
                                />
                            </button>
                            {isServicesDropdownOpen && (
                                <div className="nav-dropdown-menu" onMouseEnter={showServicesDropdown} onMouseLeave={hideServicesDropdown}>
                                    {services.map((service, index) => (
                                        <a 
                                            key={index}
                                            href={service.href} 
                                            className="nav-dropdown-link"
                                            onClick={(e) => {
                                                setIsServicesDropdownOpen(false);
                                                handleLinkClick(e, service.href);
                                            }}
                                        >
                                            {service.name}
                                        </a>
                                    ))}
                                </div>
                            )}
                        </li>
                        <li><a href="#support" className="nav-link">Support</a></li>
                        <li><a href={route('about')} onClick={(e) => handleLinkClick(e, route('about'))} className="nav-link">About Us</a></li>
                        <li><a href={route('contact')} onClick={(e) => handleLinkClick(e, route('contact'))} className="nav-link">Contact Us</a></li>
                    </ul>
                    
                    <div className="nav-auth">
                        {auth.user ? (
                            <>
                                <span className="nav-auth-welcome" style={{ 
                                    color: '#374151', 
                                    fontSize: '14px',
                                    marginRight: '1rem',
                                    display: 'flex',
                                    alignItems: 'center',
                                    fontWeight: '500'
                                }}>
                                    Welcome, {auth.user.name}
                                </span>
                                <button
                                    onClick={() => onNavigate(route('customer-dashboard'))}
                                    className="nav-auth-link signup-link"
                                >
                                    Dashboard
                                </button>
                                <button 
                                    onClick={handleLogout}
                                    className="nav-auth-link signup-link" 
                                    style={{ display: 'flex', alignItems: 'center', gap: '6px' }}
                                >
                                    <LogOut size={16} />
                                    Sign Out
                                </button>
                            </>
                        ) : (
                            <>
                                <button onClick={() => onNavigate(route('login'))} className="nav-auth-link login-link">
                                    Log in
                                </button>
                                <button onClick={() => onNavigate(route('register'))} className="nav-auth-link signup-link">
                                    Sign up
                                </button>
                            </>
                        )}
                    </div>
                </div>

                {/* Mobile Menu Button */}
                <button 
                    className="nav-mobile-toggle"
                    onClick={toggleMenu}
                    aria-label="Toggle navigation menu"
                >
                    {isMenuOpen ? <X size={24} /> : <Menu size={24} />}
                </button>
            </div>

            {/* Mobile Navigation */}
            {isMenuOpen && (
                <div className="nav-mobile">
                    <ul className="nav-mobile-links">
                        <li className="nav-mobile-dropdown">
                            <button 
                                className="nav-mobile-link nav-mobile-dropdown-toggle"
                                onClick={(e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    setIsServicesDropdownOpen(!isServicesDropdownOpen);
                                }}
                                aria-expanded={isServicesDropdownOpen}
                            >
                                Services
                                <ChevronDown 
                                    size={16} 
                                    className={`nav-mobile-dropdown-icon ${isServicesDropdownOpen ? 'open' : ''}`} 
                                />
                            </button>
                            {isServicesDropdownOpen && (
                                <div className="nav-mobile-dropdown-menu">
                                    {services.map((service, index) => (
                                        <a 
                                            key={index}
                                            href={service.href} 
                                            className="nav-mobile-dropdown-link"
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                setIsServicesDropdownOpen(false);
                                                setIsMenuOpen(false);
                                                handleLinkClick(e, service.href);
                                            }}
                                        >
                                            {service.name}
                                        </a>
                                    ))}
                                </div>
                            )}
                        </li>
                        <li><a href="#support" className="nav-mobile-link" onClick={() => setIsMenuOpen(false)}>Support</a></li>
                        <li><a href={route('about')} onClick={(e) => { setIsMenuOpen(false); handleLinkClick(e, route('about')); }} className="nav-mobile-link">About Us</a></li>
                        <li><a href={route('contact')} onClick={(e) => { setIsMenuOpen(false); handleLinkClick(e, route('contact')); }} className="nav-mobile-link">Contact Us</a></li>
                    </ul>
                    
                    <div className="nav-mobile-auth">
                        {auth.user ? (
                            <>
                                <span className="nav-mobile-welcome" style={{ 
                                    color: '#374151', 
                                    fontSize: '14px',
                                    marginBottom: '0.5rem',
                                    display: 'block',
                                    textAlign: 'center',
                                    fontWeight: '500'
                                }}>
                                    Welcome, {auth.user.name}
                                </span>
                                <button
                                    onClick={() => {
                                        setIsMenuOpen(false);
                                        onNavigate(route('customer-dashboard'));
                                    }}
                                    className="nav-mobile-auth-link signup-link"
                                >
                                    Dashboard
                                </button>
                                <button 
                                    onClick={handleLogout}
                                    className="nav-mobile-auth-link signup-link"
                                >
                                    Sign Out
                                </button>
                            </>
                        ) : (
                            <>
                                <button onClick={() => { setIsMenuOpen(false); onNavigate(route('login')); }} className="nav-mobile-auth-link login-link">
                                    Log in
                                </button>
                                <button onClick={() => { setIsMenuOpen(false); onNavigate(route('register')); }} className="nav-mobile-auth-link signup-link">
                                    Sign up
                                </button>
                            </>
                        )}
                    </div>
                </div>
            )}
        </nav>
    );
}
