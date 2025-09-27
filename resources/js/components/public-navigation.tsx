import { Link, usePage, router } from '@inertiajs/react';
import { Menu, X, ChevronDown, LogOut } from 'lucide-react';
import { useState, useEffect, useRef } from 'react';
import { route } from 'ziggy-js';
import { type SharedData } from '@/types';
import React from 'react';

export function PublicNavigation() {
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

    return (
        <nav className="kamotech-nav">
            <div className="nav-container">
                {/* Logo */}
                <div className="nav-logo">
                    <Link href="/" className="logo-link">
                        <img src="/images/logo-main.png" alt="Kamotech Logo" className="logo-image" />
                    </Link>
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
                                        <Link 
                                            key={index}
                                            href={service.href} 
                                            className="nav-dropdown-link"
                                            onClick={() => setIsServicesDropdownOpen(false)}
                                        >
                                            {service.name}
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </li>
                        <li><a href="#support" className="nav-link">Support</a></li>
                        <li><Link href={route('about')} className="nav-link">About Us</Link></li>
                        <li><Link href={route('contact')} className="nav-link">Contact Us</Link></li>
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
                                <Link 
                                    href={route('customer-dashboard')}
                                    className="nav-auth-link signup-link"
                                >
                                    Dashboard
                                </Link>
                                <button 
                                    onClick={() => router.post(route('logout'))}
                                    className="nav-auth-link signup-link" 
                                    style={{ display: 'flex', alignItems: 'center', gap: '6px' }}
                                >
                                    <LogOut size={16} />
                                    Sign Out
                                </button>
                            </>
                        ) : (
                            <>
                                <Link href={route('login')} className="nav-auth-link login-link">
                                    Log in
                                </Link>
                                <Link href={route('register')} className="nav-auth-link signup-link">
                                    Sign up
                                </Link>
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
                                        <Link 
                                            key={index}
                                            href={service.href} 
                                            className="nav-mobile-dropdown-link"
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                setIsServicesDropdownOpen(false);
                                                setIsMenuOpen(false);
                                            }}
                                        >
                                            {service.name}
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </li>
                        <li><a href="#support" className="nav-mobile-link" onClick={() => setIsMenuOpen(false)}>Support</a></li>
                        <li><Link href={route('about')} className="nav-mobile-link" onClick={() => setIsMenuOpen(false)}>About Us</Link></li>
                        <li><Link href={route('contact')} className="nav-mobile-link" onClick={() => setIsMenuOpen(false)}>Contact Us</Link></li>
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
                                <Link 
                                    href={route('customer-dashboard')}
                                    className="nav-mobile-auth-link signup-link"
                                    onClick={() => setIsMenuOpen(false)}
                                >
                                    Dashboard
                                </Link>
                                <button 
                                    onClick={() => router.post(route('logout'))}
                                    className="nav-mobile-auth-link signup-link"
                                >
                                    Sign Out
                                </button>
                            </>
                        ) : (
                            <>
                                <Link href={route('login')} className="nav-mobile-auth-link login-link">
                                    Log in
                                </Link>
                                <Link href={route('register')} className="nav-mobile-auth-link signup-link">
                                    Sign up
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            )}
        </nav>
    );
}