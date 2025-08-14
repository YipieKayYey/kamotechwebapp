import { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface SlideData {
    id: number;
    backgroundImage: string;
    welcome: string;
    title: string;
    subtitle: string;
    primaryButton: {
        text: string;
        href: string;
    };
    secondaryButton: {
        text: string;
        href: string;
    };
}

const slides: SlideData[] = [
    {
        id: 1,
        backgroundImage: '/images/slide/1.jpg',
        welcome: 'Welcome to Kamotech',
        title: 'FAST AND RELIABLE AIR CONDITIONING SERVICES',
        subtitle: 'Book a service at very affordable price!',
        primaryButton: {
            text: 'BOOK NOW',
            href: '#booking'
        },
        secondaryButton: {
            text: 'SIGN UP',
            href: '/register'
        }
    },
    {
        id: 2,
        backgroundImage: '/images/slide/2.jpg',
        welcome: 'Professional AC Services',
        title: 'EXPERT INSTALLATION & MAINTENANCE',
        subtitle: 'Quality workmanship with 24/7 emergency support!',
        primaryButton: {
            text: 'GET QUOTE',
            href: '#booking'
        },
        secondaryButton: {
            text: 'LEARN MORE',
            href: '#services'
        }
    },
    {
        id: 3,
        backgroundImage: '/images/slide/3.jpg',
        welcome: 'Trusted Since Day One',
        title: 'ALL BRANDS SERVICED WITH CARE',
        subtitle: 'From cleaning to repairs - we handle it all!',
        primaryButton: {
            text: 'VIEW SERVICES',
            href: '#services'
        },
        secondaryButton: {
            text: 'CONTACT US',
            href: '#contact'
        }
    },
    {
        id: 4,
        backgroundImage: '/images/slide/4.jpg',
        welcome: 'Quality You Can Trust',
        title: 'AFFORDABLE PRICES, EXCEPTIONAL SERVICE',
        subtitle: 'Your satisfaction is our guarantee!',
        primaryButton: {
            text: 'SCHEDULE NOW',
            href: '#booking'
        },
        secondaryButton: {
            text: 'GET ESTIMATE',
            href: '#contact'
        }
    }
];

export function HeroSlider() {
    const [currentSlide, setCurrentSlide] = useState(0);
    const [isAutoPlaying, setIsAutoPlaying] = useState(true);

    // Auto-advance slides
    useEffect(() => {
        if (!isAutoPlaying) return;

        const interval = setInterval(() => {
            setCurrentSlide((prev) => (prev + 1) % slides.length);
        }, 5000); // Change slide every 5 seconds

        return () => clearInterval(interval);
    }, [isAutoPlaying]);

    const goToSlide = (index: number) => {
        setCurrentSlide(index);
        setIsAutoPlaying(false);
        // Resume auto-play after 10 seconds of manual interaction
        setTimeout(() => setIsAutoPlaying(true), 10000);
    };

    const nextSlide = () => {
        goToSlide((currentSlide + 1) % slides.length);
    };

    const prevSlide = () => {
        goToSlide((currentSlide - 1 + slides.length) % slides.length);
    };

    return (
        <section className="hero-slider">
            <div className="slider-container">
                {slides.map((slide, index) => (
                    <div
                        key={slide.id}
                        className={`slide ${index === currentSlide ? 'active' : ''}`}
                        style={{
                            backgroundImage: `linear-gradient(rgba(0, 63, 107, 0.7), rgba(30, 64, 175, 0.8)), url(${slide.backgroundImage})`
                        }}
                    >
                        <div className="slide-overlay"></div>
                        <div className="slide-container">
                            <div className="slide-content">
                                <p className="slide-welcome">{slide.welcome}</p>
                                <h1 className="slide-title">{slide.title}</h1>
                                <p className="slide-subtitle">{slide.subtitle}</p>
                                <div className="slide-buttons">
                                    <a 
                                        href={slide.primaryButton.href} 
                                        className="slide-btn slide-btn-primary"
                                    >
                                        {slide.primaryButton.text}
                                    </a>
                                    {slide.secondaryButton.href.startsWith('/') ? (
                                        <Link 
                                            href={route(slide.secondaryButton.href.slice(1))} 
                                            className="slide-btn slide-btn-secondary"
                                        >
                                            {slide.secondaryButton.text}
                                        </Link>
                                    ) : (
                                        <a 
                                            href={slide.secondaryButton.href} 
                                            className="slide-btn slide-btn-secondary"
                                        >
                                            {slide.secondaryButton.text}
                                        </a>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {/* Navigation Arrows */}
            <button 
                className="slider-nav slider-nav-prev" 
                onClick={prevSlide}
                aria-label="Previous slide"
            >
                <ChevronLeft size={24} />
            </button>
            <button 
                className="slider-nav slider-nav-next" 
                onClick={nextSlide}
                aria-label="Next slide"
            >
                <ChevronRight size={24} />
            </button>

            {/* Slide Indicators */}
            <div className="slider-indicators">
                {slides.map((_, index) => (
                    <button
                        key={index}
                        className={`indicator ${index === currentSlide ? 'active' : ''}`}
                        onClick={() => goToSlide(index)}
                        aria-label={`Go to slide ${index + 1}`}
                    />
                ))}
            </div>
        </section>
    );
}